<?php
// +----------------------------------------------------------------------
// | 萤火商城系统 [ 致力于通过产品和服务，帮助商家高效化开拓市场 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017~2025 https://www.yiovo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed 这不是一个自由软件，不允许对程序代码以任何形式任何目的的再发行
// +----------------------------------------------------------------------
// | Author: 萤火科技 <admin@yiovo.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\common\service\order;

use app\common\model\User as UserModel;
use app\common\model\Order as OrderModel;
use app\common\model\PaymentTrade as PaymentTradeModel;
use app\common\model\user\BalanceLog as BalanceLogModel;
use app\common\service\order\source\Factory as OrderSourceFactory;
use app\common\enum\order\OrderStatus as OrderStatusEnum;
use app\common\enum\order\PayStatus as PayStatusEnum;
use app\common\enum\OrderType as OrderTypeEnum;
use app\common\enum\payment\Method as PaymentMethodEnum;
use app\common\enum\user\balanceLog\Scene as SceneEnum;
use app\common\library\Lock;
use app\common\library\Log;
use app\common\service\BaseService;
use app\common\service\goods\source\Factory as StockFactory;
use app\common\service\Order as OrderService;
use app\common\service\order\Refund as RefundService;
use cores\exception\BaseException;
use think\facade\Event;

/**
 * 订单支付成功服务类
 * Class PaySuccess
 * @package app\common\service\order
 */
class PaySuccess extends BaseService
{
    // 当前订单信息
    public OrderModel $orderInfo;

    // 当前用户信息
    private UserModel $userInfo;

    // 当前订单号
    private string $orderNo;

    // 订单支付方式
    private string $method;

    // 第三方交易记录ID
    private ?int $tradeId = null;

    // 第三方支付成功返回的数据
    private array $paymentData = [];

    /**
     * 设置当前的订单号
     * @param string $orderNo
     * @return $this
     */
    public function setOrderNo(string $orderNo): PaySuccess
    {
        $this->orderNo = $orderNo;
        return $this;
    }

    /**
     * 设置订单支付方式
     * @param string $method
     * @return $this
     */
    public function setMethod(string $method): PaySuccess
    {
        $this->method = $method;
        return $this;
    }

    /**
     * 第三方支付交易记录ID
     * @param int|null $tradeId
     * @return $this
     */
    public function setTradeId(?int $tradeId = null): PaySuccess
    {
        $this->tradeId = $tradeId;
        return $this;
    }

    /**
     * 第三方支付成功返回的数据
     * @param array $paymentData
     * @return $this
     */
    public function setPaymentData(array $paymentData): PaySuccess
    {
        $this->paymentData = $paymentData;
        return $this;
    }

    /**
     * 订单支付成功业务处理
     * @return bool
     * @throws BaseException
     */
    public function handle(): bool
    {
        // 验证当前参数是否合法
        $this->verifyParameters();
        // 当前订单开启并发锁
        $this->lockUp();
        // 验证当前订单是否允许支付
        if ($this->checkOrderStatusOnPay()) {
            // 更新订单状态为已付款
            $this->updatePayStatus();
            // 订单支付成功事件 (处理订单来源相关业务)
            Event::trigger('OrderPaySuccess', [
                'order' => $this->getOrderInfo(),
                'orderType' => OrderTypeEnum::ORDER
            ]);
        }
        // 当前订单解除并发锁
        $this->unLock();
        return true;
    }

    /**
     * 验证当前参数是否合法
     * @throws BaseException
     */
    private function verifyParameters()
    {
        if (empty($this->orderNo)) {
            throwError('orderNo not found');
        }
        if (empty($this->method)) {
            throwError('method not found');
        }
        if ($this->tradeId) {
            empty($this->paymentData) && throwError('PaymentData not found');
            !isset($this->paymentData['tradeNo']) && throwError('PaymentData not found');
        }
        // 记录日志
        Log::append('PaySuccess --handle', [
            'orderNo' => $this->orderNo, 'method' => $this->method,
            'tradeId' => $this->tradeId, 'paymentData' => $this->paymentData
        ]);
    }

    /**
     * 获取当前订单的详情信息
     * @return OrderModel|null
     * @throws BaseException
     */
    private function getOrderInfo(): ?OrderModel
    {
        // 获取订单详情 (待支付状态)
        if (empty($this->orderInfo)) {
            $this->orderInfo = OrderModel::getPayDetail($this->orderNo);
        }
        // 判断订单是否存在
        if (empty($this->orderInfo)) {
            throwError('未找到该订单信息');
        }
        return $this->orderInfo;
    }

    /**
     * 订单模型
     * @return OrderModel|null
     * @throws BaseException
     */
    private function orderModel(): ?OrderModel
    {
        return $this->getOrderInfo();
    }

    /**
     * 验证当前订单是否允许支付
     * @return bool
     * @throws BaseException
     */
    private function checkOrderStatusOnPay(): bool
    {
        // 当前订单信息
        $orderInfo = $this->getOrderInfo();
        // 验证余额支付时用户余额是否满足
        if ($this->method == PaymentMethodEnum::BALANCE) {
            if ($this->getUserInfo()['balance'] < $orderInfo['pay_price']) {
                throwError('账户余额不足，无法使用余额支付');
            }
        }
        // 检查订单状态是否为已支付
        if ($orderInfo['pay_status'] == PayStatusEnum::SUCCESS) {
            $this->onOrderPaid();
            return false;
        }
        // 检查订单是否允许支付
        $orderSource = OrderSourceFactory::getFactory($this->orderInfo['order_source']);
        if (!$orderSource->checkOrderStatusOnPay($this->orderInfo)) {
            // 记录日志
            Log::append('PaySuccess --checkOrderStatusOnPay', ['errorMsg' => $orderSource->getError()]);
            // 执行原路退款
            $this->originalRefund();
            // 取消和关闭订单
            $this->cancelOrder();
            return false;
        }
        return true;
    }

    /**
     * 处理订单已支付的情况
     * @throws BaseException
     */
    private function onOrderPaid()
    {
        // 记录日志
        Log::append('PaySuccess --onOrderPaid', ['title' => '处理订单已支付的情况']);
        // 当前订单信息
        $orderInfo = $this->getOrderInfo();
        // 余额支付直接返回错误信息
        if (in_array($this->method, [PaymentMethodEnum::BALANCE])) {
            throwError('当前订单已支付，无需重复支付');
        }
        // 第三方支付判断是否为重复下单 （因异步回调可能存在网络延迟的原因，在并发的情况下会出现同时付款两次，这里需要容错）
        // 如果订单记录中已存在tradeId并且和当前支付的tradeId不一致, 那么判断为重复的订单, 需进行退款处理
        if ($this->tradeId > 0 && $orderInfo['trade_id'] != $this->tradeId) {
            // 执行原路退款
            $this->originalRefund();
        }
    }

    /**
     * 订单原路退款
     * @return void
     * @throws BaseException
     */
    private function originalRefund(): void
    {
        // 记录日志
        Log::append('PaySuccess --originalRefund', ['title' => '订单原路退款']);
        // 当前订单信息
        $orderInfo = $this->getOrderInfo();
        // 余额支付无需退款 (因为是同步执行)
        if (in_array($this->method, [PaymentMethodEnum::BALANCE])) {
            return;
        }
        // 执行第三方支付原路退款
        try {
            $orderInfo['trade_id'] = $this->tradeId;
            $orderInfo['pay_method'] = $this->method;
            $status = (new RefundService)->handle($orderInfo);
            Log::append('PaySuccess --originalRefund', ['status' => $status ? 'true' : 'false']);
        } catch (\Throwable $e) {
            Log::append('PaySuccess --originalRefund', ['status' => 'false', 'errorMsg' => $e->getMessage()]);
        }
    }

    /**
     * 取消并关闭订单
     * @throws BaseException
     */
    private function cancelOrder(): void
    {
        // 记录日志
        Log::append('PaySuccess --cancelOrder', ['title' => '取消并关闭订单']);
        // 当前订单信息
        $orderInfo = $this->getOrderInfo();
        // 订单取消事件
        OrderService::cancelEvent($orderInfo);
        // 更新订单状态
        $this->orderModel()->save(['order_status' => OrderStatusEnum::CANCELLED]);
    }

    /**
     * 订单已付款事件
     * @return void
     * @throws BaseException
     */
    private function updatePayStatus(): void
    {
        // 记录日志
        Log::append('PaySuccess --updatePayStatus', ['title' => '订单已付款事件']);
        // 当前订单信息
        $orderInfo = $this->getOrderInfo();
        // 事务处理
        $this->orderModel()->transaction(function () use ($orderInfo) {
            // 更新订单状态
            $this->updateOrderStatus();
            // 累积用户总消费金额
            UserModel::setIncPayMoney($orderInfo['user_id'], (float)$orderInfo['pay_price']);
            // 记录订单支付信息
            $this->updatePayInfo();
        });
    }

    /**
     * 获取买家用户信息
     * @return UserModel|array|null
     * @throws BaseException
     */
    private function getUserInfo()
    {
        if (empty($this->userInfo)) {
            $this->userInfo = UserModel::detail($this->getOrderInfo()['user_id']);
        }
        if (empty($this->userInfo)) {
            throwError('未找到买家用户信息');
        }
        return $this->userInfo;
    }

    /**
     * 更新订单状态
     * @throws BaseException
     */
    private function updateOrderStatus(): void
    {
        // 当前订单信息
        $orderInfo = $this->getOrderInfo();
        // 更新商品库存、销量
        StockFactory::getFactory($orderInfo['order_source'])->updateStockSales($orderInfo['goods']);
        // 更新订单状态
        $this->orderModel()->save([
            'pay_method' => $this->method,
            'pay_status' => PayStatusEnum::SUCCESS,
            'pay_time' => time(),
            'trade_id' => $this->tradeId ?: 0,
        ]);
    }

    /**
     * 记录订单支付的信息
     * @throws BaseException
     */
    private function updatePayInfo()
    {
        // 当前订单信息
        $orderInfo = $this->getOrderInfo();
        // 余额支付
        if ($this->method == PaymentMethodEnum::BALANCE) {
            $this->updateBalanceRecord($orderInfo);
        }
        // 将第三方交易记录更新为已支付状态
        if (in_array($this->method, [PaymentMethodEnum::WECHAT, PaymentMethodEnum::ALIPAY])) {
            $this->updateTradeRecord();
        }
    }

    /**
     * 更新余额支付记录
     * @param $orderInfo
     * @return void
     */
    private function updateBalanceRecord($orderInfo)
    {
        if ($orderInfo['pay_price'] <= 0) {
            return;
        }
        // 更新用户余额
        UserModel::setDecBalance((int)$orderInfo['user_id'], (float)$orderInfo['pay_price']);
        // 新增余额变动记录
        BalanceLogModel::add(SceneEnum::CONSUME, [
            'user_id' => (int)$orderInfo['user_id'],
            'money' => -$orderInfo['pay_price'],
        ], ['order_no' => $orderInfo['order_no']], $orderInfo['store_id']);
    }

    /**
     * 将第三方交易记录更新为已支付状态
     */
    private function updateTradeRecord()
    {
        if ($this->tradeId && !empty($this->paymentData)) {
            PaymentTradeModel::updateToPaySuccess($this->tradeId, $this->paymentData['tradeNo']);
        }
    }

    /**
     * 订单锁：防止并发导致重复支付
     * @throws BaseException
     */
    private function lockUp()
    {
        $orderInfo = $this->getOrderInfo();
        Lock::lockUp("OrderPaySuccess_{$orderInfo['order_id']}");
    }

    /**
     * 订单锁：防止并发导致重复支付
     * @throws BaseException
     */
    private function unLock()
    {
        $orderInfo = $this->getOrderInfo();
        Lock::unLock("OrderPaySuccess_{$orderInfo['order_id']}");
    }
}
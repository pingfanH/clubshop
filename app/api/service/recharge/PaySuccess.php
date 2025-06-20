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

namespace app\api\service\recharge;

use app\api\model\PaymentTrade as PaymentTradeModel;
use app\common\enum\payment\Method as PaymentMethodEnum;
use app\common\service\BaseService;
use app\api\model\User as UserModel;
use app\api\model\recharge\Order as OrderModel;
use app\api\model\user\BalanceLog as BalanceLogModel;
use app\common\enum\user\balanceLog\Scene as SceneEnum;
use app\common\enum\recharge\order\PayStatus as PayStatusEnum;
use cores\exception\BaseException;

/**
 * 余额充值订单支付成功服务类
 * Class PaySuccess
 * @package app\api\service\order
 */
class PaySuccess extends BaseService
{
    // 当前订单信息
    public OrderModel $orderInfo;

    // 当前订单号
    private string $orderNo;

    // 订单支付方式
    private string $method;

    // 第三方交易记录ID
    private int $tradeId;

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
     * @param int $tradeId
     * @return $this
     */
    public function setTradeId(int $tradeId): PaySuccess
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
        // 验证当前订单是否允许支付
        if ($this->checkOrderStatusOnPay()) {
            // 更新订单状态为已付款
            $this->updatePayStatus();
        }
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
        // 检查订单状态是否为已支付
        if ($orderInfo['pay_status'] == PayStatusEnum::SUCCESS) {
            return false;
        }
        return true;
    }

    /**
     * 更新订单状态为已付款
     * @return void
     * @throws BaseException
     */
    private function updatePayStatus(): void
    {
        // 当前订单信息
        $orderInfo = $this->getOrderInfo();
        try {
            // 开启事务处理
            $this->orderModel()->startTrans();
            // 更新订单状态
            $this->orderModel()->save([
                'pay_status' => PayStatusEnum::SUCCESS,
                'pay_time' => time(),
                'pay_method' => $this->method,
                'trade_id' => $this->tradeId ?: 0,
            ]);
            // 累积用户余额
            UserModel::setIncBalance((int)$orderInfo['user_id'], (float)$orderInfo['actual_money']);
            // 用户余额变动明细
            BalanceLogModel::add(SceneEnum::RECHARGE, [
                'user_id' => $orderInfo['user_id'],
                'money' => $orderInfo['actual_money'],
                'store_id' => $orderInfo['store_id'],
            ], ['order_no' => $orderInfo['order_no']]);
            // 将第三方交易记录更新为已支付状态
            if (in_array($this->method, [PaymentMethodEnum::WECHAT, PaymentMethodEnum::ALIPAY])) {
                $this->updateTradeRecord();
            }
            // 提交事务处理
            $this->orderModel()->commit();
        } catch (\Throwable $e) {
            $this->orderModel()->rollback();
        }
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
}
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

use app\api\model\Payment as PaymentModel;
use app\api\model\recharge\Order as OrderModel;
use app\api\model\PaymentTrade as PaymentTradeModel;
use app\api\service\User as UserService;
use app\api\service\Order as OrderService;
use app\api\service\recharge\PaySuccess as RechargePaySuccesService;
use app\common\service\BaseService;
use app\common\enum\Client as ClientEnum;
use app\common\enum\OrderType as OrderTypeEnum;
use app\common\enum\payment\Method as PaymentMethodEnum;
use app\common\library\payment\Facade as PaymentFacade;
use cores\exception\BaseException;

/**
 * 余额充值订单付款服务类
 * Class Payment
 * @package app\api\controller
 */
class Payment extends BaseService
{
    // 提示信息
    private string $message = '';

    // 订单信息
    private OrderModel $orderInfo;

    // 支付方式 (微信支付、支付宝)
    private string $method = '';

    // 下单的客户端
    private string $client = '';

    /**
     * 设置当前支付方式
     * @param string $method 支付方式
     * @return $this
     */
    public function setMethod(string $method): Payment
    {
        $this->method = $method;
        return $this;
    }

    /**
     * 设置下单的客户端
     * @param string $client 客户端
     * @return $this
     */
    public function setClient(string $client): Payment
    {
        $this->client = $client;
        return $this;
    }

    /**
     * 确认订单支付事件
     * @param int|null $planId 方案ID
     * @param string|null $customMoney 自定义金额
     * @param array $extra 附加数据
     * @return array[]
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function orderPay(?int $planId = null, string $customMoney = null, array $extra = []): array
    {
        // 创建余额订单信息
        $this->orderInfo = $this->createOrder($planId, $customMoney);
        // 构建第三方支付请求的参数
        $payment = $this->unifiedorder($extra);
        // 记录第三方交易信息
        $this->recordPaymentTrade($payment);
        // 返回结果
        return compact('payment');
    }

    /**
     * 创建充值订单
     * @param int|null $planId 方案ID
     * @param string|null $customMoney 自定义金额
     * @return OrderModel
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function createOrder(?int $planId = null, string $customMoney = null): OrderModel
    {
        $model = new OrderModel;
        if (!$model->createOrder($planId, $customMoney)) {
            throwError($model->getError() ?: '创建充值订单失败');
        }
        $model['order_id'] = (int)$model['order_id'];
        return $model;
    }

    /**
     * 查询订单是否支付成功 (仅限第三方支付订单)
     * @param string $outTradeNo 商户订单号
     * @return bool
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function tradeQuery(string $outTradeNo): bool
    {
        // 判断支付方式是否合法
        if (!in_array($this->method, [PaymentMethodEnum::WECHAT, PaymentMethodEnum::ALIPAY])) {
            return false;
        }
        // 获取支付方式的配置信息
        $options = $this->getPaymentConfig();
        // 构建支付模块
        $Payment = PaymentFacade::store($this->method)->setOptions($options, $this->client);
        // 执行第三方支付查询API
        $result = $Payment->tradeQuery($outTradeNo);
        // 订单支付成功事件
        if (!empty($result) && $result['paySuccess']) {
            // 获取第三方交易记录信息
            $tradeInfo = PaymentTradeModel::detailByOutTradeNo($outTradeNo);
            // 订单支付成功事件
            $this->orderPaySuccess($tradeInfo['order_no'], $tradeInfo['trade_id'], $result);
        }
        // 返回订单状态
        return $result ? $result['paySuccess'] : false;
    }

    /**
     * 记录第三方交易信息
     * @param array $payment 第三方支付数据
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function recordPaymentTrade(array $payment): void
    {
        if (!in_array($this->method, [PaymentMethodEnum::BALANCE])) {
            PaymentTradeModel::record(
                $this->orderInfo,
                $this->method,
                $this->client,
                OrderTypeEnum::RECHARGE,
                $payment
            );
        }
    }

    /**
     * 返回消息提示
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * 构建第三方支付请求的参数
     * @param array $extra 附加数据
     * @return array
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function unifiedorder(array $extra = []): array
    {
        // 判断支付方式是否合法
        if (!in_array($this->method, [PaymentMethodEnum::WECHAT, PaymentMethodEnum::ALIPAY])) {
            return [];
        }
        // 生成第三方交易订单号 (并非主订单号)
        $outTradeNo = OrderService::createOrderNo();
        // 获取支付方式的配置信息
        $options = $this->getPaymentConfig();
        // 整理下单接口所需的附加数据
        $extra = $this->extraAsUnify($extra);
        // 构建支付模块
        $Payment = PaymentFacade::store($this->method)->setOptions($options, $this->client);
        // 执行第三方支付下单API
        if (!$Payment->unify($outTradeNo, (string)$this->orderInfo['pay_price'], $extra)) {
            throwError('第三方支付下单API调用失败');
        }
        // 返回客户端需要的支付参数
        return $Payment->getUnifyResult();
    }

    /**
     * 获取支付方式的配置信息
     * @return mixed
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getPaymentConfig()
    {
        $PaymentModel = new PaymentModel;
        $templateInfo = $PaymentModel->getPaymentInfo($this->method, $this->client, $this->getStoreId());
        return $templateInfo['template']['config'][$this->method];
    }

    /**
     * 整理下单接口所需的附加数据
     * @param array $extra
     * @return array
     * @throws BaseException
     */
    private function extraAsUnify(array $extra = []): array
    {
        // 微信支付时需要的附加数据
        if ($this->method === PaymentMethodEnum::WECHAT) {
            // 微信小程序端需要openid
            if (in_array($this->client, [ClientEnum::MP_WEIXIN])) {
                $extra['openid'] = $this->getWechatOpenid();
            }
        }
        // 支付宝支付时需要的附加数据
        if ($this->method === PaymentMethodEnum::ALIPAY) {

        }
        return $extra;
    }

    /**
     * 获取微信端的用户openid(仅微信小程序)
     * @return null
     * @throws BaseException
     */
    private function getWechatOpenid()
    {
        if (in_array($this->client, [ClientEnum::MP_WEIXIN])) {
            // 当前登录用户信息
            $useInfo = UserService::getCurrentLoginUser(true);
            if (!$useInfo['currentOauth'] || empty($useInfo['currentOauth']['oauth_id'])) {
                throwError('很抱歉，您当前不存在openid 无法发起微信支付');
            }
            // 当前第三方用户标识
            return $useInfo['currentOauth']['oauth_id'];
        }
        return null;
    }

    /**
     * 订单支付成功事件
     * @param string $orderNo 当前订单号
     * @param int|null $tradeId 第三方交易记录ID
     * @param array $paymentData 第三方支付成功返回的数据
     * @return void
     * @throws BaseException
     */
    private function orderPaySuccess(string $orderNo, ?int $tradeId = null, array $paymentData = []): void
    {
        // 获取订单详情
        $service = new RechargePaySuccesService;
        // 订单支付成功业务处理
        $service->setOrderNo($orderNo)->setMethod($this->method)->setTradeId($tradeId)->setPaymentData($paymentData);
        if (!$service->handle()) {
            throwError($service->getError() ?: '订单支付失败');
        }
        $this->message = '恭喜您，余额充值成功';
    }
}
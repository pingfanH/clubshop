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

use app\common\model\Payment as PaymentModel;
use app\common\model\PaymentTrade as PaymentTradeModel;
use app\common\model\User as UserModel;
use app\common\model\user\BalanceLog as BalanceLogModel;
use app\common\service\BaseService;
use app\common\enum\payment\Method as PaymentMethodEnum;
use app\common\enum\user\balanceLog\Scene as SceneEnum;
use app\common\library\payment\Facade as PaymentFacade;
use cores\exception\BaseException;

/**
 * 订单退款服务类
 * Class Refund
 * @package app\common\service\order
 */
class Refund extends BaseService
{
    /**
     * 执行订单退款
     * @param mixed $order 订单信息
     * @param string|null $money 退款金额
     * @return bool
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function handle($order, ?string $money = null): bool
    {
        // 退款金额，如不指定则默认为订单实付款金额
        is_null($money) && $money = (string)$order['pay_price'];
        if ($money == 0) {
            return true;
        }
        // 余额支付退款
        if ($order['pay_method'] === PaymentMethodEnum::BALANCE) {
            return $this->balance($order, $money);
        }
        // 第三方支付退款
        if (in_array($order['pay_method'], [PaymentMethodEnum::WECHAT, PaymentMethodEnum::ALIPAY])) {
            return $this->payment($order, $money);
        }
        return false;
    }

    /**
     * 余额支付退款
     * @param mixed $order 订单信息
     * @param string $money 退款金额
     * @return bool
     */
    private function balance($order, string $money): bool
    {
        if ($money <= 0) {
            return false;
        }
        // 回退用户余额
        UserModel::setIncBalance((int)$order['user_id'], (float)$money);
        // 记录余额明细
        BalanceLogModel::add(SceneEnum::REFUND, [
            'user_id' => $order['user_id'],
            'money' => $money,
        ], ['order_no' => $order['order_no']], $order['store_id']);
        return true;
    }

    /**
     * 第三方支付退款
     * @param mixed $order 订单信息
     * @param string $money 退款金额
     * @return bool
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function payment($order, string $money): bool
    {
        // 获取第三方交易记录
        $tradeInfo = $this->getTradeInfo($order['trade_id']);
        // 获取支付方式的配置信息
        $options = $this->getPaymentConfig($order);
        // 构建支付模块
        $Payment = PaymentFacade::store($order['pay_method'])->setOptions($options, $order['platform']);
        // 执行第三方支付下单API
        if (!$Payment->refund($tradeInfo['out_trade_no'], $money, ['totalFee' => (string)$order['pay_price']])) {
            throwError($Payment->getError() ?: '第三方支付退款API调用失败');
        }
        // 将第三方交易记录更新为已退款状态
        $this->updateTradeState($order['trade_id']);
        return true;
    }

    /**
     * 获取第三方交易记录
     * @param int $tradeId 交易记录ID
     * @return PaymentTradeModel|null
     * @throws BaseException
     */
    private function getTradeInfo(int $tradeId): ?PaymentTradeModel
    {
        $tradeInfo = PaymentTradeModel::detail($tradeId);
        empty($tradeInfo) && throwError('未找到第三方交易记录');
        return $tradeInfo;
    }

    /**
     * 将第三方交易记录更新为已退款状态
     * @param int $tradeId 交易记录ID
     */
    private function updateTradeState(int $tradeId)
    {
        PaymentTradeModel::updateToRefund($tradeId);
    }

    /**
     * 获取支付方式的配置信息
     * @param mixed $order 订单信息
     * @return mixed
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getPaymentConfig($order)
    {
        $PaymentModel = new PaymentModel;
        $templateInfo = $PaymentModel->getPaymentInfo($order['pay_method'], $order['platform'], $order['store_id']);
        return $templateInfo['template']['config'][$order['pay_method']];
    }
}
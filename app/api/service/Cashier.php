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

namespace app\api\service;

use app\api\service\cashier\Payment;
use app\api\service\cashier\Payment as PaymentService;
use app\common\service\BaseService;
use cores\exception\BaseException;

/**
 * 余额充值服务类 (收银台)
 * Class Cashier
 * @package app\api\controller
 */
class Cashier extends BaseService
{
    // 提示信息
    private string $message = '';

    // 订单ID
    private int $orderId;

    // 支付方式 (微信支付、支付宝)
    private string $method;

    // 下单的客户端
    private string $client;

    /**
     * 设置支付的订单ID
     * @param int $orderId 订单ID
     * @return $this
     */
    public function setOrderId(int $orderId): Cashier
    {
        $this->orderId = $orderId;
        return $this;
    }

    /**
     * 设置当前支付方式
     * @param string $method 支付方式
     * @return $this
     */
    public function setMethod(string $method): Cashier
    {
        $this->method = $method;
        return $this;
    }

    /**
     * 设置下单的客户端
     * @param string $client 客户端
     * @return $this
     */
    public function setClient(string $client): Cashier
    {
        $this->client = $client;
        return $this;
    }

    /**
     * 获取支付订单的信息
     * @param int $orderId 订单ID
     * @param string $client 指定的客户端
     * @return array
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function orderInfo(): array
    {
        $PaymentService = new PaymentService;
        return $PaymentService->setOrderId($this->orderId)
            ->setClient($this->client)
            ->orderInfo();
    }

    /**
     * 确认订单支付事件
     * @param array $extra 附加数据
     * @return array[]
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function orderPay(array $extra = []): array
    {
        $PaymentService = new PaymentService;
        $result = $PaymentService->setOrderId($this->orderId)
            ->setMethod($this->method)
            ->setClient($this->client)
            ->orderPay($extra);
        $this->message = $PaymentService->getMessage();
        return $result;
    }

    /**
     * 交易查询
     * 查询第三方支付订单是否付款成功
     * @param string $outTradeNo 商户订单号
     * @return bool
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function tradeQuery(string $outTradeNo): bool
    {
        $PaymentService = new PaymentService;
        return $PaymentService->setMethod($this->method)->setClient($this->client)->tradeQuery($outTradeNo);
    }

    /**
     * 返回消息提示
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }
}
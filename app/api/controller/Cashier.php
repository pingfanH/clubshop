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

namespace app\api\controller;

use think\response\Json;
use app\api\service\Cashier as CashierService;

/**
 * 订单付款控制器 (收银台)
 * Class Cashier
 * @package app\api\controller
 */
class Cashier extends Controller
{
    /**
     * 获取支付订单的信息
     * @param int $orderId 订单ID
     * @param string $client 指定的客户端
     * @return Json
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function orderInfo(int $orderId, string $client): Json
    {
        $CashierService = new CashierService;
        $data = $CashierService->setOrderId($orderId)->setClient($client)->orderInfo();
        return $this->renderSuccess($data);
    }

    /**
     * 确认订单支付事件
     * @param int $orderId 订单ID
     * @param string $method 支付方式
     * @param string $client 指定的客户端
     * @param array $extra 附加数据
     * @return Json
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function orderPay(int $orderId, string $method, string $client, array $extra = []): Json
    {
        $CashierService = new CashierService;
        $data = $CashierService->setOrderId($orderId)
            ->setMethod($method)
            ->setClient($client)
            ->orderPay($extra);
        return $this->renderSuccess($data, $CashierService->getMessage() ?: '下单成功');
    }

    /**
     * 交易查询
     * @param string $outTradeNo 商户订单号
     * @param string $method 支付方式
     * @param string $client 指定的客户端
     * @return Json
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function tradeQuery(string $outTradeNo, string $method, string $client): Json
    {
        $CashierService = new CashierService;
        $result = $CashierService->setMethod($method)->setClient($client)->tradeQuery($outTradeNo);
        $message = $result ? '恭喜您，订单已付款成功' : ($CashierService->getError() ?: '很抱歉，订单未支付，请重新发起');
        return $this->renderSuccess(['isPay' => $result], $message);
    }
}
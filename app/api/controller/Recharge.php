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
use app\api\service\Recharge as RechargeService;

/**
 * 用户余额充值管理
 * Class Recharge
 * @package app\api\controller
 */
class Recharge extends Controller
{
    /**
     * 充值中心页面数据
     * @param string $client 当前客户端
     * @return Json
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function center(string $client): Json
    {
        $RechargeService = new RechargeService;
        $data = $RechargeService->center($client);
        return $this->renderSuccess($data);
    }

    /**
     * 确认充值订单
     * @return Json
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function submit(): Json
    {
        $data = $this->postForm();
        $RechargeService = new RechargeService;
        $data = $RechargeService->setMethod($data['method'])
            ->setClient($data['client'])
            ->orderPay($data['planId'], $data['customMoney'], $data['extra']);
        return $this->renderSuccess($data, $RechargeService->getMessage() ?: '下单成功');
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
        $RechargeService = new RechargeService;
        $result = $RechargeService->setMethod($method)->setClient($client)->tradeQuery($outTradeNo);
        $message = $result ? '恭喜您，余额充值成功' : ($RechargeService->getError() ?: '很抱歉，订单未支付，请重新发起');
        return $this->renderSuccess(['isPay' => $result], $message);
    }
}
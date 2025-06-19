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

namespace app\timer\controller;

use cores\BaseTimer;
use app\timer\model\Setting as SettingModel;
use app\timer\service\Order as OrderService;

/**
 * 定时任务：商城订单
 * Class Order
 * @package app\timer\controller
 */
class Order extends BaseTimer
{
    // 当前任务唯一标识
    protected string $taskKey = 'Order';

    // 任务执行间隔时长 (单位:秒)
    protected int $taskExpire = 60 * 30;

    /**
     * 任务处理
     * @param array $param
     */
    public function handle(array $param)
    {
        ['storeId' => $this->storeId] = $param;
        $this->setInterval($this->storeId, $this->taskKey, $this->taskExpire, function () {
            echo $this->taskKey . PHP_EOL;
            // 未支付订单自动关闭
            $this->closeEvent();
            // 已发货订单自动确认收货
            $this->receiveEvent();
            // 已完成订单结算
            $this->settledEvent();
        });
    }

    /**
     * 未支付订单自动关闭
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function closeEvent()
    {
        // 自动关闭订单的有效期
        $closeHours = (int)$this->getTradeSetting()['closeHours'];
        // 执行自动关闭
        if ($closeHours > 0) {
            $service = new OrderService;
            $service->closeEvent($this->storeId, $closeHours);
        }
    }

    /**
     * 自动确认收货订单的天数
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function receiveEvent()
    {
        // 取消n天以前的的未付款订单
        $receiveDays = (int)$this->getTradeSetting()['receive_days'];
        // 执行自动确认收货
        if ($receiveDays > 0) {
            $service = new OrderService;
            $service->receiveEvent($this->storeId, $receiveDays);
        }
    }

    /**
     * 已完成订单自动结算
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function settledEvent()
    {
        // 取消n天以前的的未付款订单
        $refundDays = (int)$this->getTradeSetting()['refund_days'];
        // 执行自动确认收货
        if ($refundDays > 0) {
            $service = new OrderService;
            $service->settledEvent($this->storeId, $refundDays);
        }
    }

    /**
     * 获取商城交易设置
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getTradeSetting()
    {
        return SettingModel::getItem('trade', $this->storeId)['order'];
    }
}
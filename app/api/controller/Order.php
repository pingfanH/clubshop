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
use app\api\model\Order as OrderModel;
use app\api\model\Setting as SettingModel;
use app\api\service\User as UserService;
use app\api\service\Order as OrderService;
use cores\exception\BaseException;

/**
 * 我的订单控制器
 * Class Order
 * @package app\api\controller
 */
class Order extends Controller
{
    /**
     * 我的订单列表
     * @param string $dataType 订单类型
     * @return Json
     * @throws BaseException
     * @throws \think\db\exception\DbException
     */
    public function list(string $dataType): Json
    {
        $model = new OrderModel;
        $list = $model->getList($dataType);
        return $this->renderSuccess(compact('list'));
    }

    /**
     * 订单详情信息
     * @param int $orderId 订单ID
     * @return Json
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function detail(int $orderId): Json
    {
        // 订单详情
        $model = OrderModel::getUserOrderDetail($orderId);
        return $this->renderSuccess([
            'order' => $model,  // 订单详情
            'setting' => [
                // 积分名称
                'points_name' => SettingModel::getPointsName(),
            ],
        ]);
    }

    /**
     * 获取物流跟踪信息
     * @param int $orderId 订单ID
     * @return Json
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function express(int $orderId): Json
    {
        // 订单详情 (用于验证当前登录用户)
        $order = OrderModel::getDetail($orderId);
        // 获取物流信息
        $service = new OrderService;
        $express = $service->express($orderId);
        return $this->renderSuccess(compact('express'));
    }

    /**
     * 取消订单
     * @param int $orderId
     * @return Json
     * @throws BaseException
     */
    public function cancel(int $orderId): Json
    {
        $model = OrderModel::getDetail($orderId);
        if ($model->cancel()) {
            return $this->renderSuccess($model->getMessage());
        }
        return $this->renderError($model->getError() ?: '订单取消失败');
    }

    /**
     * 确认收货
     * @param int $orderId
     * @return Json
     * @throws BaseException
     */
    public function receipt(int $orderId): Json
    {
        $model = OrderModel::getDetail($orderId);
        if ($model->receipt()) {
            return $this->renderSuccess('确认收货成功');
        }
        return $this->renderError($model->getError());
    }

    /**
     * 获取当前用户待处理的订单数量
     * @return Json
     * @throws BaseException
     */
    public function todoCounts(): Json
    {
        $model = new OrderModel;
        $counts = $model->getTodoCounts();
        return $this->renderSuccess(compact('counts'));
    }
}

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

namespace app\store\controller\order;

use think\response\Json;
use think\db\exception\DbException;
use app\store\controller\Controller;
use app\store\model\Order as OrderModel;

/**
 * 订单事件控制器
 * Class Event
 * @package app\store\controller\order
 */
class Event extends Controller
{
    /**
     * 修改订单价格
     * @param int $orderId
     * @return Json
     */
    public function updatePrice(int $orderId): Json
    {
        $model = OrderModel::detail($orderId);
        if ($model->updatePrice($this->postForm())) {
            return $this->renderSuccess('操作成功');
        }
        return $this->renderError($model->getError() ?: '操作失败');
    }

    /**
     * 修改商家备注
     * @param int $orderId
     * @return Json
     */
    public function updateRemark(int $orderId): Json
    {
        $model = OrderModel::detail($orderId);
        if ($model->updateRemark($this->postForm())) {
            return $this->renderSuccess('操作成功');
        }
        return $this->renderError($model->getError() ?: '操作失败');
    }

    /**
     * 修改收货地址
     * @param int $orderId
     * @return Json
     */
    public function updateAddress(int $orderId): Json
    {
        $model = OrderModel::detail($orderId);
        if ($model->updateAddress($this->postForm())) {
            return $this->renderSuccess('操作成功');
        }
        return $this->renderError($model->getError() ?: '操作失败');
    }

    /**
     * 小票打印
     * @param int $orderId
     * @return Json
     * @throws DbException
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function printer(int $orderId): Json
    {
        $model = OrderModel::detail($orderId);
        if ($model->printer($this->postForm())) {
            return $this->renderSuccess('操作成功');
        }
        return $this->renderError($model->getError() ?: '操作失败');
    }

    /**
     * 审核：用户取消订单
     * @param $orderId
     * @return Json
     */
    public function confirmCancel($orderId): Json
    {
        $model = OrderModel::detail($orderId);
        if ($model->confirmCancel($this->postForm())) {
            return $this->renderSuccess('操作成功');
        }
        return $this->renderError($model->getError() ?: '操作失败');
    }

    /**
     * 删除订单记录
     * @param int $orderId
     * @return Json
     */
    public function delete(int $orderId): Json
    {
        $model = OrderModel::detail($orderId);
        if ($model->setDelete()) {
            return $this->renderSuccess('删除成功');
        }
        return $this->renderError($model->getError() ?: '操作失败');
    }
}

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
use app\api\model\OrderRefund as OrderRefundModel;

/**
 * 订单售后服务
 * Class service
 * @package app\api\controller\user\order
 */
class Refund extends Controller
{
    /**
     * 售后单列表
     * @param int $state
     * @return Json
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DbException
     */
    public function list(int $state = -1): Json
    {
        $model = new OrderRefundModel;
        $list = $model->getList($state);
        return $this->renderSuccess(compact('list'));
    }

    /**
     * 售后单商品详情
     * @param int $orderGoodsId
     * @return Json
     * @throws \cores\exception\BaseException
     */
    public function goods(int $orderGoodsId): Json
    {
        $model = new OrderRefundModel;
        $goods = $model->getRefundGoods($orderGoodsId);
        return $this->renderSuccess(compact('goods'));
    }

    /**
     * 申请售后
     * @param int $orderGoodsId 订单商品ID
     * @return Json
     * @throws \cores\exception\BaseException
     */
    public function apply(int $orderGoodsId): Json
    {
        // 新增售后单记录
        $model = new OrderRefundModel;
        if ($model->apply($orderGoodsId, $this->postForm())) {
            return $this->renderSuccess([], '提交成功');
        }
        return $this->renderError($model->getError() ?: '提交失败');
    }

    /**
     * 售后单详情
     * @param int $orderRefundId 售后单ID
     * @return Json
     * @throws \cores\exception\BaseException
     */
    public function detail(int $orderRefundId): Json
    {
        $detail = OrderRefundModel::getDetail($orderRefundId, true);
        return $this->renderSuccess(compact('detail'));
    }

    /**
     * 用户发货
     * @param int $orderRefundId 售后单ID
     * @return Json
     * @throws \cores\exception\BaseException
     */
    public function delivery(int $orderRefundId): Json
    {
        // 售后单详情
        $model = OrderRefundModel::getDetail($orderRefundId, false);
        if ($model->delivery($this->postForm())) {
            return $this->renderSuccess([], '操作成功');
        }
        return $this->renderError($model->getError() ?: '提交失败');
    }
}

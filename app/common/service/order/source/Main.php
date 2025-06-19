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

namespace app\common\service\order\source;

/**
 * 订单来源-普通订单
 * Class Main
 * @package app\common\service\order\source
 */
class Main extends Basics
{
    /**
     * 判断订单是否允许付款
     * @param $order
     * @return bool
     */
    public function checkOrderStatusOnPay($order): bool
    {
        // 判断订单状态
        if (!$this->checkOrderStatusOnPayCommon($order)) {
            return false;
        }
        // 判断商品状态、库存
        if (!$this->checkGoodsStatusOnPayCommon($order['goods'])) {
            return false;
        }
        return true;
    }

    /**
     * 判断订单是否允许取消
     * @param $order
     * @return bool
     */
    public function checkOrderByCancel($order): bool
    {
        // 判断订单是否允许取消
        if (!$this->checkOrderByCancelCommon($order)) {
            return false;
        }
        return true;
    }

    /**
     * 判断订单是否允许发货
     * @param $order
     * @return bool
     */
    public function checkOrderByDelivery($order): bool
    {
        // 判断订单是否允许发货
        if (!$this->checkOrderByDeliveryCommon($order)) {
            return false;
        }
        return true;
    }
}
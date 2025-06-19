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

use app\common\service\BaseService;
use app\common\model\GoodsSku as GoodsSkuModel;
use app\common\enum\goods\Status as GoodsStatusEnum;
use app\common\enum\order\PayStatus as PayStatusEnum;
use app\common\enum\order\OrderStatus as OrderStatusEnum;
use app\common\enum\order\PayStatus as OrderPayStatusEnum;
use app\common\enum\order\DeliveryStatus as DeliveryStatusEnum;
use app\common\enum\goods\DeductStockType as DeductStockTypeEnum;

/**
 * 订单来源基类
 * Class Basics
 * @package app\common\service\order\source
 */
abstract class Basics extends BaseService
{
    /**
     * 判断订单是否允许付款
     * @param $order
     * @return mixed
     */
    abstract public function checkOrderStatusOnPay($order);

    /**
     * 判断订单是否允许支付 (公共)
     * @param $order
     * @return bool
     */
    protected function checkOrderStatusOnPayCommon($order): bool
    {
        // 判断订单状态
        if ($order['order_status'] != OrderStatusEnum::NORMAL) {
            $this->error = '当前订单状态不合法，无法支付';
            return false;
        }
        // 判断订单支付状态
        if ($order['pay_status'] == OrderPayStatusEnum::SUCCESS) {
            $this->error = '当前订单已支付，无需重复支付';
            return false;
        }
        return true;
    }

    /**
     * 判断商品状态、库存 (未付款订单)
     * @param $goodsList
     * @param bool $verifyStatus 是否验证商品状态(上架)
     * @return bool
     */
    protected function checkGoodsStatusOnPayCommon($goodsList, bool $verifyStatus = true): bool
    {
        foreach ($goodsList as $goods) {
            // 判断商品是否下架
            if ($verifyStatus && $goods['goods']['status'] == GoodsStatusEnum::OFF_SALE) {
                $this->error = "很抱歉，商品 [{$goods['goods_name']}] 已下架";
                return false;
            }
            // 获取商品的sku信息
            $goodsSku = $this->getOrderGoodsSku($goods['goods_id'], $goods['goods_sku_id']);
            if (empty($goodsSku)) {
                $this->error = "很抱歉，商品 [{$goods['goods_name']}] SKU已不存在，请重新下单";
                return false;
            }
            // 付款减库存
            if ($goods['deduct_stock_type'] == DeductStockTypeEnum::PAYMENT && $goods['total_num'] > $goodsSku['stock_num']) {
                $this->error = "很抱歉，商品 [{$goods['goods_name']}] 库存不足";
                return false;
            }
        }
        return true;
    }

    /**
     * 判断订单是否允许取消 (公共)
     * @param $order
     * @return bool
     */
    protected function checkOrderByCancelCommon($order): bool
    {
        if ($order['delivery_status'] != DeliveryStatusEnum::NOT_DELIVERED) {
            $this->error = '已发货订单不可取消';
            return false;
        }
        return true;
    }

    /**
     * 判断订单是否允许发货 (公共)
     * @param $order
     * @return bool|false
     */
    protected function checkOrderByDeliveryCommon($order): bool
    {
        if ($order['pay_status'] != PayStatusEnum::SUCCESS
            || $order['delivery_status'] == DeliveryStatusEnum::DELIVERED) {
            $this->error = "订单号[{$order['order_no']}]不满足发货条件!";
            return false;
        }
        return true;
    }

    /**
     * 获取指定的商品sku信息
     * @param $goodsId
     * @param $goodsSkuId
     * @return GoodsSkuModel|array|null
     */
    private function getOrderGoodsSku($goodsId, $goodsSkuId)
    {
        return GoodsSkuModel::detail($goodsId, $goodsSkuId);
    }
}
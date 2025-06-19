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

use app\api\model\Order as OrderModel;
use app\api\model\order\Delivery as DeliveryModel;
use app\api\model\OrderAddress as OrderAddressModel;
use app\common\service\Order as OrderService;
use app\common\service\Express as ExpressService;
use app\common\enum\order\OrderStatus as OrderStatusEnum;
use cores\exception\BaseException;

/**
 * 订单服务类
 * Class Order
 * @package app\common\service
 */
class Order extends OrderService
{
    /**
     * 获取物流信息
     * @param int $orderId 订单ID
     * @return mixed
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function express(int $orderId)
    {
        // 获取发货单列表
        $model = new DeliveryModel;
        $list = $model->getList($orderId);
        // 整理物流跟踪信息
        return $this->getExpressTraces($orderId, $list);
    }

    /**
     * 整理物流跟踪信息
     * @param int $orderId 订单ID
     * @param $list
     * @return mixed
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getExpressTraces(int $orderId, $list)
    {
        // 订单收货地址
        $address = OrderAddressModel::detail(['order_id' => $orderId]);
        // 整理物流跟踪信息
        $Express = new ExpressService;
        foreach ($list as $item) {
            if (!empty($item['express'])) {
                $item['traces'] = $Express->traces(
                    $item['express'],
                    $item['express_no'],
                    $address,
                    $this->getStoreId()
                );
            }
        }
        return $list;
    }

    /**
     * 获取某商品的购买件数
     * @param int $userId 用户ID
     * @param int $goodsId 商品ID
     * @param int $orderSource 商品来源
     * @return int
     */
    public static function getGoodsBuyNum(int $userId, int $goodsId, int $orderSource): int
    {
        return (int) (new OrderModel)->setBaseQuery('order', [['order_goods', 'order_id']])
            ->where('order_goods.user_id', '=', $userId)
            ->where('order_goods.goods_id', '=', $goodsId)
            ->where('order.order_source', '=', $orderSource)
            ->where('order.order_status', '<>', OrderStatusEnum::CANCELLED)
            ->where('order.is_delete', '=', 0)
            ->sum('order_goods.total_num');
    }
}
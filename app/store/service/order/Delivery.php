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

namespace app\store\service\order;

use think\facade\Db;
use app\store\model\Order as OrderModel;
use app\store\model\Express as ExpressModel;
use app\store\model\OrderGoods as OrderGoodsModel;
use app\store\model\order\Delivery as DeliveryModel;
use app\store\model\order\DeliveryGoods as DeliveryGoodsModel;
use app\common\service\BaseService;
use app\common\service\Message as MessageService;
use app\common\service\order\Shipping as ShippingService;
use app\common\service\order\source\Factory as OrderSourceFactory;
use cores\exception\BaseException;
use app\common\enum\order\{OrderType as OrderTypeEnum, DeliveryStatus as DeliveryStatusEnum};
use app\common\library\helper;
use app\common\library\FileLocal;
use app\common\library\phpoffice\ReadExecl;

/**
 * 服务层：订单发货事件
 * Class Delivery
 * @package app\store\service\order
 */
class Delivery extends BaseService
{
    // 发货方式: 手动录入
    const DELIVERY_METHOD_MANUAL = 10;

    // 发货方式: 无需物流
    const DELIVERY_METHOD_NONE = 20;

    /**
     * 手动发货
     * @param int $orderId
     * @param array $param
     * @return bool
     */
    public function delivery(int $orderId, array $param): bool
    {
        // 设置默认的参数
        $param = $this->buildParam($param);
        // 获取订单详情
        $detail = OrderModel::detail($orderId);
        // 验证订单是否满足发货条件
        if (!$this->verifyDelivery([$detail])) {
            return false;
        }
        Db::transaction(function () use ($detail, $param, $orderId) {
            // 订单发货事件
            $this->deliveryEvent($detail, $param);
            // 获取已发货的订单
            $completed = OrderModel::detail($orderId, ['goods', 'trade']);
            // 发货信息同步微信平台
            $syncStatus = (new ShippingService)->syncMpWeixinShipping($completed, [
                // 同步至微信小程序《发货信息管理》
                'syncMpWeixinShipping' => $param['syncMpWeixinShipping'],
                // 物流模式：1物流配送 3虚拟商品 4用户自提
                'logisticsType' => [
                    self::DELIVERY_METHOD_MANUAL => ShippingService::DELIVERY_EXPRESS,
                    self::DELIVERY_METHOD_NONE => ShippingService::DELIVERY_VIRTUAL
                ][$param['deliveryMethod']],
                // 物流公司ID
                'expressId' => $param['expressId'],
                // 物流单号
                'expressNo' => $param['expressNo'],
            ]);
            // 记录是否已同步微信小程序发货信息管理
            OrderModel::updateSyncWeixinShipping($orderId, $syncStatus);
            // 发送消息通知 [未实现]
            // $this->sendDeliveryMessage([$completed]);
        });
        return true;
    }

    /**
     * 设置默认的参数
     * @param array $param
     * @return array
     */
    private function buildParam(array $param): array
    {
        return helper::setQueryDefaultValue($param, [
            // 发货方式 (10手动录入 20无需物流)
            'deliveryMethod' => self::DELIVERY_METHOD_MANUAL,
            // 物流公司ID
            'expressId' => 0,
            // 物流单号
            'expressNo' => '',
            // 整单发货
            'isAllPack' => false,
            // 发货的商品
            'packGoodsData' => [],
            // 同步至微信小程序《发货信息管理》
            'syncMpWeixinShipping' => 1,
        ]);
    }

    /**
     * 订单发货事件
     * @param $order
     * @param array $param 发货参数
     * @return bool
     */
    public function deliveryEvent($order, array $param): bool
    {
        // 默认参数
        $param = helper::setQueryDefaultValue($param, [
            // 发货方式 (10手动录入 20无需物流 30电子面单)
            'deliveryMethod' => self::DELIVERY_METHOD_MANUAL,
            // 物流公司ID
            'expressId' => 0,
            // 物流单号
            'expressNo' => '',
            // 为整单发货
            'isAllPack' => false,
            // 发货的商品 (整单发货时无需传入)
            'packGoodsData' => [],
            // 电子面单内容
            'eorderHtml' => ''
        ]);
        // 整单发货时获取所有未发货的商品
        $param['isAllPack'] && $param['packGoodsData'] = $this->getAllPackGoods($order);
        // 实物订单
        if ($order['order_type'] == OrderTypeEnum::PHYSICAL) {
            // 写入发货单
            $this->recordDeliveryOrder($order, $param);
            // 判断订单是否已全部发货
            $deliveredAll = $this->checkDeliveredAll($order['goods'], $param);
            // 更新订单的发货状态
            $deliveryStatus = $deliveredAll ? DeliveryStatusEnum::DELIVERED : DeliveryStatusEnum::PART_DELIVERED;
            $this->updateDeliveryStatus([$order['order_id']], $deliveryStatus);
        }
        return true;
    }

    /**
     * 整单发货时获取订单所有未发货的商品
     * @param $order
     * @return array
     */
    private function getAllPackGoods($order): array
    {
        $data = [];
        foreach ($order['goods'] as $goods) {
            if ($goods['delivery_status'] != DeliveryStatusEnum::DELIVERED) {
                $data[] = [
                    'orderGoodsId' => $goods['order_goods_id'],
                    'deliveryNum' => $goods['total_num'] - $goods['delivery_num'],
                ];
            }
        }
        return $data;
    }

    /**
     * 写入发货单
     * @param $order
     * @param array $param
     */
    private function recordDeliveryOrder($order, array $param)
    {
        // 新增发货单记录
        $DeliveryModel = new DeliveryModel;
        $DeliveryModel->save([
            'order_id' => $order['order_id'],
            'delivery_method' => $param['deliveryMethod'],
            'express_id' => $param['expressId'],
            'express_no' => $param['deliveryMethod'] == self::DELIVERY_METHOD_NONE ? '' : $param['expressNo'],
            'eorder_html' => $param['eorderHtml'] ?? '',
            'store_id' => $this->getStoreId(),
        ]);
        // 新增发货单商品记录
        $this->recordRelivery($order, $param['packGoodsData'], (int)$DeliveryModel['delivery_id']);
        // 更新订单商品记录
        $this->updateOrderGoods($order, $param['packGoodsData']);
    }

    /**
     * 新增发货单商品记录
     * @param $order
     * @param array $packGoodsData
     * @param int $deliveryId
     */
    private function recordRelivery($order, array $packGoodsData, int $deliveryId)
    {
        // 整理发货单记录
        $dataset = [];
        foreach ($packGoodsData as $item) {
            $goods = helper::arraySearch($order['goods'], 'order_goods_id', $item['orderGoodsId']);
            !empty($goods) && $dataset[] = [
                'delivery_id' => $deliveryId,
                'order_id' => $order['order_id'],
                'order_goods_id' => $item['orderGoodsId'],
                'goods_id' => $goods['goods_id'],
                'delivery_num' => $item['deliveryNum'],
                'store_id' => $this->getStoreId(),
            ];
        }
        (new DeliveryGoodsModel)->addAll($dataset);
    }

    /**
     * 更新订单商品记录
     * @param $order
     * @param array $packGoodsData
     */
    private function updateOrderGoods($order, array $packGoodsData)
    {
        // 更新订单商品记录
        $dataset = [];
        foreach ($order['goods'] as $goods) {
            // 发货的订单商品数据
            $item = helper::arraySearch($packGoodsData, 'orderGoodsId', $goods['order_goods_id']);
            if (!$item) {
                continue;
            }
            // 判断订单商品的发货数量是否已完成
            $newDeliveryNum = $item['deliveryNum'] + $goods['delivery_num'];
            // 记录更新内容
            $dataset[] = [
                'where' => ['order_goods_id' => $goods['order_goods_id']],
                'data' => [
                    'delivery_num' => $newDeliveryNum,
                    'delivery_status' => $newDeliveryNum >= $goods['total_num'] ? DeliveryStatusEnum::DELIVERED
                        : DeliveryStatusEnum::PART_DELIVERED
                ]
            ];
        }
        (new OrderGoodsModel)->updateAll($dataset);
    }

    /**
     * 判断订单是否已全部发货
     * @param $orderGoodsList
     * @param array $param
     * @return bool
     */
    private function checkDeliveredAll($orderGoodsList, array $param): bool
    {
        foreach ($orderGoodsList as $goods) {
            // 查询商品是否在表单中
            $packGoods = helper::arraySearch($param['packGoodsData'], 'orderGoodsId', $goods['order_goods_id']);
            if (!empty($packGoods)) {
                // 判断订单商品的发货数量是否满足
                if (($packGoods['deliveryNum'] + $goods['delivery_num']) < $goods['total_num']) {
                    return false;
                }
            } else {
                // 判断订单商品的发货状态
                if ($goods['delivery_status'] != DeliveryStatusEnum::DELIVERED) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * 确认发货后发送消息通知
     * @param $orderList
     * @return void
     */
    private function sendDeliveryMessage($orderList): void
    {
        // 发送消息通知
        foreach ($orderList as $item) {
            MessageService::send('order.delivery', ['order' => $item], $this->getStoreId());
        }
    }

    /**
     * 更新订单发货状态(批量)
     * @param array $orderIds
     * @param int $deliveryStatus 发货状态
     */
    private function updateDeliveryStatus(array $orderIds, int $deliveryStatus): void
    {
        // 整理更新的数据
        $data = [];
        foreach ($orderIds as $orderId) {
            $data[] = [
                'data' => [
                    'delivery_status' => $deliveryStatus,
                    'delivery_time' => time(),
                ],
                'where' => ['order_id' => $orderId]
            ];
        }
        // 批量更新
        (new OrderModel)->updateAll($data);
    }

    /**
     * 验证订单是否满足发货条件
     * @param $orderList
     * @return bool
     */
    public function verifyDelivery($orderList): bool
    {
        foreach ($orderList as $order) {
            $orderSource = OrderSourceFactory::getFactory($order['order_source']);
            if (!$orderSource->checkOrderByDelivery($order)) {
                $this->error = $orderSource->getError();
                return false;
            }
        }
        return true;
    }
}
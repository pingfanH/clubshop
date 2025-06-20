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

namespace app\store\model\order;

use app\store\model\OrderAddress as OrderAddressModel;
use app\common\model\order\Delivery as DeliveryModel;
use app\common\service\Express as ExpressService;

/**
 * 订单发货单模型
 * Class Delivery
 * @package app\store\model\order
 */
class Delivery extends DeliveryModel
{
    /**
     * 获取发货记录
     * @param array $param
     * @return \think\Paginator
     */
    public function getList(array $param): \think\Paginator
    {
        // 检索查询条件
        $filter = $this->getQueryFilter($param);
        return $this
            ->with(['orderData' => ['user', 'address'], 'goods.goods.image', 'express'])
            ->alias('delivery')
            ->field($this->getAliasFields('delivery', ['eorder_html']))
            ->join('order', 'order.order_id = delivery.order_id')
            ->where($filter)
            ->order(['delivery.create_time' => 'desc', 'delivery.' . $this->getPk() => 'asc'])
            ->paginate(10);
    }

    /**
     * 设置检索查询条件
     * @param array $param
     * @return array
     */
    private function getQueryFilter(array $param): array
    {
        // 默认参数
        $params = $this->setQueryDefaultValue($param, [
            'searchType' => '',     // 关键词类型 (10订单号)
            'searchValue' => '',    // 关键词内容
            'betweenTime' => [],    // 起止时间
        ]);
        // 检索查询条件
        $filter = [];
        // 关键词
        if (!empty($params['searchValue'])) {
            $searchWhere = [
                10 => ['order.order_no', 'like', "%{$params['searchValue']}%"]
            ];
            array_key_exists($params['searchType'], $searchWhere) && $filter[] = $searchWhere[$params['searchType']];
        }
        // 起止时间
        if (!empty($params['betweenTime'])) {
            $times = between_time($params['betweenTime']);
            $filter[] = ['delivery.create_time', '>=', $times['start_time']];
            $filter[] = ['delivery.create_time', '<', $times['end_time'] + 86400];
        }
        return $filter;
    }

    /**
     * 查询指定发货单的物流跟踪信息
     * @param int $deliveryId
     * @return array
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getExpressTraces(int $deliveryId): array
    {
        // 发货单详情
        $detail = self::detail($deliveryId, ['express']);
        empty($detail) && throwError('未找到指定的发货单');
        // 订单收货地址
        $address = OrderAddressModel::detail(['order_id' => $detail['order_id']]);
        // 整理物流跟踪信息
        $Express = new ExpressService;
        return empty($detail['express']) ? []
            : $Express->traces($detail['express'], $detail['express_no'], $address, self::$storeId);
    }
}
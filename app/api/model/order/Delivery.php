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

namespace app\api\model\order;

use app\common\model\order\Delivery as DeliveryModel;

/**
 * 订单发货单模型
 * Class Delivery
 * @package app\api\model\order
 */
class Delivery extends DeliveryModel
{
    // 隐藏的字段
    protected $hidden = [
        'eorder_html',
        'store_id',
        'create_time',
    ];

    /**
     * 获取指定订单的发货单记录
     * @param int $orderId
     * @return Delivery[]|array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(int $orderId)
    {
        return $this->with(['express', 'goods.goods.image'])
            ->where('order_id', '=', $orderId)
            ->select();
    }
}
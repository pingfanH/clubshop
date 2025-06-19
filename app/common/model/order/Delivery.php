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

namespace app\common\model\order;

use cores\BaseModel;
use think\model\relation\BelongsTo;
use think\model\relation\HasMany;

/**
 * 订单发货单模型
 * Class Delivery
 * @package app\common\model\order
 */
class Delivery extends BaseModel
{
    // 定义表名
    protected $name = 'order_delivery';

    // 定义主键
    protected $pk = 'delivery_id';

    protected $updateTime = false;

    /**
     * 关联订单记录
     * @return BelongsTo
     */
    public function orderData(): BelongsTo
    {
        $module = self::getCalledModule();
        return $this->belongsTo("app\\{$module}\\model\\Order", 'order_id');
    }

    /**
     * 关联发货单商品
     * @return hasMany
     */
    public function goods(): HasMany
    {
        $module = self::getCalledModule();
        return $this->hasMany("app\\{$module}\\model\\order\\DeliveryGoods", 'delivery_id');
    }

    /**
     * 关联物流公司记录
     * @return BelongsTo
     */
    public function express(): BelongsTo
    {
        $module = self::getCalledModule();
        return $this->belongsTo("app\\{$module}\\model\\Express", 'express_id');
    }

    /**
     * 发货单记录详情
     * @param int $deliveryId 发货单ID
     * @param array $with 关联查询
     * @return static|array|null
     */
    public static function detail(int $deliveryId, array $with = [])
    {
        return self::get($deliveryId, $with);
    }
}
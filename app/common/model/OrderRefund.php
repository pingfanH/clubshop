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

namespace app\common\model;

use cores\BaseModel;
use think\model\relation\BelongsTo;
use think\model\relation\HasMany;
use think\model\relation\HasOne;

/**
 * 售后单模型
 * Class OrderRefund
 * @package app\common\model\wxapp
 */
class OrderRefund extends BaseModel
{
    // 定义表名
    protected $name = 'order_refund';

    // 定义主键
    protected $pk = 'order_refund_id';

    /**
     * 关联用户表
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo('User');
    }

    /**
     * 关联订单主表
     * @return BelongsTo
     */
    public function orderData(): BelongsTo
    {
        return $this->belongsTo('Order');
    }

    /**
     * 关联订单商品表
     * @return BelongsTo
     */
    public function orderGoods(): BelongsTo
    {
        return $this->belongsTo('OrderGoods')->withoutField(['content']);
    }

    /**
     * 关联图片记录表
     * @return HasMany
     */
    public function images(): HasMany
    {
        return $this->hasMany('OrderRefundImage');
    }

    /**
     * 关联物流公司表
     * @return BelongsTo
     */
    public function express(): BelongsTo
    {
        return $this->belongsTo('Express');
    }

    /**
     * 关联用户表
     * @return HasOne
     */
    public function address(): HasOne
    {
        return $this->hasOne('OrderRefundAddress');
    }

    /**
     * 获取器：用户发货时间
     * @param $value
     * @return false|string
     */
    public function getSendTimeAttr($value)
    {
        return \format_time($value);
    }

    /**
     * 售后单详情
     * @param $where
     * @param array $with
     * @return static|array|null
     */
    public static function detail($where, array $with = [])
    {
        return static::get($where, $with);
    }
}
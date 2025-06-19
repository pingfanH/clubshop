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

namespace app\common\model\recharge;

use cores\BaseModel;
use think\model\relation\BelongsTo;
use think\model\relation\HasOne;

/**
 * 用户充值订单模型
 * Class Order
 * @package app\common\model\recharge
 */
class Order extends BaseModel
{
    // 定义表名
    protected $name = 'recharge_order';

    // 定义主键
    protected $pk = 'order_id';

    /**
     * 关联会员记录表
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        $module = self::getCalledModule();
        return $this->belongsTo("app\\{$module}\\model\\User");
    }

    /**
     * 关联订单套餐快照表
     * @return HasOne
     */
    public function orderPlan(): HasOne
    {
        return $this->hasOne('OrderPlan', 'order_id');
    }

    /**
     * 获取器：付款时间
     * @param $value
     * @return false|string
     */
    public function getPayTimeAttr($value)
    {
        return \format_time($value);
    }

    /**
     * 获取订单详情
     * @param $where
     * @param array $with
     * @return static|array|null
     */
    public static function detail($where, array $with = [])
    {
        return static::get($where, $with);
    }
}

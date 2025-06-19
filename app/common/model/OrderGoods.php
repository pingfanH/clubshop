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

use app\common\library\helper;
use think\model\relation\BelongsTo;
use think\model\relation\HasOne;

/**
 * 订单商品模型
 * Class OrderGoods
 * @package app\common\model
 */
class OrderGoods extends BaseModel
{
    // 定义表名
    protected $name = 'order_goods';

    // 定义主键
    protected $pk = 'order_goods_id';

    protected $updateTime = false;

    /**
     * 订单商品图片
     * @return BelongsTo
     */
    public function image(): BelongsTo
    {
        $model = "app\\common\\model\\UploadFile";
        return $this->belongsTo($model, 'image_id', 'file_id')
            ->bind(['goods_image' => 'preview_url']);
    }

    /**
     * 关联商品表
     * @return BelongsTo
     */
    public function goods(): BelongsTo
    {
        return $this->belongsTo('Goods')->withoutField('content');
    }

    /**
     * 关联订单主表
     * @return BelongsTo
     */
    public function orderM(): BelongsTo
    {
        return $this->belongsTo('Order');
    }

    /**
     * 售后单记录表
     * @return HasOne
     */
    public function refund(): HasOne
    {
        return $this->hasOne('OrderRefund');
    }

    /**
     * 获取器：规格属性
     * @param $value
     * @return array|mixed
     */
    public function getGoodsPropsAttr($value)
    {
        return helper::jsonDecode($value);
    }

    /**
     * 设置器：规格属性
     * @param $value
     * @return string
     */
    public function setGoodsPropsAttr($value): string
    {
        return $value ? helper::jsonEncode($value) : '';
    }

    /**
     * 订单商品详情
     * @param $where
     * @return static|array|null
     */
    public static function detail($where)
    {
        return static::get($where, ['image', 'refund']);
    }
}

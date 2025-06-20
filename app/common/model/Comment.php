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

/**
 * 商品评价模型
 * Class Comment
 * @package app\common\model
 */
class Comment extends BaseModel
{
    // 定义表名
    protected $name = 'comment';

    // 定义主键
    protected $pk = 'comment_id';

    /**
     * 所属订单
     * @return BelongsTo
     */
    public function orderData(): BelongsTo
    {
        return $this->belongsTo('Order');
    }

    /**
     * 订单商品
     * @return BelongsTo
     */
    public function orderGoods(): BelongsTo
    {
        return $this->belongsTo('OrderGoods')
            ->field(['order_goods_id', 'goods_id', 'goods_name', 'image_id', 'goods_props', 'order_id']);
    }

    /**
     * 关联用户表
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo('User')->field(['user_id', 'nick_name', 'avatar_id']);
    }

    /**
     * 关联评价图片表
     * @return HasMany
     */
    public function images(): HasMany
    {
        return $this->hasMany('CommentImage')->order(['id']);
    }

    /**
     * 详情记录
     * @param int $commentId
     * @param array $with
     * @return static|array|null
     */
    public static function detail(int $commentId, array $with = [])
    {
        return static::get($commentId, $with);
    }

    /**
     * 添加评论图片
     * @param array $images
     * @return bool|false
     */
    protected function addCommentImages(array $images): bool
    {
        $data = \array_map(fn($imageId) => [
            'image_id' => $imageId,
            'store_id' => self::$storeId
        ], $images);
        return $this->image()->saveAll($data) !== false;
    }
}

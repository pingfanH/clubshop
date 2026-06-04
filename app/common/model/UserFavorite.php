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

/**
 * 模型类：商品收藏
 * Class UserFavorite
 * @package app\common\model
 */
class UserFavorite extends BaseModel
{
    // 定义表名
    protected $name = 'user_favorite';

    // 定义主键
    protected $pk = 'id';

    /**
     * 检查是否已收藏
     * @param int $userId 用户ID
     * @param int $goodsId 商品ID
     * @param int $storeId 商城ID
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function isFavorite(int $userId, int $goodsId, int $storeId): bool
    {
        return (new static)->where('user_id', '=', $userId)
            ->where('goods_id', '=', $goodsId)
            ->where('store_id', '=', $storeId)
            ->find() ? true : false;
    }

    /**
     * 获取收藏详情
     * @param int $userId 用户ID
     * @param int $goodsId 商品ID
     * @param int $storeId 商城ID
     * @return static|array|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function detail(int $userId, int $goodsId, int $storeId)
    {
        return (new static)->where('user_id', '=', $userId)
            ->where('goods_id', '=', $goodsId)
            ->where('store_id', '=', $storeId)
            ->find();
    }
}

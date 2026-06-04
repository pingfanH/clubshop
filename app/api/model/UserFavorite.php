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

namespace app\api\model;

use app\api\model\Goods as GoodsModel;
use app\api\service\User as UserService;
use app\common\model\UserFavorite as UserFavoriteModel;
use app\common\enum\goods\Status as GoodsStatusEnum;
use cores\exception\BaseException;

/**
 * 商品收藏管理
 * Class UserFavorite
 * @package app\api\model
 */
class UserFavorite extends UserFavoriteModel
{
    /**
     * 隐藏字段
     * @var array
     */
    protected $hidden = [
        'store_id',
        'update_time'
    ];

    /**
     * 添加收藏
     * @param int $goodsId 商品ID
     * @return bool
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function add(int $goodsId): bool
    {
        // 验证商品状态
        $this->checkGoodsStatus($goodsId);
        // 获取当前用户ID
        $userId = UserService::getCurrentLoginUserId();
        // 检查是否已收藏
        if (static::isFavorite($userId, $goodsId, self::$storeId)) {
            throwError('很抱歉，该商品已收藏');
        }
        // 添加收藏
        return $this->save([
            'goods_id' => $goodsId,
            'user_id' => $userId,
            'store_id' => self::$storeId,
            'create_time' => time(),
        ]);
    }

    /**
     * 取消收藏
     * @param int $goodsId 商品ID
     * @return bool
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function remove(int $goodsId): bool
    {
        // 获取当前用户ID
        $userId = UserService::getCurrentLoginUserId();
        // 获取收藏记录
        $detail = static::detail($userId, $goodsId, self::$storeId);
        if (empty($detail)) {
            throwError('很抱歉，未找到收藏记录');
        }
        // 删除收藏
        return $detail->delete() !== false;
    }

    /**
     * 获取收藏列表
     * @param int $page 当前页码
     * @param int $listRows 每页数量
     * @return array
     * @throws BaseException
     */
    public function getList(int $page = 1, int $listRows = 10): array
    {
        // 获取当前用户ID
        $userId = UserService::getCurrentLoginUserId();
        // 查询收藏列表
        $list = $this->where('user_id', '=', $userId)
            ->where('store_id', '=', self::$storeId)
            ->order(['create_time' => 'desc'])
            ->paginate($listRows);
        // 获取商品信息
        $goodsIds = array_column($list->items(), 'goods_id');
        $goodsList = [];
        if (!empty($goodsIds)) {
            $goodsCollection = GoodsModel::with(['images.file'])
                ->where('goods_id', 'in', $goodsIds)
                ->where('is_delete', '=', 0)
                ->select();
            // 转换为以 goods_id 为键的数组
            foreach ($goodsCollection as $goods) {
                $goodsList[$goods['goods_id']] = $goods;
            }
        }
        // 组装数据
        $result = [];
        foreach ($list->items() as $item) {
            if (isset($goodsList[$item['goods_id']])) {
                $goods = $goodsList[$item['goods_id']];
                // 获取商品主图
                $goodsImage = '';
                if (!empty($goods['images'])) {
                    $firstImage = $goods['images']->first();
                    if ($firstImage && $firstImage['file']) {
                        $goodsImage = $firstImage['file']['preview_url'];
                    }
                }
                $result[] = [
                    'id' => $item['id'],
                    'goods_id' => $item['goods_id'],
                    'goods_name' => $goods['goods_name'],
                    'goods_image' => $goodsImage,
                    'goods_price' => $goods['goods_price_min'],
                    'create_time' => $item['create_time'],
                ];
            }
        }
        return [
            'list' => $result,
            'total' => $list->total(),
            'page' => $page,
            'list_rows' => $listRows,
        ];
    }

    /**
     * 检查商品状态
     * @param int $goodsId 商品ID
     * @return void
     * @throws BaseException
     */
    private function checkGoodsStatus(int $goodsId): void
    {
        // 获取商品详情
        $goods = GoodsModel::detail($goodsId);
        // 商品不存在
        if (empty($goods) || $goods['is_delete']) {
            throwError('很抱歉，商品信息不存在');
        }
        // 商品已下架
        if ($goods['status'] == GoodsStatusEnum::OFF_SALE) {
            throwError('很抱歉，该商品已经下架');
        }
    }

    /**
     * 检查是否已收藏
     * @param int $goodsId 商品ID
     * @return bool
     * @throws BaseException
     */
    public function checkIsFavorite(int $goodsId): bool
    {
        // 获取当前用户ID
        $userId = UserService::getCurrentLoginUserId();
        return static::isFavorite($userId, $goodsId, self::$storeId);
    }
}

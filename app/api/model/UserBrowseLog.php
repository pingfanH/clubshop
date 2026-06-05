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
use app\common\model\UserBrowseLog as UserBrowseLogModel;
use cores\exception\BaseException;

/**
 * 浏览历史管理
 * Class UserBrowseLog
 * @package app\api\model
 */
class UserBrowseLog extends UserBrowseLogModel
{
    // 浏览历史不随店铺切换
    protected bool $isGlobalScopeStoreId = false;

    /**
     * 隐藏字段 UserBrowseLogModel
{
    /**
     * 隐藏字段
     * @var array
     */
    protected $hidden = [
        'store_id',
    ];

    /**
     * 记录浏览历史
     * @param int $goodsId 商品ID
     * @return bool
     * @throws BaseException
     */
    public function add(int $goodsId): bool
    {
        // 获取当前用户ID
        $userId = UserService::getCurrentLoginUserId();
        // 检查是否已存在（同一用户同一商品同一天只记录一次）
        $today = strtotime('today');
        $exist = $this->where('user_id', '=', $userId)
            ->where('goods_id', '=', $goodsId)
            ->where('store_id', '=', self::$storeId)
            ->where('create_time', '>=', $today)
            ->find();
        if ($exist) {
            // 更新时间
            return $exist->save(['create_time' => time()]) !== false;
        }
        // 添加浏览记录
        return $this->save([
            'goods_id' => $goodsId,
            'user_id' => $userId,
            'store_id' => self::$storeId,
            'create_time' => time(),
        ]);
    }

    /**
     * 获取浏览历史列表
     * @param int $page 当前页码
     * @param int $listRows 每页数量
     * @return array
     * @throws BaseException
     */
    public function getList(int $page = 1, int $listRows = 20): array
    {
        // 获取当前用户ID
        $userId = UserService::getCurrentLoginUserId();
        // 查询浏览历史列表
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
     * 清除浏览历史
     * @param array $ids 记录ID集，为空清除所有
     * @return bool
     * @throws BaseException
     */
    public function clear(array $ids = []): bool
    {
        // 获取当前用户ID
        $userId = UserService::getCurrentLoginUserId();
        // 设置删除条件
        $where = [['user_id', '=', $userId], ['store_id', '=', self::$storeId]];
        if (!empty($ids)) {
            $where[] = ['id', 'in', $ids];
        }
        // 删除记录
        return $this->where($where)->delete() !== false;
    }
}

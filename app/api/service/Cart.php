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

namespace app\api\service;

use app\api\model\Cart as CartModel;
use app\api\model\Goods as GoodsModel;
use app\api\service\User as UserService;
use app\common\library\helper;
use app\common\service\BaseService;
use cores\exception\BaseException;

/**
 * 服务类: 购物车
 * Class Cart
 * @package app\api\service
 */
class Cart extends BaseService
{
    /**
     * 购物车商品列表(用于购物车页面)
     * @param array $cartIds 购物车记录ID集
     * @param bool $enableGradeMoney 是否设置商品会员价
     * @return array|\think\Collection
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws BaseException
     */
    public function getList(array $cartIds = [], bool $enableGradeMoney = true)
    {
        // 购物车列表
        $cartList = $this->getCartList($cartIds);
        // 整理商品ID集
        $goodsIds = helper::getArrayColumn($cartList, 'goods_id');
        if (empty($goodsIds)) return [];
        // 获取商品列表
        $goodsList = $this->getGoodsListByIds($goodsIds, $enableGradeMoney);
        // 整理购物车商品列表
        foreach ($cartList as $cartIdx => $item) {
            // 查找商品, 商品不存在则删除该购物车记录
            $result = $this->findGoods($goodsList, $item, $enableGradeMoney);
            if ($result !== false) {
                $cartList[$cartIdx]['goods'] = $result;
            } else {
                $this->clear([$item['id']]);
                unset($cartList[$cartIdx]);
            }
        }
        return $cartList;
    }

    /**
     * 获取购物车商品列表(用于订单结算台)
     * @param array $cartIds 购物车记录ID集
     * @return array
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws BaseException
     */
    public function getOrderGoodsList(array $cartIds = []): array
    {
        // 购物车列表
        $cartList = $this->getList($cartIds, false);
        // 订单商品列表
        $goodsList = [];
        foreach ($cartList as $item) {
            // 商品记录
            $goods = $item['goods'];
            // 商品单价
            $goods['goods_price'] = $goods['skuInfo']['goods_price'];
            // 商品购买数量
            $goods['total_num'] = $item['goods_num'];
            // 商品SKU索引
            $goods['goods_sku_id'] = $item['goods_sku_id'];
            // 商品购买总金额
            $goods['total_price'] = helper::bcmul($goods['goods_price'], $item['goods_num']);
            $goodsList[] = $goods;
        }
        return $goodsList;
    }

    /**
     * 检索查询商品
     * @param mixed $goodsList 商品列表
     * @param CartModel $item 购物车记录
     * @param bool $enableGradeMoney 是否设置商品会员价
     * @return false|mixed
     * @throws BaseException
     */
    private function findGoods($goodsList, CartModel $item, bool $enableGradeMoney = true)
    {
        // 查找商品记录
        $goodsInfo = helper::getArrayItemByColumn($goodsList, 'goods_id', $item['goods_id']);
        if (empty($goodsInfo)) {
            return false;
        }
        // 获取当前选择的商品SKU信息
        $goodsInfo['skuInfo'] = GoodsModel::getSkuInfo($goodsInfo, $item['goods_sku_id'], $enableGradeMoney);
        if (empty($goodsInfo['skuInfo'])) {
            return false;
        }
        // 商品封面 (优先sku封面)
        $goodsInfo['goods_image'] = $goodsInfo['skuInfo']['goods_image'] ?: $goodsInfo['goods_image'];
        // 这里需要用到clone, 因对象是引用传递 后面的值会覆盖前面的
        return clone $goodsInfo;
    }

    /**
     * 删除购物车中指定记录
     * @param array $cartIds
     * @return bool
     * @throws BaseException
     */
    public function clear(array $cartIds = []): bool
    {
        $model = new CartModel;
        return $model->clear($cartIds);
    }

    /**
     * 根据商品ID集获取商品列表
     * @param array $goodsIds
     * @param bool $enableGradeMoney 是否设置会员折扣价
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getGoodsListByIds(array $goodsIds, bool $enableGradeMoney = true)
    {
        return (new GoodsModel)->setEnableGradeMoney($enableGradeMoney)
            ->getListByIdsFromApi($goodsIds)
            ->hidden(GoodsModel::getHidden(['goods_images']));
    }

    /**
     * 获取当前用户的购物车记录
     * @param array $cartIds 购物车记录ID集
     * @return \think\Collection
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getCartList(array $cartIds = []): \think\Collection
    {
        // 当前用户ID
        $userId = UserService::getCurrentLoginUserId();
        // 购物车记录模型
        $model = new CartModel;
        // 检索查询条件
        $filter = [];
        if (!empty($cartIds)) {
            $filter[] = ['id', 'in', $cartIds];
        }
        // 查询列表记录
        return $model->where($filter)
            ->where('user_id', '=', $userId)
            ->where('is_delete', '=', 0)
            ->select();
    }
}
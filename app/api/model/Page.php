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

use app\common\model\Page as PageModel;
use app\api\model\Goods as GoodsModel;
use app\api\model\Coupon as CouponModel;
use app\common\library\helper;

/**
 * 页面模型
 * Class Page
 * @package app\api\model
 */
class Page extends PageModel
{
    /**
     * 隐藏字段
     * @var array
     */
    protected $hidden = [
        'store_id',
        'create_time',
        'update_time'
    ];

    /**
     * DIY页面详情
     * @param int|null $pageId 页面ID
     * @return mixed
     * @throws \cores\exception\BaseException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPageData(int $pageId = null)
    {
        // 页面详情
        $detail = $pageId > 0 ? parent::detail($pageId) : parent::getHomePage();
        empty($detail) && throwError('很抱歉，未找到该页面');
        // 页面diy元素
        $pageData = $detail['page_data'];
        // 获取动态数据
        foreach ($pageData['items'] as &$item) {
            // 移出无效的数据
            $item = $this->removeInvalidData($item);
            if ($item['type'] === 'window') {
                $item['data'] = array_values($item['data']);
            } else if ($item['type'] === 'goods') {
                $item['data'] = $this->getGoodsList($item);
            } else if ($item['type'] === 'coupon') {
                $item['data'] = $this->getCouponList($item);
            } else if ($item['type'] === 'article') {
                $item['data'] = $this->getArticleList($item);
            } else if ($item['type'] === 'special') {
                $item['data'] = $this->getSpecialList($item);
            }
        }
        return $pageData;
    }

    /**
     * 移出无效的数据(默认的或demo)
     * @param array $item
     * @return array
     */
    private function removeInvalidData(array $item): array
    {
        if (array_key_exists('defaultData', $item)) {
            unset($item['defaultData']);
        }
        if (array_key_exists('demo', $item)) {
            unset($item['demo']);
        }
        return $item;
    }

    /**
     * 商品组件：获取商品列表
     * @param $item
     * @return array
     * @throws \think\db\exception\DbException
     */
    private function getGoodsList($item): array
    {
        // 获取商品数据
        $model = new GoodsModel;
        if ($item['params']['source'] === 'choice') {
            // 数据来源：手动
            $goodsIds = helper::getArrayColumn($item['data'], 'goods_id');
            if (empty($goodsIds)) return [];
            $goodsList = $model->getListByIdsFromApi($goodsIds);
        } else {
            // 数据来源：自动
            $goodsList = $model->getList([
                'status' => 10,
                'categoryId' => $item['params']['auto']['category'],
                'sortType' => $item['params']['auto']['goodsSort'],
            ], $item['params']['auto']['showNum']);
        }
        if (empty($goodsList) && $goodsList->isEmpty()) {
            return [];
        }
        // 格式化商品列表
        $data = [];
        foreach ($goodsList as $goods) {
            $data[] = [
                'goods_id' => $goods['goods_id'],
                'goods_name' => $goods['goods_name'],
                'spec_type' => $goods['spec_type'],
                'selling_point' => $goods['selling_point'],
                'goods_image' => $goods['goods_image'],
                'goods_price_min' => $goods['goods_price_min'],
                'goods_price_max' => $goods['goods_price_max'],
                'line_price_min' => $goods['line_price_min'],
                'line_price_max' => $goods['line_price_max'],
                'goods_sales' => $goods['goods_sales'],
            ];
        }
        return $data;
    }

    /**
     * 优惠券组件：获取优惠券列表
     * @param $item
     * @return \think\Collection
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getCouponList($item): \think\Collection
    {
        // 获取优惠券数据
        $couponIds = helper::getArrayColumn($item['data'], 'coupon_id');
        return (new CouponModel)->getList($item['params']['showNum'], true, null, $couponIds);
    }

    /**
     * 文章组件：获取文章列表
     * @param $item
     * @return array
     * @throws \think\db\exception\DbException
     */
    private function getArticleList($item): array
    {
        // 获取文章数据
        $model = new Article;
        $articleList = $model->getList($item['params']['auto']['category'], $item['params']['auto']['showNum']);
        return $articleList->isEmpty() ? [] : $articleList->toArray()['data'];
    }

    /**
     * 头条快报：获取头条列表
     * @param $item
     * @return array
     * @throws \think\db\exception\DbException
     */
    private function getSpecialList($item): array
    {
        // 获取头条数据
        $model = new Article;
        $articleList = $model->getList($item['params']['auto']['category'], $item['params']['auto']['showNum']);
        return $articleList->isEmpty() ? [] : $articleList->toArray()['data'];
    }
}

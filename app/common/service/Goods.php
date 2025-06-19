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

namespace app\common\service;

use app\common\library\helper;
use app\common\model\Goods as GoodsModel;
use app\common\model\GoodsSku as GoodsSkuModel;

/**
 * 商品服务类
 * Class Goods
 * @package app\store\service
 */
class Goods extends BaseService
{
    /**
     * 设置商品数据
     * @param mixed $data 源数据
     * @param bool $isMultiple 是否为列表数据
     * @param array $hidden 隐藏的属性
     * @param string $goodsIndex 商品ID字段名称
     * @return mixed
     */
    public static function setGoodsData($data, bool $isMultiple = true, array $hidden = [], string $goodsIndex = 'goods_id')
    {
        // 数据来源
        $dataSource = !$isMultiple ? [$data] : $data;
        // 获取商品列表
        $model = new GoodsModel;
        $goodsData = $model->getListByIds(helper::getArrayColumn($dataSource, $goodsIndex));
        // 设置隐藏的属性
        $goodsData->hidden(\array_merge(['images'], $hidden));
        // 整理列表数据
        $goodsList = helper::arrayColumn2Key($goodsData, 'goods_id');
        foreach ($dataSource as &$item) {
            $item['goods'] = $goodsList[$item[$goodsIndex]] ?? null;
        }
        return $dataSource;
    }

    /**
     * 获取商品的指定的某个SKU信息
     * @param int $goodsId
     * @param string $goodsSkuId
     * @return GoodsSkuModel|array|null
     */
    public static function getSkuInfo(int $goodsId, string $goodsSkuId)
    {
        $detail = GoodsSkuModel::detail($goodsId, $goodsSkuId);
        if (!empty($detail['image'])) {
            $detail['goods_image'] = $detail['image']['preview_url'];
        }
        return $detail->hidden(['image']);
    }

    /**
     * SKU的规格属性转换为可读的字符串
     * @param array $goodsPops
     * @return string
     */
    public static function goodsPropsToAttr(array $goodsPops): string
    {
        $goodsAttr = '';
        foreach ($goodsPops as $pop) {
            $goodsAttr .= "{$pop['group']['name']}: {$pop['value']['name']}; ";
        }
        return $goodsAttr;
    }
}
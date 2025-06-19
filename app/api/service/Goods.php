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

use app\api\model\GoodsSku as GoodsSkuModel;
use app\common\service\Goods as GoodsService;

/**
 * 商品服务类
 * Class Goods
 * @package app\api\service
 */
class Goods extends GoodsService
{
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
        return $detail;
    }
}
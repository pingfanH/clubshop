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

namespace app\api\service\order\source\checkout;

use app\api\service\Order as OrderService;
use app\common\enum\goods\Status as GoodsStatusEnum;
use app\common\enum\order\OrderSource as OrderSourceEnum;

/**
 * 订单结算台-普通商品扩展类
 * Class Main
 * @package app\api\service\order\source\checkout
 */
class Main extends Basics
{
    /**
     * 验证商品列表
     * @return bool
     */
    public function validateGoodsList(): bool
    {
        // 验证商品是否下架
        if (!$this->validateGoodsStatus()) {
            return false;
        }
        // 判断商品库存
        if (!$this->validateGoodsStock()) {
            return false;
        }
        // 验证商品限购
        if (!$this->validateRestrict(OrderSourceEnum::MAIN)) {
            return false;
        }
        return true;
    }

    /**
     * 判断商品是否下架
     * @return bool
     */
    private function validateGoodsStatus(): bool
    {
        foreach ($this->goodsList as $goods) {
            if ($goods['is_delete'] || $goods['status'] == GoodsStatusEnum::OFF_SALE) {
                $this->error = "很抱歉，商品 [{$goods['goods_name']}] 已下架";
                return false;
            }
        }
        return true;
    }

    /**
     * 判断商品库存数量
     * @return bool
     */
    private function validateGoodsStock(): bool
    {
        foreach ($this->goodsList as $goods) {
            if ($goods['total_num'] > $goods['skuInfo']['stock_num']) {
                $this->error = "很抱歉，商品 [{$goods['goods_name']}] 库存不足";
                return false;
            }
        }
        return true;
    }
}
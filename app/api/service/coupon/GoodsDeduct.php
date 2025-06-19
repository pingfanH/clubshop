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

namespace app\api\service\coupon;

use app\common\enum\coupon\ApplyRange as ApplyRangeEnum;
use app\common\enum\coupon\CouponType as CouponTypeEnum;
use app\common\service\BaseService;
use app\common\library\helper;
use cores\exception\BaseException;

/**
 * 订单优惠券折扣服务类
 * Class GoodsDeduct
 * @package app\api\service\coupon
 */
class GoodsDeduct extends BaseService
{
    // 实际抵扣金额
    private int $actualReducedMoney;

    // 订单商品列表
    private iterable $goodsList = [];

    // 优惠券信息
    private array $couponInfo = [];

    // 实际参与优惠券折扣的商品记录
    private array $rangeGoodsList = [];

    // 设置订单商品列表
    public function setGoodsList(iterable $goodsList): GoodsDeduct
    {
        $this->goodsList = $goodsList;
        return $this;
    }

    // 设置优惠券信息
    public function setCouponInfo(array $couponInfo): GoodsDeduct
    {
        $this->couponInfo = $couponInfo;
        return $this;
    }

    /**
     * 计算优惠券抵扣金额
     * @return GoodsDeduct
     * @throws BaseException
     */
    public function setGoodsCouponMoney(): GoodsDeduct
    {
        // 验证当前类属性
        $this->checkAttribute();
        // 设置实际参与优惠券折扣的商品记录
        $this->setRangeGoodsList();
        // 计算实际抵扣的金额
        $this->setActualReducedMoney();
        // 实际抵扣金额为0
        if ($this->actualReducedMoney > 0) {
            // 计算商品的价格权重
            $this->setGoodsListWeight();
            // 计算商品优惠券抵扣金额
            $this->setGoodsListCouponMoney();
            // 总抵扣金额 (已分配的)
            $assignedCouponMoney = (int)helper::getArrayColumnSum($this->rangeGoodsList, 'coupon_money');
            // 分配剩余的抵扣金额
            $this->setGoodsListCouponMoneyFill($assignedCouponMoney);
            $this->setGoodsListCouponMoneyDiff($assignedCouponMoney);
        }
        return $this;
    }

    // 获取实际参与优惠券折扣的商品记录
    public function getRangeGoodsList(): array
    {
        return $this->rangeGoodsList;
    }

    /**
     * 获取实际抵扣的金额
     * @return int
     */
    public function getActualReducedMoney(): int
    {
        return $this->actualReducedMoney;
    }

    /**
     * 设置实际参与优惠券折扣的商品记录
     * @return void
     */
    private function setRangeGoodsList()
    {
        $this->rangeGoodsList = [];
        foreach ($this->goodsList as $goods) {
            $goods['total_price'] *= 100;
            $goodsKey = "{$goods['goods_id']}-{$goods['goods_sku_id']}";
            switch ($this->couponInfo['apply_range']) {
                case ApplyRangeEnum::ALL:
                    $this->rangeGoodsList[$goodsKey] = $goods;
                    break;
                case ApplyRangeEnum::SOME:
                    if (\in_array($goods['goods_id'], $this->couponInfo['apply_range_config']['goodsIds'])) {
                        $this->rangeGoodsList[$goodsKey] = $goods;
                    }
                    break;
                case ApplyRangeEnum::EXCLUDE:
                    if (!\in_array($goods['goods_id'], $this->couponInfo['apply_range_config']['goodsIds'])) {
                        $this->rangeGoodsList[$goodsKey] = $goods;
                    }
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * 验证当前类属性
     * @throws BaseException
     */
    private function checkAttribute()
    {
        if (empty($this->goodsList) || empty($this->couponInfo)) {
            throwError('goodsList or couponInfo not found.');
        }
    }

    /**
     * 计算实际抵扣的金额
     */
    private function setActualReducedMoney()
    {
        // 获取当前订单商品总额
        $orderTotalPrice = $this->getOrderTotalPrice();
        // 计算打折金额
        if ($this->couponInfo['coupon_type'] == CouponTypeEnum::DISCOUNT) {
            $reducePrice = $orderTotalPrice - ($orderTotalPrice * ($this->couponInfo['discount'] / 10));
        } else {
            $reducePrice = $this->couponInfo['reduce_price'] * 100;
        }
        // 优惠券最大允许抵扣到一分钱，所以此处判断抵扣金额大于等于订单金额时，减去一分钱
        $this->actualReducedMoney = ($reducePrice >= $orderTotalPrice) ? $orderTotalPrice - 1 : (int)$reducePrice;
    }

    /**
     * 获取当前订单商品总额
     * @return int
     */
    private function getOrderTotalPrice(): int
    {
        $orderTotalPrice = 0;
        foreach ($this->goodsList as $goods) {
            $orderTotalPrice += (int)($goods['total_price'] * 100);
        }
        return $orderTotalPrice;
    }

    /**
     * 计算商品抵扣的权重(占比)
     */
    private function setGoodsListWeight()
    {
        $orderTotalPrice = helper::getArrayColumnSum($this->rangeGoodsList, 'total_price');
        foreach ($this->rangeGoodsList as &$goods) {
            $weight = \round($goods['total_price'] / $orderTotalPrice, 6);
            $goods['weight'] = helper::scToStr((string)$weight, 6);
        }
        \array_sort($this->rangeGoodsList, 'weight', true);
    }

    /**
     * 计算商品抵扣的金额
     */
    private function setGoodsListCouponMoney(): void
    {
        foreach ($this->rangeGoodsList as &$goods) {
            $goods['coupon_money'] = helper::bcmul($this->actualReducedMoney, $goods['weight'], 0);
        }
    }

    private function setGoodsListCouponMoneyFill(int $assignedCouponMoney): void
    {
        if ($assignedCouponMoney === 0) {
            $temReducedMoney = $this->actualReducedMoney;
            foreach ($this->rangeGoodsList as &$goods) {
                if ($temReducedMoney == 0) break;
                $goods['coupon_money'] = 1;
                $temReducedMoney--;
            }
        }
    }

    private function setGoodsListCouponMoneyDiff(int $assignedCouponMoney): void
    {
        // 剩余未抵扣金额 = 实际抵扣金额 - 总抵扣金额 (已分配的)
        $tempDiff = $this->actualReducedMoney - $assignedCouponMoney;
        foreach ($this->rangeGoodsList as &$goods) {
            if ($tempDiff < 1) break;
            // 当抵扣金额大于商品总价时不再累积抵扣
            if ($goods['coupon_money'] >= $goods['total_price']) continue;
            $goods['coupon_money']++;
            $tempDiff--;
        }
    }
}
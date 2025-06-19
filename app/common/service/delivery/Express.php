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

namespace app\common\service\delivery;

use app\common\library\helper;
use app\common\model\Delivery as DeliveryModel;
use app\common\model\store\Setting as SettingModel;
use app\common\enum\Setting as SettingEnum;
use app\common\enum\delivery\Method as DeliveryMethodEnum;
use app\common\service\BaseService;

/**
 * 快递配送服务类
 * Class Delivery
 * @package app\common\service
 */
class Express extends BaseService
{
    // 用户收货城市ID
    private int $cityId;

    // 订单商品列表
    private iterable $goodsList;

    // 不在配送范围的商品ID
    private ?int $notInRuleGoodsId = null;

    // 运费模板数据集
    private array $data = [];

    // 是否启用了满额包邮
    private bool $enabledFullFree = false;

    /**
     * 构造方法
     * Express constructor.
     * @param $cityId
     * @param $goodsList
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function __construct($cityId, $goodsList)
    {
        parent::__construct();
        // 赋值传参
        $this->cityId = $cityId;
        $this->goodsList = $goodsList;
        // 整合运费模板
        $this->initDeliveryTemplate();
    }

    /**
     * 验证用户收货地址是否在配送范围
     * @return bool
     */
    public function isIntraRegion(): bool
    {
        if (!$this->cityId) {
            return false;
        }
        foreach ($this->data as $item) {
            $cityIds = [];
            foreach ($item['delivery']['rule'] as $ruleItem) {
                $cityIds = \array_merge($cityIds, $ruleItem['region']);
            }
            if (!in_array($this->cityId, $cityIds)) {
                $this->notInRuleGoodsId = current($item['goodsList'])['goods_id'];
                return false;
            }
        }
        return true;
    }

    /**
     * 获取不在配送范围的商品名称
     * @return null
     */
    public function getNotInRuleGoodsName()
    {
        $item = helper::getArrayItemByColumn($this->goodsList, 'goods_id', $this->notInRuleGoodsId);
        return !empty($item) ? $item['goods_name'] : null;
    }

    /**
     * 获取订单的配送费用
     * @param bool $allowFullFree 是否参与满额包邮
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDeliveryFee(bool $allowFullFree): string
    {
        if (empty($this->cityId) || empty($this->goodsList) || $this->notInRuleGoodsId > 0) {
            return helper::number2(0.00);
        }
        // 处理满额包邮
        $this->freeShipping($allowFullFree);
        // 计算配送金额
        foreach ($this->data as &$item) {
            // 计算当前配送模板的运费
            $item['delivery_fee'] = $this->calcDeliveryAmount($item);
        }
        // 根据运费组合策略获取最终运费金额
        return helper::number2($this->getFinalFreight());
    }

    /**
     * 获取是否启用了满额包邮
     * @return bool
     */
    public function getEnabledFullFree(): bool
    {
        return $this->enabledFullFree;
    }

    /**
     * 根据运费组合策略 计算最终运费
     * @return float|int|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getFinalFreight()
    {
        // 运费合集
        $expressPriceArr = helper::getArrayColumn($this->data, 'delivery_fee');
        if (empty($expressPriceArr)) {
            return 0.00;
        }
        // 最终运费金额
        $expressPrice = 0.00;
        // 判断运费组合策略
        switch (SettingModel::getItem('trade')['freight_rule']) {
            case '10':    // 策略1: 叠加
                $expressPrice = \array_sum($expressPriceArr);
                break;
            case '20':    // 策略2: 以最低运费结算
                $expressPrice = \min($expressPriceArr);
                break;
            case '30':    // 策略3: 以最高运费结算
                $expressPrice = \max($expressPriceArr);
                break;
        }
        return $expressPrice;
    }

    /**
     * 商品满额包邮
     * @param bool $allowFullFree 是否参与满额包邮
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function freeShipping(bool $allowFullFree): void
    {
        // 设置默认数据：包邮的商品列表
        helper::setDataAttribute($this->data, ['free_goods_list' => []], true);
        // 订单商品总金额
        $orderTotalPrice = helper::getArrayColumnSum($this->goodsList, 'total_price');
        // 获取满额包邮设置
        $options = SettingModel::getItem(SettingEnum::FULL_FREE);
        // 判断是否满足条件
        if (
            !$allowFullFree
            || !$options['is_open']
            || $orderTotalPrice < $options['money']
            || \in_array($this->cityId, $options['excludedRegions']['cityIds'])
        ) {
            return;
        }
        // 记录包邮的商品
        foreach ($this->data as &$item) {
            $item['free_goods_list'] = [];
            foreach ($item['goodsList'] as $goodsItem) {
                if (!\in_array($goodsItem['goods_id'], $options['excludedGoodsIds'])) {
                    $item['free_goods_list'][] = $goodsItem['goods_id'];
                }
            }
        }
        // 记录已启用满额包邮
        $this->enabledFullFree = true;
    }

    /**
     * 计算当前配送模板的运费
     * @param $item
     * @return float|mixed|string
     */
    private function calcDeliveryAmount($item)
    {
        // 获取运费模板下商品总数量or总重量
        if (!$totality = $this->getItemGoodsTotal($item)) {
            return 0.00;
        }
        // 当前收货城市配送规则
        $deliveryRule = $this->getCityDeliveryRule($item['delivery']);
        if ($totality <= $deliveryRule['first']) {
            return $deliveryRule['first_fee'];
        }
        // 续件or续重 数量
        $additional = helper::bcsub($totality, $deliveryRule['first']);
        if ($additional <= $deliveryRule['additional']) {
            return helper::bcadd($deliveryRule['first_fee'], $deliveryRule['additional_fee']);
        }
        // 续重总费用
        $additionalFee = 0.00;
        // 计算续重/件金额
        if ($deliveryRule['additional'] > 0) {
            $additionalFee = \ceil($additional / $deliveryRule['additional']) * $deliveryRule['additional_fee'];
        }
        return helper::bcadd($deliveryRule['first_fee'], $additionalFee);
    }

    /**
     * 获取运费模板下商品总数量or总重量
     * @param $item
     * @return int|string
     */
    private function getItemGoodsTotal($item)
    {
        $totalWeight = 0;   // 总重量
        $totalNum = 0;      // 总数量
        foreach ($item['goodsList'] as $goodsItem) {
            // 如果商品为包邮，则不计算总量中
            if (!\in_array($goodsItem['goods_id'], $item['free_goods_list'])) {
                $goodsWeight = helper::bcmul($goodsItem['skuInfo']['goods_weight'], $goodsItem['total_num']);
                $totalWeight = helper::bcadd($totalWeight, $goodsWeight);
                $totalNum = helper::bcadd($totalNum, $goodsItem['total_num']);
            }
        }
        return $item['delivery']['method'] == DeliveryMethodEnum::QUANTITY ? $totalNum : $totalWeight;
    }

    /**
     * 根据城市id获取规则信息
     * @param
     * @return array|false
     */
    private function getCityDeliveryRule($delivery)
    {
        foreach ($delivery['rule'] as $item) {
            if (\in_array($this->cityId, $item['region'])) {
                return $item;
            }
        }
        return false;
    }

    /**
     * 整合运费模板
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function initDeliveryTemplate(): void
    {
        // 运费模板ID集
        $deliveryIds = $this->getDeliveryIds();
        if (empty($deliveryIds)) {
            return;
        }
        // 运费模板列表
        $deliveryList = (new DeliveryModel)->getListByIds($deliveryIds);
        // 整理数据集
        foreach ($deliveryList as $item) {
            $this->data[$item['delivery_id']]['delivery'] = $item;
            $this->data[$item['delivery_id']]['goodsList'] = $this->getGoodsListByDeliveryId($item['delivery_id']);
        }
    }

    /**
     * 运费模板ID集
     * @return array
     */
    private function getDeliveryIds(): array
    {
        $deliveryIds = helper::getArrayColumn($this->goodsList, 'delivery_id');
        return \array_values(array_unique(array_filter($deliveryIds)));
    }

    /**
     * 根据运费模板id整理商品集
     * @param $deliveryId
     * @return array
     */
    private function getGoodsListByDeliveryId($deliveryId): array
    {
        $data = [];
        foreach ($this->goodsList as $item) {
            $item['delivery_id'] == $deliveryId && $data[] = $item;
        }
        return $data;
    }
}

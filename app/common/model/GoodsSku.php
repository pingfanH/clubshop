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
use app\common\library\helper;
use think\model\relation\HasOne;
use app\common\enum\goods\SpecType as SpecTypeEnum;

/**
 * 商品SKU模型
 * Class GoodsSku
 * @package app\common\model
 */
class GoodsSku extends BaseModel
{
    // 定义表名
    protected $name = 'goods_sku';

    // 定义主键
    protected $pk = 'id';

    /**
     * 关联模型：规格图片
     * @return HasOne
     */
    public function image(): HasOne
    {
        return $this->hasOne('UploadFile', 'file_id', 'image_id');
    }

    /**
     * 获取器：规格值ID集
     * @param $value
     * @return array|mixed
     */
    public function getSpecValueIdsAttr($value)
    {
        return helper::jsonDecode($value);
    }

    /**
     * 获取器：规格属性
     * @param $value
     * @return array|mixed
     */
    public function getGoodsPropsAttr($value)
    {
        return helper::jsonDecode($value);
    }

    /**
     * 设置器：规格值ID集
     * @param $value
     * @return string
     */
    public function setSpecValueIdsAttr($value): string
    {
        return helper::jsonEncode($value);
    }

    /**
     * 设置器：规格属性
     * @param $value
     * @return string
     */
    public function setGoodsPropsAttr($value): string
    {
        return helper::jsonEncode($value);
    }

    /**
     * 获取sku信息详情
     * @param int $goodsId
     * @param string $goodsSkuId
     * @return static|array|null
     */
    public static function detail(int $goodsId, string $goodsSkuId)
    {
        return static::get(['goods_id' => $goodsId, 'goods_sku_id' => $goodsSkuId], ['image']);
    }

    /**
     * 获取商品SKU列表
     * @param int $goodsId 商品ID
     * @param bool $withImage 是否携带sku封面图
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getSkuList(int $goodsId, bool $withImage = false): \think\Collection
    {
        return (new static)->with($withImage ? ['image'] : [])
            ->where('goods_id', '=', $goodsId)
            ->order(['id' => 'asc'])
            ->select();
    }

    /**
     * 生成skuList数据(写入goods_sku_id)
     * @param array $newSpecList
     * @param array $skuList
     * @return array
     */
    public static function getNewSkuList(array $newSpecList, array $skuList): array
    {
        foreach ($skuList as &$skuItem) {
            $skuItem['specValueIds'] = static::getSpecValueIds($newSpecList, $skuItem['skuKeys']);
            $skuItem['goodsProps'] = static::getGoodsProps($newSpecList, $skuItem['skuKeys']);
            $skuItem['goods_sku_id'] = implode('_', $skuItem['specValueIds']);
        }
        return $skuList;
    }

    /**
     * 根据$skuKeys生成规格值id集
     * @param array $newSpecList
     * @param array $skuKeys
     * @return array
     */
    private static function getSpecValueIds(array $newSpecList, array $skuKeys): array
    {
        $goodsSkuIdArr = [];
        foreach ($skuKeys as $skuKey) {
            $groupItem = helper::arraySearch($newSpecList, 'key', $skuKey['groupKey']);
            $specValueItem = helper::arraySearch($groupItem['valueList'], 'key', $skuKey['valueKey']);
            $goodsSkuIdArr[] = $specValueItem['spec_value_id'];
        }
        return $goodsSkuIdArr;
    }

    /**
     * 根据$skuKeys生成规格属性记录
     * @param array $newSpecList
     * @param array $skuKeys
     * @return array
     */
    private static function getGoodsProps(array $newSpecList, array $skuKeys): array
    {
        $goodsPropsArr = [];
        foreach ($skuKeys as $skuKey) {
            $groupItem = helper::arraySearch($newSpecList, 'key', $skuKey['groupKey']);
            $specValueItem = helper::arraySearch($groupItem['valueList'], 'key', $skuKey['valueKey']);
            $goodsPropsArr[] = [
                'group' => ['name' => $groupItem['spec_name'], 'id' => $groupItem['spec_id']],
                'value' => ['name' => $specValueItem['spec_value'], 'id' => $specValueItem['spec_value_id']]
            ];
        }
        return $goodsPropsArr;
    }

    /**
     * 新增商品sku记录
     * @param int $goodsId
     * @param array $newSkuList
     * @param int $specType
     * @param int|null $storeId 商城ID
     * @return array|bool|false
     */
    public static function add(int $goodsId, int $specType = SpecTypeEnum::SINGLE, array $newSkuList = [], int $storeId = null)
    {
        // 单规格模式
        if ($specType === SpecTypeEnum::SINGLE) {
            return (new static)->save(\array_merge($newSkuList, [
                'goods_id' => $goodsId,
                'goods_sku_id' => 0,
                'store_id' => $storeId ?: self::$storeId
            ]));
        } // 多规格模式
        elseif ($specType === SpecTypeEnum::MULTI) {
            // 批量写入商品sku记录
            return static::increasedFroMulti($goodsId, $newSkuList, $storeId);
        }
        return false;
    }

    /**
     * 批量写入商品sku记录
     * @param int $goodsId
     * @param array $skuList
     * @param int|null $storeId 商城ID
     * @return array|false
     */
    private static function increasedFroMulti(int $goodsId, array $skuList, int $storeId = null)
    {
        $dataset = [];
        foreach ($skuList as $skuItem) {
            $dataset[] = \array_merge($skuItem, [
                'id' => null,   // 此处的id必须是数据库自增
                'goods_sku_id' => $skuItem['goods_sku_id'],
                'goods_price' => $skuItem['goods_price'] ?: 0.01,
                'line_price' => $skuItem['line_price'] ?: 0.00,
                'goods_sku_no' => $skuItem['goods_sku_no'] ?: '',
                'stock_num' => $skuItem['stock_num'] ?: 0,
                'goods_weight' => $skuItem['goods_weight'] ?: 0,
                'goods_props' => $skuItem['goodsProps'],
                'spec_value_ids' => $skuItem['specValueIds'],
                'goods_id' => $goodsId,
                'store_id' => $storeId ?: self::$storeId
            ]);
        }
        return (new static)->addAll($dataset);
    }

    /**
     * 获取库存总数量 (根据sku列表数据)
     * @param array $skuList
     * @return float|int
     */
    public static function getStockTotal(array $skuList)
    {
        return (int)helper::getArrayColumnSum($skuList, 'stock_num');
    }

    /**
     * 获取商品价格高低区间 (根据sku列表数据)
     * @param array $skuList
     * @return array
     */
    public static function getGoodsPrices(array $skuList): array
    {
        $goodsPriceArr = helper::getArrayColumn($skuList, 'goods_price');
        return empty($goodsPriceArr) ? [0, 0] : [min($goodsPriceArr), max($goodsPriceArr)];
    }

    /**
     * 获取划线价格高低区间 (根据sku列表数据)
     * @param array $skuList
     * @return array
     */
    public static function getLinePrices(array $skuList): array
    {
        $linePriceArr = helper::getArrayColumn($skuList, 'line_price');
        return empty($linePriceArr) ? [0, 0] : [min($linePriceArr), max($linePriceArr)];
    }
}

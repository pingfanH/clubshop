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
use think\model\relation\BelongsTo;

/**
 * 商品规格关系模型
 * Class GoodsSpecRel
 * @package app\common\model
 */
class GoodsSpecRel extends BaseModel
{
    // 定义表名
    protected $name = 'goods_spec_rel';

    // 定义主键
    protected $pk = 'id';

    protected $updateTime = false;

    /**
     * 关联规格组
     * @return BelongsTo
     */
    public function spec(): BelongsTo
    {
        return $this->belongsTo('Spec');
    }

    /**
     * 关联规格值
     * @return BelongsTo
     */
    public function specValue(): BelongsTo
    {
        return $this->belongsTo('SpecValue');
    }

    /**
     * 指定商品的规格列表
     * @param int $goodsId 商品ID
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getSpecList(int $goodsId): array
    {
        // 获取指定商品的规格值关系记录
        $data = static::getList($goodsId);
        // 规格组
        $groupData = [];
        foreach ($data as $groupKey => $item) {
            $groupData[$item['spec_id']] = [
                'spec_id' => $item['spec']['spec_id'],
                'spec_name' => $item['spec']['spec_name']
            ];
        }
        // 去除索引
        $specList = array_values($groupData);
        // 规格值
        foreach ($specList as $groupKey => &$group) {
            $group['key'] = $groupKey;
            foreach ($data as $valueKey => $item) {
                ($item['spec_id'] == $group['spec_id']) && $group['valueList'][] = [
                    'key' => isset($group['valueList']) ? count($group['valueList']) : 0,
                    'groupKey' => $groupKey,
                    'spec_value_id' => $item['specValue']['spec_value_id'],
                    'spec_value' => $item['specValue']['spec_value']
                ];
            }
        }
        return $specList;
    }

    /**
     * 获取指定商品的规格值关系记录
     * @param int $goodsId 商品ID
     * @return iterable|\think\model\Collection|\think\Paginator
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private static function getList(int $goodsId)
    {
        return (new static)->with(['spec', 'specValue'])
            ->where('goods_id', '=', $goodsId)
            ->select();
    }

    /**
     * 批量写入商品与规格值关系记录
     * @param int $goodsId 商品ID
     * @param array $specList 规格数据
     * @param int|null $storeId 商城ID
     * @return array|false
     */
    public static function increased(int $goodsId, array $specList, int $storeId = null)
    {
        $dataset = [];
        foreach ($specList as $item) {
            foreach ($item['valueList'] as $specValueItem) {
                $dataset[] = [
                    'goods_id' => $goodsId,
                    'spec_id' => $item['spec_id'],
                    'spec_value_id' => $specValueItem['spec_value_id'],
                    'store_id' => $storeId ?: self::$storeId
                ];
            }
        }
        return (new static)->addAll($dataset);
    }
}

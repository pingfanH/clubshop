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
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\model\relation\BelongsTo;

/**
 * 规格/属性(值)模型
 * Class SpecValue
 * @package app\common\model
 */
class SpecValue extends BaseModel
{
    // 定义表名
    protected $name = 'spec_value';

    // 定义主键
    protected $pk = 'spec_value_id';

    protected $updateTime = false;

    /**
     * 关联规格组表
     * @return BelongsTo
     */
    public function spec(): BelongsTo
    {
        return $this->belongsTo('Spec');
    }

    /**
     * 规格值写入数据库并生成id
     * @param int $specId
     * @param array $valueList
     * @param int|null $storeId
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function getNewValueList(int $specId, array $valueList, int $storeId = null): array
    {
        // 规格组名称合集
        $values = helper::getArrayColumn($valueList, 'spec_value');
        // 获取到已存在的规格值
        $alreadyData = static::getListByValues($specId, $values, $storeId);
        // 遍历整理新的规格集
        foreach ($valueList as $key => &$item) {
            $alreadyItem = helper::getArrayItemByColumn($alreadyData, 'spec_value', $item['spec_value']);
            if (!empty($alreadyItem)) {
                // 规格值已存在的记录spec_value_id
                $item['spec_value_id'] = $alreadyItem['spec_value_id'];
            } else {
                // 规格值不存在的新增记录
                $result = self::add($specId, $item, $storeId);
                $item['spec_value_id'] = $result['spec_value_id'];
            }
        }
        return $valueList;
    }

    /**
     * 根据规格组名称集获取列表
     * @param int $specId
     * @param array $values
     * @param int|null $storeId
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private static function getListByValues(int $specId, array $values, int $storeId = null): \think\Collection
    {
        return (new static)->where('spec_id', '=', $specId)
            ->where('spec_value', 'in', $values)
            ->where('store_id', '=', $storeId)
            ->select();
    }

    /**
     * 新增规格值记录
     * @param int $specId
     * @param array $item
     * @param int|null $storeId
     * @return SpecValue|\think\Model
     */
    private static function add(int $specId, array $item, int $storeId = null)
    {
        return self::create([
            'spec_value' => $item['spec_value'],
            'spec_id' => $specId,
            'store_id' => $storeId ?: self::$storeId
        ]);
    }
}

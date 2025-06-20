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
use cores\exception\BaseException;
use app\common\library\helper;
use app\store\model\SpecValue as SpecValueModel;

/**
 * 规格/属性(组)模型
 * Class Spec
 * @package app\common\model
 */
class Spec extends BaseModel
{
    // 定义表名
    protected $name = 'spec';

    // 定义主键
    protected $pk = 'spec_id';

    protected $updateTime = false;

    /**
     * 验证规格值是否合法
     * @param array $specList
     * @throws BaseException
     */
    public static function checkSpecData(array $specList)
    {
        $specNames = helper::getArrayColumn($specList, 'spec_name');
        if (count($specList) != count(array_unique($specNames))) {
            throwError('很抱歉，不能存在重复的规格组');
        }
        foreach ($specList as $item) {
            $values = helper::getArrayColumn($item['valueList'], 'spec_value');
            if (count($item['valueList']) != count(array_unique($values))) {
                throwError('很抱歉，不能存在重复的规格值');
            }
        }
    }

    /**
     * 根据规格值计算能生成的SKU总量
     * @param array $specList
     * @return int
     */
    public static function calcSkuListTotal(array $specList): int
    {
        $total = 1;
        foreach ($specList as $item) {
            $total *= \count($item['valueList']);
        }
        return $total;
    }

    /**
     * 规格组写入数据库并生成id
     * 此时的$specList是用户端传来的
     * @param array $specList
     * @param int|null $storeId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getNewSpecList(array $specList, int $storeId = null): array
    {
        // 规格组名称合集
        $names = helper::getArrayColumn($specList, 'spec_name');
        // 获取到已存在的规格组
        $alreadyData = static::getListByNames($names, $storeId);
        // 遍历整理新的规格集
        foreach ($specList as $key => &$item) {
            $alreadyItem = helper::getArrayItemByColumn($alreadyData, 'spec_name', $item['spec_name']);
            if (!empty($alreadyItem)) {
                // 规格名已存在的记录spec_id
                $item['spec_id'] = $alreadyItem['spec_id'];
            } else {
                // 规格名不存在的新增记录
                $result = static::add($item, $storeId);
                $item['spec_id'] = $result['spec_id'];
            }
            // 规格值写入数据库并生成id
            $item['valueList'] = SpecValueModel::getNewValueList((int)$item['spec_id'], $item['valueList'], $storeId);
        }
        return $specList;
    }

    /**
     * 根据规格组名称集获取列表
     * @param array $names
     * @param int|null $storeId
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private static function getListByNames(array $names, int $storeId = null): \think\Collection
    {
        return (new static)->where('spec_name', 'in', $names)
            ->where('store_id', '=', $storeId)
            ->select();
    }

    /**
     * 新增规格组记录
     * @param array $item
     * @param int|null $storeId
     * @return Spec|\think\Model
     */
    private static function add(array $item, int $storeId = null)
    {
        return self::create([
            'spec_name' => $item['spec_name'],
            'store_id' => $storeId ?: self::$storeId
        ]);
    }
}

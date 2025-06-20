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

namespace app\common\model\store;

use cores\BaseModel;
use app\common\model\Region as RegionModel;

/**
 * 商家地址模型
 * Class Address
 * @package app\common\model\store
 */
class Address extends BaseModel
{
    // 定义表名
    protected $name = 'store_address';

    // 定义主键
    protected $pk = 'address_id';

    /**
     * 追加字段
     * @var array
     */
    protected $append = ['cascader', 'region', 'full_address'];

    /**
     * 省市区ID集
     * @param $value
     * @param $data
     * @return array
     */
    public function getCascaderAttr($value, $data): array
    {
        return [$data['province_id'], $data['city_id'], $data['region_id']];
    }

    /**
     * 省市区名称集
     * @param $value
     * @param $data
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRegionAttr($value, $data): array
    {
        return $this->getRegionNames($data);
    }

    /**
     * 获取完整地址
     * @param $value
     * @param $data
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getFullAddressAttr($value, $data): string
    {
        $rgionNames = $this->getRegionNames($data);
        return "{$rgionNames['province']}{$rgionNames['city']}{$rgionNames['region']}{$data['detail']}";
    }

    /**
     * 获取省市区名称
     * @param array $data
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getRegionNames(array $data)
    {
        static $dataset = [];
        $id = $data[$this->getPk()];
        if (!isset($dataset[$id])) {
            $dataset[$id] = [
                'province' => RegionModel::getNameById($data['province_id']),
                'city' => RegionModel::getNameById($data['city_id']),
                'region' => RegionModel::getNameById($data['region_id']),
            ];
        }
        return $dataset[$id];
    }

    /**
     * 详情记录
     * @param int $addressId
     * @return static|array|null
     */
    public static function detail(int $addressId)
    {
        return self::get($addressId);
    }
}

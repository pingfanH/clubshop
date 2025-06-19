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
use think\model\relation\HasMany;

/**
 * 配送模板模型
 * Class Delivery
 * @package app\common\model
 */
class Delivery extends BaseModel
{
    // 定义表名
    protected $name = 'delivery';

    // 定义主键
    protected $pk = 'delivery_id';

    /**
     * 关联配送模板区域及运费
     * @return HasMany
     */
    public function rule(): HasMany
    {
        return $this->hasMany('DeliveryRule');
    }

    /**
     * 运费模板详情
     * @param int $deliveryId
     * @param array $with
     * @return static|array|null
     */
    public static function detail(int $deliveryId, array $with = [])
    {
        return self::get($deliveryId, ['rule']);
    }

    /**
     * 获取列表(根据模板id集)
     * @param array $deliveryIds
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getListByIds(array $deliveryIds): \think\Collection
    {
        return $this->with(['rule'])
            ->where('delivery_id', 'in', $deliveryIds)
            ->order(['sort', $this->getPk()])
            ->select();
    }

    /**
     * 验证模板ID是否存在
     * @param int $deliveryId 模板ID
     * @param int|null $storeId 商城ID
     * @return bool
     */
    public static function checkDeliveryId(int $deliveryId, int $storeId = null): bool
    {
        return (bool)(new static)->where('delivery_id', '=', $deliveryId)
            ->where('is_delete', '=', 0)
            ->where('store_id', '=', $storeId)
            ->value('delivery_id');
    }
}

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

namespace app\common\model\goods;

use cores\BaseModel;

/**
 * 商品服务与承诺模型
 * Class Service
 */
class Service extends BaseModel
{
    // 定义表名
    protected $name = 'goods_service';

    // 定义主键
    protected $pk = 'service_id';

    /**
     * 帮助详情
     * @param int $helpId
     * @return static|array|null
     */
    public static function detail(int $helpId)
    {
        return self::get($helpId);
    }

    /**
     * 过滤不存在的ID集
     * @param array $serviceIds
     * @param int|null $storeId
     * @return array
     */
    public static function filterServiceIds(array $serviceIds, int $storeId = null): array
    {
        return (new static)->where('service_id', 'in', $serviceIds)
            ->where('is_delete', '=', 0)
            ->where('store_id', '=', $storeId ?: self::$storeId)
            ->column('service_id');
    }
}

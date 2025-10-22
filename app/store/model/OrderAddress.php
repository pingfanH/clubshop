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

namespace app\store\model;

use app\common\model\OrderAddress as OrderAddressModel;

/**
 * 订单收货地址模型
 * Class OrderAddress
 * @package app\store\model
 */
class OrderAddress extends OrderAddressModel
{
    /**
     * 修改订单收货地址
     * @param int $orderAddressId
     * @param array $data
     * @return bool
     */
    public static function updateAddress(int $orderAddressId, array $data): bool
    {
        static::updateBase([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'province_id' => $data['cascader'][0],
            'city_id' => $data['cascader'][1],
            'region_id' => $data['cascader'][2],
            'detail' => $data['detail'],
        ], $orderAddressId);
        return true;    // todo
    }
}

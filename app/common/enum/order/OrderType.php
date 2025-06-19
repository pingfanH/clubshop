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

namespace app\common\enum\order;

use app\common\enum\EnumBasics;

/**
 * 枚举类：订单类型
 * Class OrderType
 * @package app\common\enum\order
 */
class OrderType extends EnumBasics
{
    // 实物订单
    const PHYSICAL = 10;

    /**
     * 获取枚举数据
     * @return array
     */
    public static function data(): array
    {
        return [
            self::PHYSICAL => [
                'name' => '实物订单',
                'value' => self::PHYSICAL,
            ]
        ];
    }
}
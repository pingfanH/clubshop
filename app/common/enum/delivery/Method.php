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

namespace app\common\enum\delivery;

use app\common\enum\EnumBasics;

/**
 * 枚举类：配送模板 - 计费方式
 * Class Method
 * @package app\common\enum\goods
 */
class Method extends EnumBasics
{
    // 按数量
    const QUANTITY = 10;

    // 按重量
    const WEIGHT = 20;

    /**
     * 获取枚举类型值
     * @return array
     */
    public static function data(): array
    {
        return [
            self::QUANTITY => [
                'name' => '按数量',
                'value' => self::QUANTITY,
            ],
            self::WEIGHT => [
                'name' => '按重量',
                'value' => self::WEIGHT,
            ]
        ];
    }
}

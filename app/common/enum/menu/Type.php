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

namespace app\common\enum\menu;

use app\common\enum\EnumBasics;

/**
 * 枚举类：商家后台菜单 - 菜单类型
 * Class Type
 * @package app\common\enum\goods
 */
class Type extends EnumBasics
{
    // 页面
    const PAGE = 10;

    // 操作
    const ACTION = 20;

    /**
     * 获取枚举类型值
     * @return array
     */
    public static function data(): array
    {
        return [
            self::PAGE => [
                'name' => '页面',
                'value' => self::PAGE,
            ],
            self::ACTION => [
                'name' => '操作',
                'value' => self::ACTION,
            ]
        ];
    }
}

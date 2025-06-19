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

namespace app\common\enum\setting;

use app\common\enum\EnumBasics;

/**
 * 小票打印机类型 枚举类
 * Class Printer
 * @package app\common\enum
 */
class PrinterType extends EnumBasics
{
    // 飞鹅打印机
    const FEI_E_YUN = 'FEI_E_YUN';

    // 365云打印
    const PRINT_CENTER = 'PRINT_CENTER';

    /**
     * 获取类型值
     * @return array
     */
    public static function data(): array
    {
        return [
            self::FEI_E_YUN => [
                'name' => '飞鹅打印机',
                'value' => self::FEI_E_YUN,
            ],
            self::PRINT_CENTER => [
                'name' => '365云打印',
                'value' => self::PRINT_CENTER,
            ]
        ];
    }

}

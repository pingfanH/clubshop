<?php
declare (strict_types=1);

namespace app\common\enum\order;

use app\common\enum\EnumBasics;

/**
 * 枚举类：订单支付方式
 * Class PayType
 * @package app\common\enum\order
 */
class PayType extends EnumBasics
{
    // 全款购买
    const FULL = 10;

    // 定金购买
    const DEPOSIT = 20;

    /**
     * 获取枚举数据
     * @return array
     */
    public static function data(): array
    {
        return [
            self::FULL => [
                'name' => '全款购买',
                'value' => self::FULL,
            ],
            self::DEPOSIT => [
                'name' => '定金购买',
                'value' => self::DEPOSIT,
            ]
        ];
    }
}

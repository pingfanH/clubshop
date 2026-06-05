<?php
declare (strict_types=1);

namespace app\common\enum\order;

use app\common\enum\EnumBasics;

/**
 * 枚举类：定金支付状态
 * Class DepositPayStatus
 * @package app\common\enum\order
 */
class DepositPayStatus extends EnumBasics
{
    // 未付
    const PENDING = 10;

    // 已付
    const PAID = 20;

    /**
     * 获取枚举数据
     * @return array
     */
    public static function data(): array
    {
        return [
            self::PENDING => [
                'name' => '未付定金',
                'value' => self::PENDING,
            ],
            self::PAID => [
                'name' => '已付定金',
                'value' => self::PAID,
            ]
        ];
    }
}

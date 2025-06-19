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

namespace app\common\enum\payment\trade;

use app\common\enum\EnumBasics;

/**
 * 枚举类：第三方支付交易记录 - 交易状态
 * Class TradeStatus
 * @package app\common\enum\payment\trade
 */
class TradeStatus extends EnumBasics
{
    // 未支付
    const UNPAID = 10;

    // 支付成功
    const SUCCESS = 20;

    // 转入退款
    const REFUND = 30;

    // 已关闭
    const CLOSED = 40;

    /**
     * 获取类型值
     * @return array
     */
    public static function data(): array
    {
        return [
            self::UNPAID => [
                'name' => '未支付',
                'value' => self::UNPAID
            ],
            self::SUCCESS => [
                'name' => '支付成功',
                'value' => self::SUCCESS
            ],
            self::REFUND => [
                'name' => '转入退款',
                'value' => self::REFUND
            ],
            self::CLOSED => [
                'name' => '已关闭',
                'value' => self::CLOSED
            ]
        ];
    }
}
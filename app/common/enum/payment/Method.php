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

namespace app\common\enum\payment;

use app\common\enum\EnumBasics;

/**
 * 枚举类：支付方式类型
 * Class Method
 * @package app\common\enum\payment
 */
class Method extends EnumBasics
{
    // 微信支付
    const WECHAT = 'wechat';

    // 支付宝支付
    const ALIPAY = 'alipay';

    // 余额支付
    const BALANCE = 'balance';

    /**
     * 获取类型值
     * @return array
     */
    public static function data(): array
    {
        return [
            self::WECHAT => [
                'name' => '微信支付',
                'value' => self::WECHAT,
            ],
            self::ALIPAY => [
                'name' => '支付宝',
                'value' => self::ALIPAY,
            ],
            self::BALANCE => [
                'name' => '余额支付',
                'value' => self::BALANCE,
            ]
        ];
    }
}

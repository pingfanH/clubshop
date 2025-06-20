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

namespace app\common\enum;

/**
 * 枚举类：客户端类型
 * Class Client
 * @package app\common\enum
 */
class Client extends EnumBasics
{
    // APP端
    const APP = 'APP';

    // H5端
    const H5 = 'H5';

    // 微信公众号端
    // 相当于H5端运行在微信内置浏览器, 但是需要使用微信的jssdk所以要单独区分
    const WXOFFICIAL = 'WXOFFICIAL';

    // 微信小程序端
    const MP_WEIXIN = 'MP-WEIXIN';

    /**
     * 获取订单类型值
     * @return array
     */
    public static function data(): array
    {
        return [
            self::APP => [
                'name' => 'APP端',
                'value' => self::APP,
            ],
            self::H5 => [
                'name' => 'H5端',
                'value' => self::H5,
            ],
            self::MP_WEIXIN => [
                'name' => '微信小程序端',
                'value' => self::MP_WEIXIN,
            ],
            self::WXOFFICIAL => [
                'name' => '微信公众号端',
                'value' => self::WXOFFICIAL,
            ],
        ];
    }

    /**
     * 根据值获取名称
     * @param string $value
     * @return string
     */
    public static function getName(string $value): string
    {
        return self::data()[$value]['name'];
    }
}
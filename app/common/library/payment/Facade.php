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
namespace app\common\library\payment;

use app\common\library\payment\gateway\Driver;

/**
 * 第三方支付工厂类
 * Class Facade
 * @package app\common\library\payment
 * @mixin Payment
 * @method static Driver store(string $name = null) 连接或者切换驱动
 * @method static Driver setOptions(string $client, array $options) 设置支付配置参数
 */
class Facade extends \think\Facade
{
    protected static function getFacadeClass(): string
    {
        return Payment::class;
    }
}
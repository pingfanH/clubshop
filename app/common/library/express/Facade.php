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

namespace app\common\library\express;

use app\common\library\express\provider\Driver;

/**
 * 物流查询工厂类
 * Class Facade
 * @package app\common\library\express
 * @mixin Express
 * @method static Driver store(string $name = null) 连接或者切换驱动
 * @method static Driver setOptions(array $options) 设置支付配置参数
 */
class Facade extends \think\Facade
{
    protected static function getFacadeClass(): string
    {
        return Express::class;
    }
}
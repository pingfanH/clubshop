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

namespace app\common\service\order\source;

use app\common\service\BaseService;
use app\common\enum\order\OrderSource as OrderSourceEnum;

/**
 * 订单来源工厂类
 * Class Factory
 * @package app\common\service\order\source
 */
class Factory extends BaseService
{
    // 订单来源的结算台服务类
    private static array $class = [
        OrderSourceEnum::MAIN => Main::class
    ];

    /**
     * 根据订单来源获取商品库存类
     * @param int $orderSource 订单来源
     * @return mixed
     */
    public static function getFactory(int $orderSource = OrderSourceEnum::MAIN)
    {
        static $classObj = [];
        if (!isset($classObj[$orderSource])) {
            $classObj[$orderSource] = new self::$class[$orderSource];
        }
        return $classObj[$orderSource];
    }
}
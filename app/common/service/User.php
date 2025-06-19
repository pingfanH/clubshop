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

namespace app\common\service;

use app\common\service\Order as OrderService;

/**
 * 用户服务类
 * Class User
 * @package app\common\service
 */
class User extends BaseService
{
    /**
     * 判断指定用户ID是否为新用户
     * 新用户定义：在店铺中无支付过订单、已支付过订单但手动取消并退款
     * @param int $userId
     * @return bool
     */
    public static function checkIsNewUser(int $userId): bool
    {
        return !OrderService::getValidCountByUser($userId);
    }
}
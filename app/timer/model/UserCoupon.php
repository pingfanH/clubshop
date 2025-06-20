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

namespace app\timer\model;

use app\common\model\UserCoupon as UserCouponModel;

/**
 * 用户优惠券模型
 * Class UserCoupon
 * @package app\timer\model
 */
class UserCoupon extends UserCouponModel
{
    // 是否允许全局查询store_id
    protected bool $isGlobalScopeStoreId = false;

    /**
     * 获取已过期的优惠券ID集
     * @param int $storeId
     * @return array
     */
    public function getExpiredCouponIds(int $storeId): array
    {
        $time = time();
        return $this->where('is_expire', '=', 0)
            ->where('is_use', '=', 0)
            ->where('end_time', '<=', time())
            ->where('store_id', '=', $storeId)
            ->column('user_coupon_id');
    }

    /**
     * 设置优惠券过期状态
     * @param array $couponIds
     * @return bool
     */
    public function setIsExpire(array $couponIds): bool
    {
        if (empty($couponIds)) {
            return false;
        }
        return $this->updateBase(['is_expire' => 1], [['user_coupon_id', 'in', $couponIds]]);
    }
}

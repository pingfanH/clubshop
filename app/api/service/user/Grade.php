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

namespace app\api\service\user;

use app\common\library\helper;
use app\common\service\BaseService;
use app\api\service\User as UserService;
use app\api\model\user\Grade as UserGradeModel;
use cores\exception\BaseException;

/**
 * 服务类: 会员等级
 * Class Grade
 * @package app\api\service\user
 */
class Grade extends BaseService
{
    /**
     * 获取当前登录用户的会员等级信息
     * @param bool $isForce 是否强制验证登录, 如果未登录将抛错
     * @throws BaseException
     */
    public static function getCurrentGradeInfo(bool $isForce = false)
    {
        // 当前登录的用户信息
        $userInfo = UserService::getCurrentLoginUser($isForce);
        if (empty($userInfo) || empty($userInfo['grade_id'])) {
            return false;
        }
        // 获取会员等级信息
        $gradeInfo = UserGradeModel::detail($userInfo['grade_id']);
        if (empty($gradeInfo) || $gradeInfo['is_delete'] || empty($gradeInfo['status'])) {
            return false;
        }
        return $gradeInfo;
    }

    /**
     * 获取和计算折扣后的价格
     * @param $originalPrice
     * @param $discountRatio
     * @return string
     */
    public static function getDiscountPrice($originalPrice, $discountRatio): string
    {
        // 使用高精度方法计算等级折扣; 因bcmach不支持四舍五入, 所以精确计算到3位数, 使用round四舍五入
        $discountPrice = helper::bcmul($originalPrice, $discountRatio / 10, 3);
        $discountPrice = round((float)$discountPrice, 2);
        return helper::number2($discountPrice, true);
    }
}
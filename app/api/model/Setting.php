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

namespace app\api\model;

use app\common\model\store\Setting as SettingModel;
use app\common\enum\Setting as SettingEnum;

/**
 * 系统设置模型
 * Class Setting
 * @package app\api\model
 */
class Setting extends SettingModel
{
    /**
     * 获取积分名称
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getPointsName(): string
    {
        return static::getItem(SettingEnum::POINTS)['points_name'];
    }

    /**
     * 获取未支付订单自动关闭期限(单位:小时)
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getOrderCloseTime()
    {
        return static::getItem(SettingEnum::TRADE)['order']['closeHours'];
    }

    /**
     * 获取充值设置
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getRecharge(): array
    {
        return static::getItem(SettingEnum::RECHARGE);
    }
}

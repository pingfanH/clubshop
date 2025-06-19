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

namespace app\api\model\wxapp;

use app\common\model\wxapp\Setting as SettingModel;

/**
 * 微信小程序设置模型
 * Class Setting
 * @package app\api\model\wxapp
 */
class Setting extends SettingModel
{
    /**
     * 验证当前是否允许访问
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function checkStatus(): bool
    {
        return (bool)static::getItem('basic', static::$storeId)['enabled'];
    }
}
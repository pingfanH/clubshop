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

namespace app\common\service\system;

use app\common\service\BaseService;
use app\common\model\system\Process as SystemProcessModel;

/**
 * 系统进程服务类
 * Class Process
 * @package app\common\service\system
 */
class Process extends BaseService
{
    /**
     * 获取指定进程最后运行时间
     * @param string $key
     * @return mixed
     */
    public static function getLastWorkingTime(string $key)
    {
        return (new SystemProcessModel)->where('key', '=', $key)->value('last_working_time');
    }

    /**
     * 记录指定进程最后运行时间
     * @param string $key
     * @return bool
     */
    public static function setLastWorkingTime(string $key): bool
    {
        return SystemProcessModel::updateBase(['last_working_time' => \time()], ['key' => $key]);
    }
}
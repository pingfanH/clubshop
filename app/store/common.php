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

use app\store\service\Auth;


/**
 * 应用公共函数库文件
 */

/**
 * 验证指定url是否有访问权限
 * @param string|array $url
 * @param bool $strict 严格模式
 * @return bool
 */
function checkPrivilege($url, bool $strict = true): bool
{
    try {
        return Auth::getInstance()->checkPrivilege($url, $strict);
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * 日期转换时间戳
 * 例如: 2020-04-01 08:15:08 => 1585670400
 * @param string $date
 * @param bool $isWithTime 是否包含时间
 * @return false|int
 */
function str2date(string $date, bool $isWithTime = false)
{
    if (!$isWithTime) {
        $date = date('Y-m-d', strtotime($date));
    }
    return strtotime($date);
}

/**
 * 格式化起止时间(为了兼容前端RangePicker组件)
 * 2020-04-01T08:15:08.891Z => 1585670400
 * @param array $times
 * @param bool $isWithTime 是否包含时间
 * @return array
 */
function between_time(array $times, bool $isWithTime = false): array
{
    foreach ($times as &$time) {
        $time = trim($time, '&quot;');
        $time = str2date($time, $isWithTime);
    }
    return ['start_time' => current($times), 'end_time' => next($times)];
}

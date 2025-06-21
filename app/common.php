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

/**
 * 应用公共函数库文件
 */

use think\Response;
use think\facade\Env;
use think\facade\Log;
use think\facade\Config;
use think\facade\Request;
use cores\exception\BaseException;
use cores\exception\DebugException;
use think\exception\HttpResponseException;

/**
 * 打印调试函数 html
 * @param $content
 * @param bool $export
 */
function pre($content, bool $export = false)
{
    $output = $export ? var_export($content, true) : print_r($content, true);
    echo "<pre>{$output}</pre>";
    app_end();
}

/**
 * 打印调试函数 json
 * @param $content
 * @param bool $export
 * @throws DebugException
 */
function pree($content, bool $export = false)
{
    $output = $export ? var_export($content, true) : $content;
    throw new DebugException([], $output);
}

/**
 * 打印调试函数 输出在终端
 * 只有debug模式下输出
 * @param $content
 * @return void
 */
function tre($content)
{
//    if (is_debug()) {
    $output = print_r($content, true);
    echo "$output\n";
//    }
}

/**
 * 输出错误信息
 * @param string $message 报错信息
 * @param int|null $status 状态码,默认为配置文件status.error
 * @param array $data 附加数据
 * @throws BaseException
 */
function throwError(string $message, ?int $status = null, array $data = [])
{
    is_null($status) && $status = config('status.error');
    $data = \array_merge(['isPrompt' => true], $data);
    throw new BaseException(compact('status', 'message', 'data'));
}

/**
 * 下划线转驼峰
 * @param string $str
 * @param string $separator
 * @return string
 */
function camelize(string $str, string $separator = '_'): string
{
    $str = ucwords(str_replace($separator, ' ', $str));
    $str = str_replace(' ', '', $str);
    return lcfirst($str);
}

/**
 * 驼峰转下划线
 * @param string $camelCaps
 * @param string $separator
 * @return string
 */
function uncamelize(string $camelCaps, string $separator = '_'): string
{
    return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $camelCaps));
}

/**
 * 生成密码hash值
 * @param string $password
 * @return string
 */
function encryption_hash(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * 获取当前域名及根路径
 * @return string
 */
function base_url(): string
{
    static $baseUrl = '';
    if (empty($baseUrl)) {
        $request = Request::instance();
        // url协议，设置强制https或自动获取
        $scheme = Config::get('route')['url_force_https'] ? 'https' : $request->scheme();
        // url子目录
        $rootUrl = root_url();
        // 拼接完整url
        $baseUrl = "{$scheme}://" . $request->host() . $rootUrl;
    }
    return $baseUrl;
}

/**
 * 获取当前url的子目录路径
 * @return string
 */
function root_url(): string
{
    static $rootUrl = '';
    if (empty($rootUrl)) {
        $request = Request::instance();
        $subUrl = str_replace('\\', '/', dirname($request->baseFile()));
        $rootUrl = $subUrl . ($subUrl === '/' ? '' : '/');
    }
    return $rootUrl;
}

/**
 * 获取当前uploads目录访问地址
 * @return string
 */
function uploads_url(): string
{
    return base_url() . 'uploads/';
}

/**
 * 获取当前temp目录访问地址
 * @return string
 */
function temp_url(): string
{
    return base_url() . 'temp/';
}

/**
 * 获取当前的应用名称
 * @return mixed
 */
function app_name()
{
    return app('http')->getName();
}

/**
 * 获取web根目录
 * @return string
 */
function web_path(): string
{
    static $webPath = '';
    if (empty($webPath)) {
        $request = Request::instance();
        $webPath = dirname($request->server('SCRIPT_FILENAME')) . DIRECTORY_SEPARATOR;
    }
    return $webPath;
}

/**
 * 获取data目录路径
 * @return string
 */
function data_path(): string
{
    return config('filesystem.disks.data.root') . DIRECTORY_SEPARATOR;
}

/**
 * 获取runtime根目录路径
 * @return string
 */
function runtime_root_path(): string
{
//    return dirname(runtime_path()) . DIRECTORY_SEPARATOR;
    return root_path() . 'runtime' . DIRECTORY_SEPARATOR;
}

/**
 * 写入日志 (使用tp自带驱动记录到runtime目录中)
 * @param mixed $value
 * @param string $type
 */
function log_record($value, string $type = 'info')
{
    $content = is_string($value) ? $value : print_r($value, true);
    Log::record($content, $type);
}

/**
 * 重组缓存数据（多维）
 * @param $data1
 * @param $data2
 * @return array
 */
function resetOptions($data1, $data2): array
{
    $data = [];
    foreach ($data1 + $data2 as $key => $val) {
        $data[$key] = (isset($data1[$key]) && is_array($data1[$key]) && isset($data2[$key]) && is_array($data2[$key]))
            ? (is_assoc($data1[$key]) ? \resetOptions($data1[$key], $data2[$key]) : $data2[$key])
            : $data2[$key] ?? $data1[$key];
    }
    return $data;
}

/**
 * 判断是否为自定义索引数组
 * @param array $array
 * @return bool
 */
function is_assoc(array $array): bool
{
    if (empty($array)) return false;
    return array_keys($array) !== range(0, count($array) - 1);
}

/**
 * 二维数组排序
 * @param $arr
 * @param $keys
 * @param bool $desc
 * @return array
 */
function array_sort($arr, $keys, bool $desc): array
{
    $key_value = $new_array = array();
    foreach ($arr as $k => $v) {
        $key_value[$k] = $v[$keys];
    }
    if ($desc) {
        arsort($key_value);
    } else {
        asort($key_value);
    }
    reset($key_value);
    foreach ($key_value as $k => $v) {
        $new_array[$k] = $arr[$k];
    }
    return $new_array;
}

/**
 * 隐藏敏感字符
 * @param $value
 * @return string
 */
function substr_cut($value): string
{
    $strlen = mb_strlen($value, 'utf-8');
    if ($strlen <= 1) return $value;
    $firstStr = mb_substr($value, 0, 1, 'utf-8');
    $lastStr = mb_substr($value, -1, 1, 'utf-8');
    return $strlen == 2 ? $firstStr . str_repeat('*', $strlen - 1) : $firstStr . str_repeat("*", $strlen - 2) . $lastStr;
}

/**
 * 获取当前系统版本号
 * @return string
 * @throws BaseException
 */
function get_version(): string
{
    return \cores\library\Version::getVersion();
}

/**
 * 获取全局唯一标识符
 * @param bool $trim
 * @return string
 */
function get_guid_v4(bool $trim = true): string
{
    // Windows
    if (function_exists('com_create_guid') === true) {
        $charid = com_create_guid();
        return $trim ? trim($charid, '{}') : $charid;
    }
    // OSX/Linux
    if (function_exists('openssl_random_pseudo_bytes') === true) {
        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);    // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);    // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
    // Fallback (PHP 4.2+)
    mt_srand(intval((double)microtime() * 10000));
    $charid = strtolower(md5(uniqid((string)rand(), true)));
    $hyphen = chr(45);                  // "-"
    $lbrace = $trim ? "" : chr(123);    // "{"
    $rbrace = $trim ? "" : chr(125);    // "}"
    return $lbrace .
        substr($charid, 0, 8) . $hyphen .
        substr($charid, 8, 4) . $hyphen .
        substr($charid, 12, 4) . $hyphen .
        substr($charid, 16, 4) . $hyphen .
        substr($charid, 20, 12) .
        $rbrace;
}

/**
 * 时间戳转换日期
 * @param int|string $timeStamp 时间戳
 * @param bool $withTime 是否关联时间
 * @return false|string
 */
function format_time($timeStamp, bool $withTime = true)
{
    $format = 'Y-m-d';
    $withTime && $format .= ' H:i:s';
    return $timeStamp ? date($format, $timeStamp) : '';
}

/**
 * 左侧填充0
 * @param string $value
 * @param int $padLength
 * @return string
 */
function pad_left(string $value, int $padLength = 2): string
{
    return str_pad($value, $padLength, '0', STR_PAD_LEFT);
}

/**
 * 重写trim方法 (解决int类型过滤后后变为string类型)
 * @param $str
 * @return string|void
 */
function my_trim($str)
{
    return is_string($str) ? trim($str) : $str;
}

/**
 * 重写htmlspecialchars方法 (解决int类型过滤后后变为string类型)
 * @param $string
 * @return string|void
 */
function my_htmlspecialchars($string)
{
    return is_string($string) ? htmlspecialchars($string, ENT_COMPAT) : $string;
}

/**
 * 过滤emoji表情
 * @param $text
 * @return null|string|string[]
 */
function filter_emoji($text)
{
    if (!is_string($text)) {
        return $text;
    }
    // 此处的preg_replace用于过滤emoji表情
    // 如需支持emoji表情, 需将mysql的编码改为utf8mb4
    return preg_replace('/[\xf0-\xf7].{3}/', '', $text);
}

/**
 * 根据指定长度截取字符串
 * @param $str
 * @param int $length
 * @return bool|string
 */
function str_substr($str, int $length = 30)
{
    if (strlen($str) > $length) {
        $str = mb_substr($str, 0, $length);
    }
    return $str;
}

/**
 * 结束执行
 */
function app_end()
{
    if (\request()->isCli()) {
        exit(PHP_EOL);
    }
    throw new HttpResponseException(Response::create());
}

/**
 * 当前是否为调试模式
 * @return bool
 */
function is_debug(): bool
{
    return (bool)Env::instance()->get('APP_DEBUG');
}

/**
 * 文本左斜杠转换为右斜杠
 * @param string $string
 * @return string
 */
function convert_left_slash(string $string): string
{
    return str_replace('\\', '/', $string);
}

/**
 * 隐藏手机号中间四位 13012345678 -> 130****5678
 * @param string $mobile 手机号
 * @return string
 */
function hide_mobile(string $mobile): string
{
    return substr_replace($mobile, '****', 3, 4);
}

/**
 * 获取当前登录的商城ID
 * @return int $storeId
 */
function getStoreId(): int
{
    return 10001;
}

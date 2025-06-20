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

namespace app\admin\controller\setting;

use think\response\Json;
use app\common\library\helper;
use app\admin\controller\Controller;

/**
 * 环境检测
 * Class Science
 * @package app\admin\controller\setting
 */
class Science extends Controller
{
    /**
     * 环境检测
     * @return Json
     */
    public function info(): Json
    {
        return $this->renderSuccess([
            'scienceInfo' => [
                'server' => $this->server(),        // 服务器信息
                'phpinfo' => $this->phpinfo(),      // PHP环境要求
                'writeable' => $this->writeable(),  // 目录权限监测
            ]
        ]);
    }

    /**
     * 服务器信息
     * @return array
     */
    private function server(): array
    {
        return [
            [
                'name' => '服务器操作系统',
                'key' => 'system',
                'value' => PHP_OS,
                'status' => PHP_SHLIB_SUFFIX === 'dll' ? 'warning' : 'normal',
                'remark' => '建议使用 Linux 系统以提升程序性能'
            ],
            [
                'name' => 'Web服务器环境',
                'key' => 'webserver',
                'value' => $this->request->server('SERVER_SOFTWARE'),
                'status' => PHP_SAPI === 'isapi' ? 'warning' : 'normal',
                'remark' => '建议使用 Apache 或 Nginx 以提升程序性能'
            ],
            [
                'name' => 'PHP版本',
                'key' => 'php',
                'value' => PHP_VERSION,
                'status' => version_compare(PHP_VERSION, '7.4.0') === -1 ? 'danger' : 'normal',
                'remark' => 'PHP版本必须为 7.4.0 及以上'
            ],
            [
                'name' => 'PHP运行位数',
                'key' => 'architecture',
                'value' => (PHP_INT_SIZE === 4 ? '32' : '64') . '位',
                'status' => PHP_INT_SIZE === 4 ? 'warning' : 'normal',
                'remark' => '建议使用 64位 PHP以提升程序性能'
            ],
//            [
//                'name' => '文件上传功能',
//                'key' => 'file_uploads',
//                'value' => !ini_get('file_uploads') ? '关闭' : '开启',
//                'status' => !ini_get('file_uploads') ? 'danger' : 'normal',
//                'remark' => '文件上传必须开启 file_uploads'
//            ],
            [
                'name' => '文件上传最大值',
                'key' => 'upload_max_filesize',
                'value' => ini_get('upload_max_filesize'),
                'status' => $this->compareBytes(ini_get('upload_max_filesize'), '30m') ? 'danger' : 'normal',
                'remark' => '不能小于30MB；请修改php.ini中upload_max_filesize'
            ],
            [
                'name' => 'POST数据最大值',
                'key' => 'post_max_size',
                'value' => ini_get('post_max_size'),
                'status' => $this->compareBytes(ini_get('post_max_size'), '30m') ? 'danger' : 'normal',
                'remark' => '不能小于30MB；请修改php.ini中post_max_size'
            ],
            [
                'name' => '程序运行目录',
                'key' => 'root_path',
                'value' => str_replace('\\', '/', root_path()),
                'status' => 'normal',
                'remark' => ''
            ],
        ];
    }

    /**
     * PHP环境要求
     * @return string[][]
     */
    private function phpinfo(): array
    {
        return [
            [
                'name' => 'PHP版本',
                'key' => 'php_version',
                'value' => '7.4.0',
                'status' => version_compare(PHP_VERSION, '7.4.0') === -1 ? 'danger' : 'normal',
                'remark' => 'PHP版本必须为 7.4.0'
            ],
            [
                'name' => 'Mysqlnd',
                'key' => 'mysqlnd',
                'value' => '支持',
                'status' => extension_loaded('mysqlnd') ? 'normal' : 'danger',
                'remark' => '您的PHP环境不支持mysqlnd, 系统无法正常运行'
            ],
//            [
//                'name' => 'Redis',
//                'key' => 'redis',
//                'value' => '支持',
//                'status' => extension_loaded('redis') ? 'normal' : 'warning',
//                'remark' => '您的PHP环境不支持redis, 系统无法使用队列服务'
//            ],
            [
                'name' => 'ZIP',
                'key' => 'zip',
                'value' => '支持',
                'status' => extension_loaded('zip') ? 'normal' : 'warning',
                'remark' => '您的PHP环境不支持zip, 系统无法使用zip压缩文件'
            ],
            [
                'name' => 'CURL',
                'key' => 'curl',
                'value' => '支持',
                'status' => extension_loaded('curl') && function_exists('curl_init') ? 'normal' : 'danger',
                'remark' => '您的PHP环境不支持CURL, 系统无法正常运行'
            ],
            [
                'name' => 'JSON',
                'key' => 'json',
                'value' => '支持',
                'status' => extension_loaded('json') ? 'normal' : 'danger',
                'remark' => '您的PHP环境不支持JSON, 系统无法正常运行'
            ],
            [
                'name' => 'Fileinfo',
                'key' => 'fileinfo',
                'value' => '支持',
                'status' => extension_loaded('fileinfo') ? 'normal' : 'danger',
                'remark' => '您的PHP环境不支持fileinfo, 系统无法上传文件'
            ],
            [
                'name' => 'OpenSSL',
                'key' => 'openssl',
                'value' => '支持',
                'status' => extension_loaded('openssl') ? 'normal' : 'danger',
                'remark' => '没有启用OpenSSL, 将无法访问微信平台的接口'
            ],
            [
                'name' => 'PDO',
                'key' => 'pdo',
                'value' => '支持',
                'status' => extension_loaded('PDO') && extension_loaded('pdo_mysql') ? 'normal' : 'danger',
                'remark' => '您的PHP环境不支持PDO, 系统无法正常运行'
            ],
            [
                'name' => 'GD',
                'key' => 'gd',
                'value' => '支持',
                'status' => extension_loaded('gd') ? 'normal' : 'danger',
                'remark' => '您的PHP环境不支持GD, 系统无法正常生成图片'
            ],
            [
                'name' => 'BCMath',
                'key' => 'bcmath',
                'value' => '支持',
                'status' => extension_loaded('bcmath') ? 'normal' : 'danger',
                'remark' => '您的PHP环境不支持BCMath, 系统无法正常运行'
            ],
            [
                'name' => 'Mbstring',
                'key' => 'mbstring',
                'value' => '支持',
                'status' => extension_loaded('mbstring') ? 'normal' : 'danger',
                'remark' => '您的PHP环境不支持mbstring, 系统无法正常运行'
            ],
            [
                'name' => 'SimpleXML',
                'key' => 'simplexML',
                'value' => '支持',
                'status' => extension_loaded('SimpleXML') ? 'normal' : 'danger',
                'remark' => '您的PHP环境不支持SimpleXML, 系统无法解析xml 无法使用微信支付'
            ],
        ];
    }

    /**
     * 目录权限监测
     * @return array[]
     */
    private function writeable(): array
    {
        $paths = [
            'data' => realpath(data_path()) . '/',
            'uploads' => realpath(web_path()) . '/uploads/',
            'downloads' => realpath(web_path()) . '/downloads/',
            'temp' => realpath(web_path()) . '/temp/',
        ];
        return [
            [
                'name' => '系统数据目录',
                'key' => 'data',
                'value' => str_replace('\\', '/', $paths['data']),
                'status' => helper::checkWriteable($paths['data']) ? 'normal' : 'danger',
                'remark' => '目录不可写，系统将无法正常写入文件'
            ],
            [
                'name' => '文件上传目录',
                'key' => 'uploads',
                'value' => str_replace('\\', '/', $paths['uploads']),
                'status' => helper::checkWriteable($paths['uploads']) ? 'normal' : 'danger',
                'remark' => '目录不可写，系统将无法正常上传文件'
            ],
            [
                'name' => '文件下载目录',
                'key' => 'downloads',
                'value' => str_replace('\\', '/', $paths['downloads']),
                'status' => helper::checkWriteable($paths['downloads']) ? 'normal' : 'danger',
                'remark' => '目录不可写，系统将无法正常上传文件'
            ],
            [
                'name' => '临时文件目录',
                'key' => 'temp',
                'value' => str_replace('\\', '/', $paths['temp']),
                'status' => helper::checkWriteable($paths['temp']) ? 'normal' : 'danger',
                'remark' => '目录不可写，系统将无法正常写入文件'
            ],
        ];
    }

    /**
     * 比较数据大小
     * @param string $size1
     * @param string $size2
     * @return bool
     */
    private function compareBytes(string $size1, string $size2): bool
    {
        $size1 = helper::convertToBytes($size1);
        $size2 = helper::convertToBytes($size2);
        return $size1 < $size2;
    }
}

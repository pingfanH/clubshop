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

namespace app\common\library;

use think\facade\Filesystem;
use cores\exception\BaseException;

/**
 * 写入文件到local（主要处理用户上传的excel文件）
 * Class Lock
 * @package app\common\library
 */
class FileLocal
{
    const DISK = 'local';

    /**
     * 执行文件写入
     * @param $file
     * @param string $dirName 文件夹名称
     * @param int|null $storeId 商城ID
     * @return string
     * @throws BaseException
     */
    public static function writeFile(\think\File $file, string $dirName = '', int $storeId = null): string
    {
        // 文件目录路径
        $dirPath = "{$dirName}/{$storeId}";
        // 生成文件名
        $hash = str_substr(md5(get_guid_v4()), 32);
        $fileName = $hash . '.' . $file->extension();
        // 写入到本地服务器
        $path = Filesystem::disk(self::DISK)->putFileAs($dirPath, $file, $fileName);
        empty($path) && throwError('很抱歉，文件写入失败');
        // 返回文件完整路径
        return runtime_root_path() . self::DISK . "/{$path}";
    }
}
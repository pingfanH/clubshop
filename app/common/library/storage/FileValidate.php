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

namespace app\common\library\storage;

/**
 * 文件上传验证类
 * Class FileValidate
 * @package app\common\library\storage
 */
class FileValidate extends \think\Validate
{
    // 验证规则
    protected $rule = [
        // 图片文件: jpg,jpeg,png,bmp,gif
        // 文件大小: 2MB = (1024 * 1024 * 2) = 2097152 字节
        'image' => 'filesize:2097152|fileExt:jpg,jpeg,png,bmp,gif',

        // 视频文件: mp4
        // 文件大小: 20MB = (1024 * 1024 * 20) = 20971520 字节
        'video' => 'filesize:20971520|fileExt:mp4',
    ];

    // 错误提示信息
    protected $message = [
        'image.filesize' => '图片文件大小不能超出2MB',
        'image.fileExt' => '图片文件扩展名有误',
        'video.filesize' => '视频文件大小不能超出20MB',
        'video.fileExt' => '视频文件扩展名有误',
    ];

    // 验证场景
    protected $scene = [
        'image' => ['image'],
        'video' => ['video'],
    ];
}

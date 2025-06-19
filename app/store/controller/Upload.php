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

namespace app\store\controller;

use think\response\Json;
use app\store\service\Upload as UploadService;
use app\common\enum\file\FileType as FileTypeEnum;

/**
 * 文件库管理
 * Class Upload
 * @package app\store\controller
 */
class Upload extends Controller
{
    /**
     * 图片上传接口
     * @param int $groupId 文件库分组ID
     * @return Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function image(int $groupId = 0): Json
    {
        // 执行文件上传
        $service = new UploadService();
        if (!$service->upload(FileTypeEnum::IMAGE, $groupId)) {
            return $this->renderError('文件上传失败：' . $service->getError());
        }
        // 图片上传成功
        return $this->renderSuccess(['fileInfo' => $service->getFileInfo()], '文件上传成功');
    }

    /**
     * 视频上传接口
     * @param int $groupId 文件库分组ID
     * @return Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function video(int $groupId = 0): Json
    {
        // 执行文件上传
        $service = new UploadService();
        if (!$service->upload(FileTypeEnum::VIDEO, $groupId)) {
            return $this->renderError('文件上传失败：' . $service->getError());
        }
        // 图片上传成功
        return $this->renderSuccess(['fileInfo' => $service->getFileInfo()], '文件上传成功');
    }
}

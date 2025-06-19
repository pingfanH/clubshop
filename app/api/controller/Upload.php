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

namespace app\api\controller;

use think\response\Json;
use app\api\service\Upload as UploadService;
use app\common\enum\file\FileType as FileTypeEnum;

/**
 * 文件上传管理
 * Class Upload
 * @package app\api\controller
 */
class Upload extends Controller
{
    /**
     * 图片上传接口
     * @param bool $checkLogin 是否验证登录
     * @return Json
     * @throws \cores\exception\BaseException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function image(bool $checkLogin = true): Json
    {
        // 执行文件上传
        $service = new UploadService();
        if (!$service->upload(FileTypeEnum::IMAGE, $checkLogin)) {
            return $this->renderError('文件上传失败：' . $service->getError());
        }
        // 图片上传成功
        return $this->renderSuccess(['fileInfo' => $service->getFileInfo()], '文件上传成功');
    }
}

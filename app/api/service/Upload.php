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

namespace app\api\service;

use app\api\model\Setting as SettingModel;
use app\api\model\UploadFile as UploadFileModel;
use app\api\service\User as UserService;
use app\common\enum\Setting as SettingEnum;
use app\common\enum\file\FileType as FileTypeEnum;
use app\common\library\storage\Driver as StorageDriver;
use app\common\service\BaseService;
use cores\exception\BaseException;

/**
 * 文件上传管理
 * Class Upload
 * @package app\service\controller
 */
class Upload extends BaseService
{
    /**
     * 文件上传场景
     */
    const UPLOAD_SCENE_ENUM = [
        FileTypeEnum::IMAGE => 'image',
        FileTypeEnum::VIDEO => 'video',
    ];

    // 用户上传文件的file名称
    const FORM_NAME = 'file';

    // 文件信息 (上传后)
    private array $fileInfo;

    /**
     * 文件上传（用户提交上传）
     * @param int $fileType 文件类型 image和video
     * @param bool $checkLogin 是否验证登录
     * @return bool
     * @throws BaseException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function upload(int $fileType, bool $checkLogin = true): bool
    {
        $userId = $checkLogin && UserService::isLogin(true) ? UserService::getCurrentLoginUserId() : 0;
        // 实例化上传驱动
        $storage = $this->getDriver(self::UPLOAD_SCENE_ENUM[$fileType]);
        // 执行文件上传
        if (!$storage->upload()) {
            $this->error = $storage->getError();
            return false;
        }
        // 文件信息
        $fileInfo = $storage->getSaveFileInfo();
        // 添加文件库记录
        return $this->record($fileInfo, $fileType, $userId);
    }

    /**
     * 实例化存储驱动
     * @param string $scene 上传场景 image和video
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getDriver(string $scene)
    {
        $config = $this->getConfig();
        $storage = new StorageDriver($config);
        // 设置上传文件的信息
        return $storage->setUploadFile(self::FORM_NAME)
            ->setRootDirName((string)$this->storeId)
            ->setValidationScene($scene);
    }

    /**
     * 添加文件库记录
     * @param array $fileInfo 文件信息
     * @param int $fileType 文件类型 image和video
     * @param int $userId 用户ID
     * @return bool
     */
    private function record(array $fileInfo, int $fileType, int $userId = 0): bool
    {
        // 添加文件库记录
        $model = new UploadFileModel;
        $model->add($fileInfo, $fileType, $userId);
        $this->fileInfo = $model->toArray();
        return true;
    }

    /**
     * 文件信息 (上传后)
     * @return array
     */
    public function getFileInfo(): array
    {
        return $this->fileInfo;
    }

    /**
     * 获取存储配置信息
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getConfig()
    {
        return SettingModel::getItem(SettingEnum::STORAGE);
    }
}
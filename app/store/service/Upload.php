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

namespace app\store\service;

use app\store\model\Setting as SettingModel;
use app\store\model\UploadFile as UploadFileModel;
use app\common\enum\Setting as SettingEnum;
use app\common\enum\file\FileType as FileTypeEnum;
use app\common\library\storage\Driver as StorageDriver;
use app\common\service\BaseService;

/**
 * 文件上传服务类
 * Class Upload
 * @package app\store\service
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
    const FORM_NAME = 'iFile';

    /**
     * 上传成功的文件信息
     * @var array
     */
    private array $fileInfo = [];

    /**
     * 文件上传（用户提交上传）
     * @param int $fileType 文件类型 image和video
     * @param int $groupId 分组ID
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function upload(int $fileType, int $groupId = 0): bool
    {
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
        return $this->record($fileInfo, $fileType, $groupId);
    }

    /**
     * 文件上传 (服务器本地上传)
     * @param int $fileType 文件类型 image和video
     * @param string $filePath 文件路径
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function uploadByLocal(int $fileType, string $filePath): bool
    {
        // 实例化上传驱动
        $storage = $this->getDriver(self::UPLOAD_SCENE_ENUM[$fileType], true, $filePath);
        // 执行文件上传
        if (!$storage->upload()) {
            $this->error = $storage->getError();
            return false;
        }
        // 文件信息
        $fileInfo = $storage->getSaveFileInfo();
        // 添加文件库记录
        return $this->record($fileInfo, $fileType);
    }

    /**
     * 文件上传 (保存外链)
     * @param int $fileType
     * @param string $fileUrl
     * @return bool
     */
    public function uploadByExternal(int $fileType, string $fileUrl): bool
    {
        // 文件信息
        $parseUrl = parse_url($fileUrl);
        $fileInfo = [
            'storage' => 'external',
            'domain' => "{$parseUrl['scheme']}://{$parseUrl['host']}",
            'file_name' => basename($parseUrl['path']),
            'file_path' => ltrim($parseUrl['path'], '/'),
            'file_size' => 0,
            'file_ext' => pathinfo($fileUrl)['extension'],
        ];
        // 添加文件库记录
        return $this->record($fileInfo, $fileType);
    }

    /**
     * 添加文件库记录
     * @param array $fileInfo 文件信息
     * @param int $fileType 文件类型 image和video
     * @param int $groupId 分组ID
     * @return bool
     */
    private function record(array $fileInfo, int $fileType, int $groupId = 0): bool
    {
        // 添加文件库记录
        $model = new UploadFileModel;
        $model->add($fileInfo, $fileType, $groupId, $this->getStoreId());
        $this->fileInfo = $model->toArray();
        return true;
    }

    /**
     * 上传成功的文件信息
     * @return array
     */
    public function getFileInfo(): array
    {
        return $this->fileInfo;
    }

    /**
     * 实例化上传驱动
     * @param string $scene 上传场景 image和video
     * @param bool $isReal 是否为本地服务器上传（反之为用户提交上传）
     * @param string $filePath 文件路径
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getDriver(string $scene, bool $isReal = false, string $filePath = '')
    {
        // 获取文件上传设置
        $config = $this->getConfig();
        // 实例化存储驱动
        $storage = new StorageDriver($config);
        // 判断文件来源是用户上传或真实路径
        $storage = $isReal ? $storage->setUploadFileByReal($filePath) : $storage->setUploadFile(self::FORM_NAME);
        // 设置上传文件的信息
        return $storage->setRootDirName((string)$this->getStoreId())
            ->setValidationScene($scene);
    }

    /**
     * 获取文件上传设置
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getConfig()
    {
        return SettingModel::getItem(SettingEnum::STORAGE, $this->getStoreId());
    }
}
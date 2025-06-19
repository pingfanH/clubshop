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

namespace app\api\model;

use app\common\model\UploadFile as UploadFileModel;

/**
 * 文件库模型
 * Class UploadFile
 * @package app\api\model
 */
class UploadFile extends UploadFileModel
{
    /**
     * 隐藏字段
     * @var array
     */
    protected $hidden = [
        'file_path',
        'file_name',
        'group_id',
        'channel',
        'storage',
        'domain',
        'file_size',
        'file_ext',
        'cover',
        'uploader_id',
        'is_recycle',
        'is_delete',
        'store_id',
        'create_time',
        'update_time',
    ];

    /**
     * 添加新记录
     * @param array $data 文件信息
     * @param int $fileType 文件类型
     * @param int $userId 用户ID
     * @return bool
     */
    public function add(array $data, int $fileType, int $userId): bool
    {
        return $this->save([
            'channel' => 20,
            'storage' => $data['storage'],
            'domain' => $data['domain'],
            'file_name' => $data['file_name'],
            'file_path' => $data['file_path'],
            'file_size' => $data['file_size'],
            'file_ext' => $data['file_ext'],
            'file_type' => $fileType,
            'uploader_id' => $userId,
            'store_id' => self::$storeId
        ]);
    }

    /**
     * 设置上传者用户ID
     * @param int $fileId 文件ID
     * @param int $userId 上传者用户ID
     * @return bool
     */
    public static function setUploaderId(int $fileId, int $userId): bool
    {
        $model = static::detail($fileId);
        if (empty($model) || $model['uploader_id'] > 0) {
            return false;
        }
        return $model->save(['uploader_id' => $userId]);
    }
}

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
use cores\library\Version;
use app\store\model\UploadFile as UploadFileModel;

/**
 * 文件库管理
 * Class Files
 * @package app\store\controller
 */
class Files extends Controller
{
    /**
     * 文件列表
     * @return Json
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DbException
     */
    public function list(): Json
    {
        $this->env();
        $model = new UploadFileModel;
        $list = $model->getList($this->request->param());
        return $this->renderSuccess(compact('list'));
    }

    /**
     * 编辑文件
     * @param int $fileId
     * @return Json
     */
    public function edit(int $fileId): Json
    {
        // 文件详情
        $model = UploadFileModel::detail($fileId);
        // 更新记录
        if ($model->edit($this->postForm())) {
            return $this->renderSuccess('更新成功');
        }
        return $this->renderError($model->getError() ?: '更新失败');
    }

    /**
     * 删除文件(批量)
     * @param array $fileIds 文件id集
     * @return Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function delete(array $fileIds): Json
    {
        $model = new UploadFileModel;
        if (!$model->setDelete($fileIds)) {
            return $this->renderError($model->getError() ?: '操作失败');
        }
        return $this->renderSuccess('操作成功');
    }

    /**
     * 移动文件到指定分组(批量)
     * @param int $groupId
     * @param array $fileIds
     * @return Json
     */
    public function moveGroup(int $groupId, array $fileIds): Json
    {
        $model = new UploadFileModel;
        if (!$model->moveGroup($groupId, $fileIds)) {
            return $this->renderError($model->getError() ?: '操作失败');
        }
        return $this->renderSuccess('操作成功');
    }

    /**
     * 临时方法：环境检测并删除废弃的库文件
     * 文件：vendor/topthink/framework/src/think/Filesystem.php
     * 文件：vendor/topthink/framework/src/think/facade/Filesystem.php
     * 文件：vendor/topthink/framework/tests/FilesystemTest.php
     * 目录：vendor/topthink/framework/src/think/filesystem
     * @return void
     * @throws \cores\exception\BaseException
     */
    private function env()
    {
        // 判断当前版本小于2.0.7则不执行
        if (Version::compare(Version::getVersion(), '2.0.7') === -1) {
            return;
        }
        // 要删除的文件列表
        $files = [
            'vendor/topthink/framework/src/think/Filesystem.php',
            'vendor/topthink/framework/src/think/facade/Filesystem.php',
            'vendor/topthink/framework/tests/FilesystemTest.php'
        ];
        foreach ($files as $file) {
            $filePath = root_path() . $file;
            file_exists($filePath) && unlink($filePath);
        }
        // 要删除的目录列表
        $folders = ['vendor/topthink/framework/src/think/filesystem/'];
        foreach ($folders as $folder) {
            $folderPath = root_path() . $folder;
            is_dir($folderPath) && $this->deleteFolder($folderPath);
        }
    }

    /**
     * 临时方法：递归删除指定目录下所有文件
     * @param $path
     * @return void
     */
    private function deleteFolder($path): void
    {
        if (!is_dir($path)) {
            return;
        }
        // 扫描一个文件夹内的所有文件夹和文件
        foreach (scandir($path) as $val) {
            // 排除目录中的.和..
            if (!in_array($val, ['.', '..', '.gitignore'])) {
                // 如果是目录则递归子目录，继续操作
                if (is_dir($path . $val)) {
                    // 子目录中操作删除文件夹和文件
                    $this->deleteFolder($path . $val . '/');
                    // 目录清空后删除空文件夹
                    rmdir($path . $val . '/');
                } else {
                    // 如果是文件直接删除
                    unlink($path . $val);
                }
            }
        }
    }
}

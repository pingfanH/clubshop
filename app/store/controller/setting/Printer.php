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

namespace app\store\controller\setting;

use think\response\Json;
use app\store\controller\Controller;
use app\store\model\Printer as PrinterModel;

/**
 * 小票打印机管理
 * Class Printer
 * @package app\store\controller\setting
 */
class Printer extends Controller
{
    /**
     * 打印机列表
     * @return Json
     * @throws \think\db\exception\DbException
     */
    public function list(): Json
    {
        $model = new PrinterModel;
        $list = $model->getList($this->request->param());
        return $this->renderSuccess(compact('list'));
    }

    /**
     * 获取打印机记录
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function all(): Json
    {
        $list = PrinterModel::getAll();
        return $this->renderSuccess(compact('list'));
    }

    /**
     * 添加打印机
     * @return Json
     */
    public function add(): Json
    {
        // 新增记录
        $model = new PrinterModel;
        if ($model->add($this->postForm())) {
            return $this->renderSuccess('添加成功');
        }
        return $this->renderError($model->getError() ?: '添加失败');
    }

    /**
     * 编辑打印机
     * @param int $printerId
     * @return Json
     */
    public function edit(int $printerId): Json
    {
        // 打印机详情
        $model = PrinterModel::detail($printerId);
        // 更新记录
        if ($model->edit($this->postForm())) {
            return $this->renderSuccess('更新成功');
        }
        return $this->renderError($model->getError() ?: '更新失败');
    }

    /**
     * 删除打印机
     * @param int $printerId
     * @return Json
     */
    public function delete(int $printerId): Json
    {
        // 打印机详情
        $model = PrinterModel::detail($printerId);
        if (!$model->setDelete()) {
            return $this->renderError($model->getError() ?: '删除失败');
        }
        return $this->renderSuccess('删除成功');
    }
}

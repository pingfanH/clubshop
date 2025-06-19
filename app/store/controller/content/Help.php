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

namespace app\store\controller\content;

use think\response\Json;
use app\store\controller\Controller;
use app\store\model\Help as HelpModel;

/**
 * 帮助中心
 * Class Help
 * @package app\store\controller\content
 */
class Help extends Controller
{
    /**
     * 获取列表记录
     * @return Json
     * @throws \think\db\exception\DbException
     */
    public function list(): Json
    {
        $model = new HelpModel;
        $list = $model->getList();
        return $this->renderSuccess(compact('list'));
    }

    /**
     * 添加帮助
     * @return Json
     */
    public function add(): Json
    {
        // 新增记录
        $model = new HelpModel;
        if ($model->add($this->postForm())) {
            return $this->renderSuccess('添加成功');
        }
        return $this->renderError($model->getError() ?: '添加失败');
    }

    /**
     * 更新帮助
     * @param int $helpId
     * @return Json
     */
    public function edit(int $helpId): Json
    {
        // 帮助详情
        $model = HelpModel::detail($helpId);
        // 更新记录
        if ($model->edit($this->postForm())) {
            return $this->renderSuccess('更新成功');
        }
        return $this->renderError($model->getError() ?: '更新失败');
    }

    /**
     * 删除帮助
     * @param int $helpId
     * @return Json
     */
    public function delete(int $helpId): Json
    {
        // 帮助详情
        $model = HelpModel::detail($helpId);
        if (!$model->setDelete()) {
            return $this->renderError($model->getError() ?: '删除失败');
        }
        return $this->renderSuccess('删除成功');
    }
}

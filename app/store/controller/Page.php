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
use app\store\model\Page as PageModel;

/**
 * 店铺页面管理
 * Class Page
 * @package app\store\controller\wxapp
 */
class Page extends Controller
{
    /**
     * 页面列表
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function list(): Json
    {
        $model = new PageModel;
        $list = $model->getList($this->request->param());
        return $this->renderSuccess(compact('list'));
    }

    /**
     * 页面设计默认数据
     * @return Json
     */
    public function defaultData(): Json
    {
        $model = new PageModel;
        return $this->renderSuccess([
            'page' => $model->getDefaultPage(),
            'items' => $model->getDefaultItems()
        ]);
    }

    /**
     * 页面详情
     * @param int $pageId
     * @return Json
     */
    public function detail(int $pageId): Json
    {
        $detail = PageModel::detail($pageId);
        return $this->renderSuccess(compact('detail'));
    }

    /**
     * 新增页面
     * @return Json
     */
    public function add(): Json
    {
        $model = new PageModel;
        if (!$model->add($this->postForm('form', false))) {
            return $this->renderError($model->getError() ?: '添加失败');
        }
        return $this->renderSuccess('添加成功');
    }

    /**
     * 编辑页面
     * @param int $pageId
     * @return Json
     */
    public function edit(int $pageId): Json
    {
        $model = PageModel::detail($pageId);
        if (!$model->edit($this->postForm('form', false))) {
            return $this->renderError($model->getError() ?: '更新失败');
        }
        return $this->renderSuccess('更新成功');
    }

    /**
     * 删除页面
     * @param int $pageId
     * @return Json
     */
    public function delete(int $pageId): Json
    {
        // 帮助详情
        $model = PageModel::detail($pageId);
        if (!$model->setDelete()) {
            return $this->renderError($model->getError() ?: '删除失败');
        }
        return $this->renderSuccess('删除成功');
    }

    /**
     * 设置默认首页
     * @param int $pageId
     * @return Json
     */
    public function setHome(int $pageId): Json
    {
        // 帮助详情
        $model = PageModel::detail($pageId);
        if (!$model->setHome()) {
            return $this->renderError($model->getError() ?: '设置失败');
        }
        return $this->renderSuccess('设置成功');
    }
}

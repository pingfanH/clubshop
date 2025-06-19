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

namespace app\store\controller\store;

use think\response\Json;
use app\store\controller\Controller;
use app\store\model\store\Address as AddressModel;

/**
 * 商家地址管理
 * Class Address
 * @package app\store\controller\setting
 */
class Address extends Controller
{
    /**
     * 列表记录
     * @return Json
     * @throws \think\db\exception\DbException
     */
    public function list(): Json
    {
        $model = new AddressModel;
        $list = $model->getList($this->request->param());
        return $this->renderSuccess(compact('list'));
    }

    /**
     * 全部记录
     * @return Json
     * @throws \think\db\exception\DbException
     */
    public function all(): Json
    {
        $model = new AddressModel;
        $list = $model->getAll($this->request->param());
        return $this->renderSuccess(compact('list'));
    }

    /**
     * 添加记录
     * @return Json
     */
    public function add(): Json
    {
        // 新增记录
        $model = new AddressModel;
        if ($model->add($this->postForm())) {
            return $this->renderSuccess('添加成功');
        }
        return $this->renderError($model->getError() ?: '添加失败');
    }

    /**
     * 编辑记录
     * @param int $addressId
     * @return Json
     */
    public function edit(int $addressId): Json
    {
        // 详情记录
        $model = AddressModel::detail($addressId);
        // 更新记录
        if ($model->edit($this->postForm())) {
            return $this->renderSuccess('更新成功');
        }
        return $this->renderError($model->getError() ?: '更新失败');
    }

    /**
     * 删除记录
     * @param int $addressId
     * @return Json
     */
    public function delete(int $addressId): Json
    {
        // 详情记录
        $model = AddressModel::detail($addressId);
        if (!$model->remove()) {
            return $this->renderError($model->getError() ?: '删除失败');
        }
        return $this->renderSuccess('删除成功');
    }
}

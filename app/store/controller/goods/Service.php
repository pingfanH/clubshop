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

namespace app\store\controller\goods;

use think\response\Json;
use app\store\controller\Controller;
use app\store\model\goods\Service as ServiceModel;

/**
 * 商品服务与承诺管理
 * Class Service
 * @package app\store\controller\goods
 */
class Service extends Controller
{
    /**
     * 获取列表记录
     * @return Json
     * @throws \think\db\exception\DbException
     */
    public function list(): Json
    {
        $model = new ServiceModel;
        $list = $model->getList($this->request->param());
        return $this->renderSuccess(compact('list'));
    }

    /**
     * 获取全部记录
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function all(): Json
    {
        $model = new ServiceModel;
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
        $model = new ServiceModel;
        if ($model->add($this->postForm())) {
            return $this->renderSuccess('添加成功');
        }
        return $this->renderError($model->getError() ?: '添加失败');
    }

    /**
     * 更新记录
     * @param int $serviceId
     * @return Json
     */
    public function edit(int $serviceId): Json
    {
        // 记录详情
        $model = ServiceModel::detail($serviceId);
        // 更新记录
        if ($model->edit($this->postForm())) {
            return $this->renderSuccess('更新成功');
        }
        return $this->renderError($model->getError() ?: '更新失败');
    }

    /**
     * 删除记录
     * @param int $serviceId
     * @return Json
     * @throws \Exception
     */
    public function delete(int $serviceId): Json
    {
        // 记录详情
        $model = ServiceModel::detail($serviceId);
        if (!$model->remove()) {
            return $this->renderError($model->getError() ?: '删除失败');
        }
        return $this->renderSuccess('删除成功');
    }
}

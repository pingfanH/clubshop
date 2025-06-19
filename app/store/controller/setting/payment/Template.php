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

namespace app\store\controller\setting\payment;

use think\response\Json;
use app\store\controller\Controller;
use app\store\model\PaymentTemplate as PaymentTemplateModel;
use think\db\exception\DbException;

/**
 * 支付模板设置
 * Class Template
 * @package app\store\controller\setting
 */
class Template extends Controller
{
    /**
     * 支付模板列表
     * @return Json
     * @throws DbException
     */
    public function list(): Json
    {
        $model = new PaymentTemplateModel;
        $list = $model->getList();
        return $this->renderSuccess(compact('list'));
    }

    /**
     * 获取全部支付模板
     * @return Json
     * @throws DbException
     */
    public function all(): Json
    {
        $model = new PaymentTemplateModel;
        $list = $model->getAll();
        return $this->renderSuccess(compact('list'));
    }

    /**
     * 支付模板详情
     * @param int $templateId
     * @return Json
     */
    public function detail(int $templateId): Json
    {
        $detail = PaymentTemplateModel::detail($templateId);
        return $this->renderSuccess(compact('detail'));
    }

    /**
     * 添加支付模板
     * @return Json
     * @throws \cores\exception\BaseException
     */
    public function add(): Json
    {
        // 新增记录
        $model = new PaymentTemplateModel;
        if ($model->add($this->postData())) {
            return $this->renderSuccess('添加成功');
        }
        return $this->renderError($model->getError() ?: '添加失败');
    }

    /**
     * 编辑支付模板
     * @param int $templateId
     * @return Json
     * @throws \cores\exception\BaseException
     */
    public function edit(int $templateId): Json
    {
        // 模板详情
        $model = PaymentTemplateModel::detail($templateId);
        // 更新记录
        if ($model->edit($this->postData())) {
            return $this->renderSuccess('更新成功');
        }
        return $this->renderError($model->getError() ?: '更新失败');
    }

    /**
     * 删除模板
     * @param int $templateId
     * @return Json
     */
    public function delete(int $templateId): Json
    {
        // 模板详情
        $model = PaymentTemplateModel::detail($templateId);
        if (!$model->setDelete()) {
            return $this->renderError($model->getError() ?: '删除失败');
        }
        return $this->renderSuccess('删除成功');
    }
}

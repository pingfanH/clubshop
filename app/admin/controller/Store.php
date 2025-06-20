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

namespace app\admin\controller;

use think\response\Json;
use app\admin\model\Store as StoreModel;

/**
 * 商城管理
 * Class Store
 * @package app\admin\controller
 */
class Store extends Controller
{
    /**
     * 强制验证当前访问的控制器方法method
     * @var array
     */
    protected array $methodRules = [
        'index' => 'GET',
        'recycle' => 'GET',
        'add' => 'POST',
        'move' => 'POST',
        'delete' => 'POST',
    ];

    /**
     * 商城列表
     * @return Json
     * @throws \think\db\exception\DbException
     */
    public function index(): Json
    {
        // 商城列表
        $model = new StoreModel;
        $list = $model->getList();
        return $this->renderSuccess(compact('list'));
    }

    /**
     * 新增商城
     * @return Json
     */
    public function add(): Json
    {
        return $this->renderError('很抱歉，免费版暂不支持多开商城');
    }

    /**
     * 回收站列表
     * @return Json
     * @throws \think\db\exception\DbException
     */
    public function recycle(): Json
    {
        // 商城列表
        $model = new StoreModel;
        $list = $model->getList(true);
        return $this->renderSuccess(compact('list'));
    }

    /**
     * 移入回收站
     * @param int $storeId
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function recovery(int $storeId): Json
    {
        // 商城详情
        $model = StoreModel::detail($storeId);
        if (!$model->recycle()) {
            return $this->renderError($model->getError() ?: '操作失败');
        }
        return $this->renderSuccess('操作成功');
    }

    /**
     * 移出回收站
     * @param int $storeId
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function move(int $storeId): Json
    {
        // 商城详情
        $model = StoreModel::detail($storeId);
        if (!$model->recycle(false)) {
            return $this->renderError($model->getError() ?: '操作失败');
        }
        return $this->renderSuccess('操作成功');
    }
}

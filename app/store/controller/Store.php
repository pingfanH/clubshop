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
use app\store\model\Store as StoreModel;

/**
 * 商家中心控制器
 * Class Store
 * @package app\store\controller
 */
class Store extends Controller
{
    /**
     * 获取当前登录的商城信息
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function info(): Json
    {
        // 商城详情
        $model = StoreModel::detail($this->storeId);
        return $this->renderSuccess(['storeInfo' => $model]);
    }

    /**
     * 更新商城信息
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function update(): Json
    {
        // 商城详情
        $model = StoreModel::detail($this->storeId);
        // 更新记录
        if (!$model->edit($this->postForm())) {
            return $this->renderError($model->getError() ?: '更新失败');
        }
        return $this->renderSuccess('更新成功');
    }
}

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
use app\store\model\store\User as StoreUserModel;
use app\store\service\store\User as StoreUserService;
use app\store\service\store\Role as StoreRoleService;

/**
 * 商家用户控制器
 * Class StoreUser
 * @package app\store\controller
 */
class User extends Controller
{
    /**
     * 获取当前登录的用户信息
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function info(): Json
    {
        // 当前登录用户的ID
        $storeUserId = StoreUserService::getLoginUserId();
        return $this->renderSuccess([
            // 用户信息
            'userInfo' => StoreUserModel::detail($storeUserId),
            // 菜单权限
            'roles' => StoreRoleService::getLoginPermissions(),
        ]);
    }

    /**
     * 管理员列表
     * @return Json
     * @throws \think\db\exception\DbException
     */
    public function list(): Json
    {
        $model = new StoreUserModel;
        $list = $model->getList($this->request->param());
        return $this->renderSuccess(compact('list'));
    }

    /**
     * 添加管理员
     * @return Json
     */
    public function add(): Json
    {
        // 新增记录
        $model = new StoreUserModel;
        if ($model->add($this->postForm())) {
            return $this->renderSuccess('添加成功');
        }
        return $this->renderError($model->getError() ?: '添加失败');
    }

    /**
     * 更新管理员
     * @param int $userId
     * @return Json
     */
    public function edit(int $userId): Json
    {
        // 管理员详情
        $model = StoreUserModel::detail($userId);
        // 更新记录
        if ($model->edit($this->postForm())) {
            return $this->renderSuccess('更新成功');
        }
        return $this->renderError($model->getError() ?: '更新失败');
    }

    /**
     * 删除管理员
     * @param int $userId
     * @return Json
     */
    public function delete(int $userId): Json
    {
        // 管理员详情
        $model = StoreUserModel::detail($userId);
        if (!$model->setDelete()) {
            return $this->renderError($model->getError() ?: '删除失败');
        }
        return $this->renderSuccess('删除成功');
    }

    /**
     * 更新当前管理员信息
     * @return Json
     */
    public function renew(): Json
    {
        // 管理员详情
        $model = StoreUserModel::detail(StoreUserService::getLoginUserId());
        // 更新当前管理员信息
        if ($model->renew($this->postForm())) {
            return $this->renderSuccess('更新成功');
        }
        return $this->renderError($model->getError() ?: '更新失败');
    }
}

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
use app\store\model\store\User as StoreUserModel;
use app\store\service\store\User as StoreUserService;

/**
 * 商家后台认证
 * Class Passport
 * @package app\store\controller
 */
class Passport extends Controller
{
    /**
     * 强制验证当前访问的控制器方法method
     * 例: [ 'login' => 'POST' ]
     * @var array
     */
    protected array $methodRules = [
        'login' => 'POST',
        'logout' => 'POST',
    ];

    /**
     * 商家用户登录
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function login(): Json
    {
        $model = new StoreUserModel;
        if (($userInfo = $model->login($this->postData())) === false) {
            return $this->renderError($model->getError() ?: '登录失败');
        }
        return $this->renderSuccess([
            'userId' => (int)$userInfo['store_user_id'],
            'token' => $model->getToken()
        ], '登录成功');
    }

    /**
     * 退出登录
     * @return Json
     */
    public function logout(): Json
    {
        // 清空登录状态
        StoreUserService::logout();
        return $this->renderSuccess('操作成功');
    }
}

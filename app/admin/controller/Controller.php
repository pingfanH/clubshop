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

use cores\BaseController;
use app\admin\service\admin\User as AdminUserService;
use cores\exception\BaseException;

/**
 * 超管后台控制器基类
 * Class Controller
 * @package app\admin\controller
 */
class Controller extends BaseController
{
    // 商家登录信息
    protected $admin;

    // 当前控制器名称
    protected string $controller = '';

    // 当前方法名称
    protected string $action = '';

    // 当前路由uri
    protected string $routeUri = '';

    // 当前路由：分组名称
    protected string $group = '';

    // 登录验证白名单
    protected array $allowAllAction = [
        // 登录页面
        'passport/login',
    ];

    /**
     * 强制验证当前访问的控制器方法method
     * 例: [ 'login' => 'POST' ]
     * @var array
     */
    protected array $methodRules = [];

    /**
     * 后台初始化
     * @return void
     * @throws BaseException
     */
    public function initialize()
    {
        // 设置管理员登录信息
        $this->setAdminInfo();
        // 当前路由信息
        $this->getRouteinfo();
        // 验证登录
        $this->checkLogin();
        // 强制验证当前访问的控制器方法method
        $this->checkMethodRules();
    }

    /**
     * 设置管理员登录信息
     */
    private function setAdminInfo()
    {
        $this->admin = AdminUserService::getLoginInfo();
    }

    /**
     * 解析当前路由参数 （分组名称、控制器名称、方法名）
     */
    protected function getRouteinfo()
    {
        // 控制器名称
        $this->controller = uncamelize($this->request->controller());
        // 方法名称
        $this->action = $this->request->action();
        // 控制器分组 (用于定义所属模块)
        $groupstr = strstr($this->controller, '.', true);
        $this->group = $groupstr !== false ? $groupstr : $this->controller;
        // 当前uri
        $this->routeUri = "{$this->controller}/$this->action";
    }

    /**
     * 验证登录状态
     * @return void
     * @throws BaseException
     */
    private function checkLogin(): void
    {
        // 验证当前请求是否在白名单
        if (in_array($this->routeUri, $this->allowAllAction)) {
            return;
        }
        // 验证登录状态
        if (empty($this->admin) || (int)$this->admin['is_login'] !== 1) {
            throwError('请先登录后再访问', config('status.not_logged'));
        }
    }

    /**
     * 强制验证当前访问的控制器方法method
     * @throws BaseException
     */
    private function checkMethodRules(): void
    {
        if (!isset($this->methodRules[$this->action])) {
            return;
        }
        $methodRule = $this->methodRules[$this->action];
        $currentMethod = $this->request->method();
        if (empty($methodRule)) {
            return;
        }
        if (is_array($methodRule) && in_array($currentMethod, $methodRule)) {
            return;
        }
        if (is_string($methodRule) && $methodRule == $currentMethod) {
            return;
        }
        throwError('illegal request method');
    }
}

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

namespace app\api\controller;

use cores\BaseController;
use app\api\model\User as UserModel;
use app\api\model\Store as StoreModel;
use app\api\service\User as UserService;
use cores\exception\BaseException;
use think\db\exception\DbException;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;

/**
 * API控制器基类
 * Class BaseController
 * @package app\store\controller
 */
class Controller extends BaseController
{
    // 当前商城ID
    protected int $storeId;

    /**
     * API基类初始化
     * @throws BaseException
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    protected function initialize()
    {
        // 当前的商城ID
        $this->getStoreId();
        // 验证当前商城状态
        $this->checkStore();
        // 验证当前客户端状态
        $this->checkClient();
    }

    /**
     * 获取当前的商城ID
     * @throws BaseException
     */
    protected function getStoreId()
    {
        if (!$this->storeId = \getStoreId()) {
            throwError('很抱歉，未找到必要的参数storeId');
        }
    }

    /**
     * 验证当前商城状态
     * @return void
     * @throws BaseException
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private function checkStore(): void
    {
        // 获取当前商城信息
        $store = StoreModel::detail($this->storeId);
        if (empty($store)) {
            throwError('很抱歉，当前商城信息不存在');
        }
        if ($store['is_recycle'] || $store['is_delete']) {
            throwError('很抱歉，当前商城已删除');
        }
    }

    /**
     * 验证当前客户端是否允许访问
     * @return void
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function checkClient()
    {
        $client = getPlatform();
        $settingClass = [
            'MP-WEIXIN' => [
                'name' => '微信小程序端',
                'class' => '\app\api\model\wxapp\Setting',
                'method' => 'checkStatus',
            ],
            'H5' => [
                'name' => 'H5端',
                'class' => '\app\api\model\h5\Setting',
                'method' => 'checkStatus',
            ],
        ];
        if (!isset($settingClass[$client])) {
            return;
        }
        $item = $settingClass[$client];
        if (!class_exists($item['class'])) {
            throwError("很抱歉，当前{$item['name']}不存在");
        }
        if (!call_user_func([$item['class'], $item['method']])) {
            throwError("很抱歉，当前{$item['name']}暂未开启访问");
        }
    }

    /**
     * 获取当前用户信息
     * @param bool $isForce 强制验证登录
     * @return UserModel|bool|null
     * @throws BaseException
     */
    protected function getLoginUser(bool $isForce = true)
    {
        return UserService::getCurrentLoginUser($isForce);
    }
}

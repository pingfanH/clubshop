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

namespace app\store\controller\client\wxapp;

use app\store\controller\Controller;
use app\store\model\wxapp\Setting as SettingModel;
use think\response\Json;

/**
 * 微信小程序设置
 * Class Setting
 * @package app\store\controller\apps\wxapp
 */
class Setting extends Controller
{
    /**
     * 获取微信小程序设置 (指定)
     * @param string $key
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function detail(string $key): Json
    {
        // 获取微信小程序设置
        $detail = SettingModel::getItem($key);
        // 服务端域名
        $domain = $this->request->host(true);
        return $this->renderSuccess(compact('detail', 'domain'));
    }

    /**
     * 更新设置项
     * @param string $key
     * @return Json
     */
    public function update(string $key): Json
    {
        $model = new SettingModel;
        if ($model->edit($key, $this->postForm())) {
            return $this->renderSuccess('更新成功');
        }
        return $this->renderError($model->getError() ?: '更新失败');
    }
}
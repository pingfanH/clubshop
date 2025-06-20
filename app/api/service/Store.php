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

namespace app\api\service;

use app\api\model\Store as StoreModel;
use app\api\service\Client as ClientService;
use app\api\service\Setting as SettingService;
use app\common\service\BaseService;

/**
 * 商城基础信息
 * Class Store
 * @package app\api\service
 */
class Store extends BaseService
{
    /**
     * 获取商城基础信息
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function data(): array
    {
        return [
            // 店铺基本信息
            'storeInfo' => $this->storeInfo(),
            // 当前客户端名称
            'client' => \getPlatform(),
            // 商城设置
            'setting' => $this->setting(),
            // 客户端设置
            'clientData' => $this->getClientData(),
        ];
    }

    /**
     * 客户端公共数据
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getClientData(): array
    {
        $service = new ClientService;
        return $service->getPublic();
    }

    /**
     * 商城设置
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function setting(): array
    {
        $service = new SettingService;
        return $service->getPublic();
    }

    /**
     * 店铺基本信息（名称、简介、logo）
     * @return StoreModel|array|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function storeInfo()
    {
        return StoreModel::getInfo();
    }
}
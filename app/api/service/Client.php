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

use app\api\model\h5\Setting as H5SettingModel;
use app\common\library\helper;
use app\common\service\BaseService;

/**
 * 服务类：客户端公共数据
 * Class Client
 * @package app\api\service
 */
class Client extends BaseService
{
    /**
     * 客户端公共数据
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPublic(): array
    {
        return [
            'h5' => $this->getH5Public(),
        ];
    }

    /**
     * 获取H5端公共数据
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getH5Public(): array
    {
        $values = H5SettingModel::getItem('basic');
        return ['setting' => helper::pick($values, ['enabled', 'baseUrl'])];
    }
}
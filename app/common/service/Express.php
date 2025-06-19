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

namespace app\common\service;

use think\facade\Cache;
use app\common\enum\Setting as SettingEnum;
use app\common\model\store\Setting as SettingModel;
use app\common\library\express\Facade as ExpressFacade;
use cores\exception\BaseException;

/**
 * 服务类：物流管理
 * Class Express
 * @package app\common\service
 */
class Express extends BaseService
{
    /**
     * 物流轨迹查询
     * @param mixed $express 物流公司记录
     * @param string $expressNo 物流单号
     * @param $address
     * @param int $storeId 商城ID
     * @return array
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function traces($express, string $expressNo, $address, int $storeId): array
    {
        // 获取物流查询API配置项
        $config = $this->getTracesConfig($storeId);
        // 物流公司编码
        $code = $config['default'] === 'kd100' ? $express['kuaidi100_code'] : $express['kdniao_code'];
        // 获取缓存的数据
        $cacheIndex = "expressTraces_{$code}_$expressNo";
        if (Cache::has($cacheIndex)) {
            return Cache::get($cacheIndex);
        }
        // 请求API查询物流轨迹
        $result = ExpressFacade::store($config['default'])
            ->setOptions($config['providerConfig'][$config['default']])
            ->query($code, $expressNo, ['phone' => $address['phone']]);
        // 记录缓存, 有效期60分钟
        Cache::set($cacheIndex, $result, 60 * 60);
        return $result;
    }

    /**
     * 获取物流查询API配置项
     * @return mixed
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getTracesConfig(int $storeId)
    {
        // 实例化快递100类
        $config = SettingModel::getItem(SettingEnum::DELIVERY, $storeId);
        if (empty($config['traces']['enable'])) {
            throwError('很抱歉，物流查询功能未开启');
        }
        return $config['traces'];
    }
}
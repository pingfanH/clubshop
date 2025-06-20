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

namespace app\store\model\wxapp;

use think\facade\Cache;
use app\common\model\wxapp\Setting as SettingModel;
use app\common\library\helper;
use app\common\library\wechat\Shipping as WechatShippingApi;
use cores\exception\BaseException;

/**
 * 微信小程序设置模型
 * Class Setting
 * @package app\store\model\wxapp
 */
class Setting extends SettingModel
{
    /**
     * 设置项描述
     * @var array
     */
    private array $describe = ['basic' => '基础设置'];

    /**
     * 更新系统设置
     * @param string $key
     * @param array $values
     * @return bool
     * @throws BaseException
     */
    public function edit(string $key, array $values): bool
    {
        $model = self::detail($key) ?: $this;
        // 删除小程序设置缓存
        Cache::delete('wxapp_setting_' . self::$storeId);
        // 设置消息跳转路径
        $values['enableShipping'] && $this->setMsgJumpPath($values);
        // 保存设置
        return $model->save([
            'key' => $key,
            'describe' => $this->describe[$key],
            'values' => helper::pick($values, ['enabled', 'app_id', 'app_secret', 'enableShipping']),
            'update_time' => time(),
            'store_id' => self::$storeId,
        ]);
    }

    /**
     * 微信小程序 -> 发货信息管理 -> 消息跳转路径设置接口
     * @param array $values
     * @return true
     * @throws BaseException
     */
    private function setMsgJumpPath(array $values): bool
    {
        // 请求API数据
        $WechatShippingApi = new WechatShippingApi($values['app_id'], $values['app_secret']);
        // 处理返回结果
        $response = $WechatShippingApi->setMsgJumpPath([
            'path' => 'pages/order/index?dataType=received'
        ]);
        empty($response) && throwError('微信API请求失败：' . $WechatShippingApi->getError());
        return true;
    }
}
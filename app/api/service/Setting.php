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

use app\api\model\Setting as SettingModel;
use app\common\library\helper;
use app\common\service\BaseService;
use app\common\enum\Setting as SettingEnum;

/**
 * 服务类：商城设置
 * Class Setting
 * @package app\api\service
 */
class Setting extends BaseService
{
    /**
     * 指定的商城公共设置
     * @param string $key
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getPublicItem(string $key): array
    {
        $setting = (new static)->getPublic();
        if (array_key_exists($key, $setting)) {
            return $setting[$key];
        }
        return [];
    }

    /**
     * 商城公共设置
     * 这里的商城设置仅暴露可公开的设置项 例如分类页模板、积分名称
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPublic(): array
    {
        $data = [];
        // 店铺页面风格设置
        $data[SettingEnum::APP_THEME] = $this->getAppTheme();
        // 分类页模板设置
        $data[SettingEnum::PAGE_CATEGORY_TEMPLATE] = $this->getCatTplStyle();
        // 积分设置
        $data[SettingEnum::POINTS] = $this->getPoints();
        // 充值设置
        $data[SettingEnum::RECHARGE] = $this->getRecharge();
        // 注册设置
        $data[SettingEnum::REGISTER] = $this->getRegister();
        // 商城客服设置
        $data[SettingEnum::CUSTOMER] = $this->getCustomer();
        return $data;
    }

    /**
     * 积分设置 (积分名称、积分描述)
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getPoints(): array
    {
        $values = SettingModel::getItem(SettingEnum::POINTS);
        return helper::pick($values, ['points_name', 'describe']);
    }

    /**
     * 积分设置 (积分名称、积分描述)
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getRecharge(): array
    {
        $values = SettingModel::getItem(SettingEnum::RECHARGE);
        return helper::pick($values, ['is_entrance', 'is_custom', 'describe']);
    }

    /**
     * 注册设置 (默认登录方式、是否开启微信小程序授权登录)
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getRegister(): array
    {
        $values = SettingModel::getItem(SettingEnum::REGISTER);
        return helper::pick($values, [
            'registerMethod', 'isManualBind',
            'isOauthMpweixin', 'isOauthMobileMpweixin',
        ]);
    }

    /**
     * 商城客服设置
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getCustomer(): array
    {
        $values = SettingModel::getItem(SettingEnum::CUSTOMER);
        return helper::pick($values, ['enabled', 'provider', 'config']);
    }

    /**
     * 获取分类页模板设置
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getCatTplStyle()
    {
        return SettingModel::getItem(SettingEnum::PAGE_CATEGORY_TEMPLATE);
    }

    /**
     * 获取店铺页面风格设置
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getAppTheme()
    {
        return SettingModel::getItem(SettingEnum::APP_THEME)['data'];
    }
}
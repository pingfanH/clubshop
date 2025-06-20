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

namespace app\api\service\user;

use app\api\model\UserOauth as UserOauthModel;
use app\api\model\wxapp\Setting as WxappSettingModel;
use app\common\service\BaseService;
use app\common\library\wechat\WxUser;
use app\common\library\wechat\ErrorCode;
use app\common\library\wechat\WXBizDataCrypt;
use cores\exception\BaseException;

/**
 * 服务类: 第三方用户服务类
 * Class Avatar
 * @package app\api\service\user
 */
class Oauth extends BaseService
{
    /**
     * 微信小程序通过code获取session (openid session_key unionid)
     * @param string $code
     * @return array|false
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function wxCode2Session(string $code)
    {
        // 获取当前小程序信息
        $config = self::getMpWxConfig();
        // 微信登录 (获取session_key)
        $WxUser = new WxUser($config['app_id'], $config['app_secret']);
        $result = $WxUser->jscode2session($code);
        empty($result) && throwError($WxUser->getError());
        return $result;
    }

    /**
     * 解密微信的加密数据encryptedData
     * @param string $encryptedData
     * @param string $iv
     * @param string|null $sessionKey
     * @return mixed
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \cores\exception\BaseException
     */
    public static function wxDecryptData(string $encryptedData, string $iv, ?string $sessionKey = null)
    {
        // 微信数据解密
        $WXBizDataCrypt = new WXBizDataCrypt($sessionKey);
        $plainData = null;
        $code = $WXBizDataCrypt->decryptData($encryptedData, $iv, $plainData);
        if ($code !== ErrorCode::$OK) {
            throwError('微信数据 encryptedData 解密失败');
        }
        return $plainData;
    }

    /**
     * 获取微信小程序配置项
     * @return array
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private static function getMpWxConfig(): array
    {
        $config = WxappSettingModel::getConfigBasic();
        if (empty($config['app_id']) || empty($config['app_secret'])) {
            throwError('请到后台小程序设置填写AppID和AppSecret参数');
        }
        return $config;
    }

    /**
     * 根据openid获取用户ID
     * @param string $oauthId 第三方用户唯一标识 (openid)
     * @param string $oauthType 第三方登陆类型
     * @return mixed
     */
    public static function getUserIdByOauthId(string $oauthId, string $oauthType)
    {
        return UserOauthModel::getUserIdByOauthId($oauthId, $oauthType);
    }
}
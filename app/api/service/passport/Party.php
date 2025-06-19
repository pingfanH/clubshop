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

namespace app\api\service\passport;

use app\api\model\UserOauth as UserOauthModel;
use app\api\service\user\Oauth as OauthService;
use app\api\service\user\Avatar as AvatarService;
use app\common\service\BaseService;
use app\common\enum\Client as ClientEnum;
use cores\exception\BaseException;
use think\Exception;

/**
 * 第三方用户注册登录服务
 * Class Party
 * @package app\api\service\passport
 */
class Party extends BaseService
{
    /**
     * 保存用户的第三方认证信息
     * @param int $userId 用户ID
     * @param array $partyData 第三方登录信息
     * @return bool
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createUserOauth(int $userId, array $partyData = []): bool
    {
        try {
            // 获取oauthId和unionId
            $oauthInfo = $this->getOauthInfo($partyData);
        } catch (BaseException $e) {
            // isBack参数代表需重新获取code, 前端拿到该参数进行页面返回
            throwError($e->getMessage(), null, ['isBack' => true]);
        }
        // 是否存在第三方用户
        $oauthId = UserOauthModel::getOauthIdByUserId($userId, $partyData['oauth']);
        // 如果不存在oauth则写入
        if (empty($oauthId)) {
            return (new UserOauthModel)->add([
                'user_id' => $userId,
                'oauth_type' => $partyData['oauth'],
                'oauth_id' => $oauthInfo['oauth_id'],
                'unionid' => $oauthInfo['unionid'] ?? '',   // unionid可以不存在
                'store_id' => $this->storeId
            ]);
        }
        // 如果存在第三方用户, 需判断oauthId是否相同
        if ($oauthId != $oauthInfo['oauth_id']) {
            // isBack参数代表需重新获取code, 前端拿到该参数进行页面返回
            throwError('很抱歉，当前手机号已绑定其他微信号', null, ['isBack' => true]);
        }
        return true;
    }

    /**
     * 获取微信小程序登录态(session)
     * 这里支持静态变量缓存, 用于实现第二次调用该方法时直接返回已获得的session
     * @param string $code
     * @return array|false
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getMpWxSession(string $code)
    {
        static $session;
        empty($session) && $session = OauthService::wxCode2Session($code);
        return $session;
    }

    /**
     * 第三方用户信息
     * @param array $partyData 第三方用户信息
     * @param bool $defaultNickName 是否需要生成默认用户昵称 (仅首次注册时)
     * @param bool $isGetAvatarUrl 是否保存或下载头像
     * @return array
     * @throws BaseException
     * @throws Exception
     */
    public static function partyUserInfo(array $partyData, bool $defaultNickName = false, bool $isGetAvatarUrl = true): array
    {
        $partyUserInfo = $partyData['userInfo'] ?? [];
        $data = [];
        if (!empty($partyUserInfo['nickName'])) {
            $data['nick_name'] = $partyUserInfo['nickName'];
        }
        // 生成默认的用户昵称
        if ($defaultNickName && empty($data['nick_name'])) {
            $data['nick_name'] = self::getDefaultNickName($partyData);
        }
        if ($isGetAvatarUrl) {
            // 记录avatarId
            if (!empty($partyUserInfo['avatarId']) && $partyUserInfo['avatarId'] > 0) {
                $data['avatar_id'] = (int)$partyUserInfo['avatarId'];
            }
            // 通过外链下载头像
            if (empty($data['avatar_id']) && !empty($partyUserInfo['avatarUrl'])) {
                $data['avatar_id'] = static::partyAvatar($partyUserInfo['avatarUrl']);
            }
        }
        return $data;
    }

    /**
     * 下载第三方头像并写入文件库
     * @param string $avatarUrl
     * @return int
     * @throws BaseException
     * @throws \think\Exception
     */
    private static function partyAvatar(string $avatarUrl): int
    {
        $Avatar = new AvatarService;
        $fileId = $Avatar->party($avatarUrl);
        return $fileId ?: 0;
    }

    /**
     * 获取第三方用户session信息 (openid、unionid)
     * @param array $partyData
     * @return array|null
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getOauthInfo(array $partyData): ?array
    {
        if ($partyData['oauth'] === ClientEnum::MP_WEIXIN) {
            $wxSession = static::getMpWxSession($partyData['code']);
            return ['oauth_id' => $wxSession['openid'], 'unionid' => $wxSession['unionid'] ?? null];
        }
        return null;
    }

    /**
     * 根据第三方来源生成默认用户昵称
     * @param array $partyData
     * @return string
     */
    private static function getDefaultNickName(array $partyData): string
    {
        $default = [
            ClientEnum::MP_WEIXIN => '微信',
            ClientEnum::H5 => 'H5',
            ClientEnum::APP => 'APP',
        ];
        return isset($default[$partyData['oauth']]) ? "{$default[$partyData['oauth']]}用户" : '商城用户';
    }
}
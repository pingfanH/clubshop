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

use think\facade\Cache;
use yiovo\captcha\facade\CaptchaApi;
use app\api\model\{
    User as UserModel,
    Setting as SettingModel,
    UploadFile as UploadFileModel
};
use app\api\service\{user\Oauth as OauthService, passport\Party as PartyService};
use app\api\validate\passport\Login as ValidateLogin;
use app\common\service\BaseService;
use app\common\enum\Client as ClientEnum;
use app\common\enum\Setting as SettingEnum;
use cores\exception\BaseException;

/**
 * 服务类：用户登录
 * Class Login
 * @package app\api\service\passport
 */
class Login extends BaseService
{
    /**
     * 用户信息 (登录成功后才记录)
     * @var UserModel|null $userInfo
     */
    private ?UserModel $userInfo;

    // 用于生成token的自定义盐
    const TOKEN_SALT = 'user_salt';

    /**
     * 执行用户登录
     * @param array $data
     * @return bool
     * @throws BaseException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function login(array $data): bool
    {
        // 数据验证
        $this->validate($data);
        // 自动登录注册
        $this->register($data);
        // 保存第三方用户信息
        $this->createUserOauth($this->getUserId(), $data['isParty'], $data['partyData']);
        // 记录登录态
        return $this->setSession();
    }

    /**
     * 快捷登录：微信小程序用户
     * @param array $form
     * @return bool
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\Exception
     */
    public function loginMpWx(array $form): bool
    {
        // 获取微信小程序登录态(session)
        $wxSession = PartyService::getMpWxSession($form['partyData']['code']);
        // 判断openid是否存在
        $userId = OauthService::getUserIdByOauthId($wxSession['openid'], ClientEnum::MP_WEIXIN);
        // 获取用户信息
        $userInfo = !empty($userId) ? UserModel::detail($userId) : null;
        // 用户信息存在, 更新登录信息
        if (!empty($userInfo)) {
            // 更新用户登录信息
            $this->updateUser($userInfo, true, $form['partyData']);
            // 记录登录态
            return $this->setSession();
        }
        // 用户信息不存在 => 注册新用户 或者 跳转到绑定手机号页
        $setting = SettingModel::getItem(SettingEnum::REGISTER);
        // 后台设置了需强制绑定手机号, 返回前端isBindMobile, 跳转到手机号验证页
        if ($setting['isForceBindMpweixin']) {
            throwError('当前用户未绑定手机号', null, ['isBindMobile' => true, 'isPrompt' => false]);
        }
        // 后台未开启强制绑定手机号, 直接保存新用户
        if (!$setting['isForceBindMpweixin']) {
            // 用户不存在: 创建一个新用户
            $this->createUser('', true, $form['partyData']);
            // 保存第三方用户信息
            $this->createUserOauth($this->getUserId(), true, $form['partyData']);
        }
        // 记录登录态
        return $this->setSession();
    }

    /**
     * 是否需要填写昵称头像 (微信小程序端)
     * @param string $code
     * @return bool
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\Exception
     */
    public function isPersonalMpweixin(string $code): bool
    {
        // 后台需开启填写微信头像和昵称
        $setting = SettingModel::getItem(SettingEnum::REGISTER);
        if (!$setting['isPersonalMpweixin']) {
            return false;
        }
        // 获取微信小程序登录态 (session)
        $wxSession = PartyService::getMpWxSession($code);
        // 判断用户是否存在 (openid)
        return !OauthService::getUserIdByOauthId($wxSession['openid'], ClientEnum::MP_WEIXIN);
    }

    /**
     * 快捷登录：微信小程序授权手机号登录
     * @param array $form
     * @return bool
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\Exception
     */
    public function loginMpWxMobile(array $form): bool
    {
        // 获取微信小程序登录态(session)
        $wxSession = PartyService::getMpWxSession($form['code']);
        // 解密encryptedData -> 拿到手机号
        $plainData = OauthService::wxDecryptData($form['encryptedData'], $form['iv'], $wxSession['session_key']);
        // 整理登录注册数据
        $loginData = [
            'mobile' => $plainData['purePhoneNumber'],
            'isParty' => $form['isParty'],
            'partyData' => $form['partyData'],
        ];
        // 自动登录注册
        $this->register($loginData);
        // 保存第三方用户信息
        $this->createUserOauth($this->getUserId(), $loginData['isParty'], $loginData['partyData']);
        // 记录登录态
        return $this->setSession();
    }

    /**
     * 保存oauth信息(第三方用户信息)
     * @param int $userId 用户ID
     * @param bool $isParty 是否为第三方用户
     * @param array $partyData 第三方用户数据
     * @return void
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function createUserOauth(int $userId, bool $isParty, array $partyData = []): void
    {
        if ($isParty) {
            $Oauth = new PartyService;
            $Oauth->createUserOauth($userId, $partyData);
        }
    }

    /**
     * 当前登录的用户信息
     * @return UserModel
     */
    public function getUserInfo(): UserModel
    {
        return $this->userInfo;
    }

    /**
     * 当前登录的用户ID
     * @return int
     */
    private function getUserId(): int
    {
        return (int)$this->getUserInfo()['user_id'];
    }

    /**
     * 自动登录注册
     * @param array $data
     * @throws BaseException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function register(array $data): void
    {
        // 查询用户是否已存在
        // 用户存在: 更新用户登录信息
        $userInfo = UserModel::detail(['mobile' => $data['mobile']]);
        if ($userInfo) {
            $this->updateUser($userInfo, $data['isParty'], $data['partyData']);
            return;
        }
        // 用户不存在: 创建一个新用户
        $this->createUser($data['mobile'], $data['isParty'], $data['partyData']);
    }

    /**
     * 新增用户
     * @param string $mobile 手机号
     * @param bool $isParty 是否存在第三方用户信息
     * @param array $partyData 用户信息(第三方)
     * @return void
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function createUser(string $mobile, bool $isParty, array $partyData = []): void
    {
        // 用户信息
        $data = [
            'mobile' => $mobile,
            'nick_name' => !empty($mobile) ? \hide_mobile($mobile) : '',
            'platform' => \getPlatform(),
            'last_login_time' => \time(),
            'store_id' => $this->storeId
        ];
        // 写入用户信息(第三方)
        if ($isParty === true && !empty($partyData)) {
            $partyUserInfo = PartyService::partyUserInfo($partyData, true);
            $data = \array_merge($data, $partyUserInfo);
        }
        // 新增用户记录
        $model = new UserModel;
        $model->save($data);
        // 将微信用户昵称添加编号便于后台管理, 例如：微信用户_10001
        if (\in_array($data['nick_name'], ['微信用户', '支付宝用户'])) {
            $model->save(['nick_name' => "{$data['nick_name']}_{$model['user_id']}"]);
        }
        // 记录头像文件上传者
        if (isset($data['avatar_id']) && $data['avatar_id'] > 0) {
            UploadFileModel::setUploaderId($data['avatar_id'], (int)$model['user_id']);
        }
        // 记录用户信息
        $this->userInfo = $model;
    }

    /**
     * 更新用户登录信息
     * @param UserModel $userInfo
     * @param bool $isParty 是否存在第三方用户信息
     * @param array $partyData 用户信息(第三方)
     */
    private function updateUser(UserModel $userInfo, bool $isParty, array $partyData = []): void
    {
        // 用户信息
        $data = [
            'last_login_time' => \time(),
            'store_id' => $this->storeId
        ];
        // 写入用户信息(第三方)
        // 如果不需要每次登录都更新微信用户头像昵称, 下面几行代码可以屏蔽掉
//        if ($isParty === true && !empty($partyData)) {
//            $partyUserInfo = PartyService::partyUserInfo($partyData);
//            $data = \array_merge($data, $partyUserInfo);
//        }
//        // 记录头像文件上传者
//        if (isset($data['avatar_id']) && $data['avatar_id'] > 0) {
//            UploadFileModel::setUploaderId($data['avatar_id'], $userInfo['user_id']);
//        }
        // 更新用户记录
        $userInfo->save($data);
        // 记录用户信息
        $this->userInfo = $userInfo;
    }

    /**
     * 记录登录态
     * @return bool
     * @throws BaseException
     */
    private function setSession(): bool
    {
        empty($this->userInfo) && \throwError('未找到用户信息');
        // 登录的token
        $token = $this->getToken($this->getUserId());
        // 记录缓存, 30天
        Cache::set($token, [
            'user' => $this->userInfo,
            'store_id' => $this->storeId,
            'is_login' => true,
        ], 86400 * 30);
        return true;
    }

    /**
     * 数据验证
     * @param array $data
     * @return void
     * @throws BaseException
     */
    private function validate(array $data): void
    {
        // 数据验证
        $validate = new ValidateLogin;
        if (!$validate->check($data)) {
            throwError($validate->getError());
        }
        // 验证短信验证码是否匹配
        try {
            CaptchaApi::checkSms($data['smsCode'], $data['mobile']);
        } catch (\Exception $e) {
            throwError($e->getMessage() ?: '短信验证码不正确');
        }
    }

    /**
     * 获取登录的token
     * @param int $userId
     * @return string
     */
    public function getToken(int $userId): string
    {
        static $token = '';
        if (empty($token)) {
            $token = $this->makeToken($userId);
        }
        return $token;
    }

    /**
     * 生成用户认证的token
     * @param int $userId
     * @return string
     */
    private function makeToken(int $userId): string
    {
        $storeId = $this->storeId;
        // 生成一个不会重复的随机字符串
        $guid = \get_guid_v4();
        // 当前时间戳 (精确到毫秒)
        $timeStamp = \microtime(true);
        // 自定义一个盐
        $salt = self::TOKEN_SALT;
        return md5("{$storeId}_{$timeStamp}_{$userId}_{$guid}_{$salt}");
    }
}
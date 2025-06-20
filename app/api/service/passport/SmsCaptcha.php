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

use yiovo\cache\facade\Cache;
use yiovo\captcha\facade\CaptchaApi;
use app\api\validate\passport\SmsCaptcha as ValidateSmsCaptcha;
use app\common\service\BaseService;
use app\common\service\Message as MessageService;
use cores\exception\BaseException;

/**
 * 服务类：发送短信验证码
 * Class SmsCaptcha
 * @package app\api\service\passport
 */
class SmsCaptcha extends BaseService
{
    // 最大发送次数，默认10次
    protected int $sendTimes = 10;

    // 发送限制间隔时间，默认24小时
    protected int $safeTime = 86400;

    /**
     * 发送短信验证码
     * @param array $data
     * @return bool
     * @throws BaseException
     */
    public function handle(array $data): bool
    {
        // 数据验证
        $this->validate($data);
        // 执行发送短信
        if (!$this->sendCaptcha($data['mobile'])) {
            return false;
        }
        return true;
    }

    /**
     * 执行发送短信
     * @param string $mobile
     * @return bool
     */
    private function sendCaptcha(string $mobile): bool
    {
        // 缓存发送记录并判断次数
        if (!$this->record($mobile)) {
            return false;
        }
        // 生成验证码
        $smsCaptcha = CaptchaApi::createSMS($mobile);
        // 发送短信
        MessageService::send('passport.captcha', [
            'code' => $smsCaptcha['code'],
            'mobile' => $smsCaptcha['key']
        ], $this->storeId);
        return true;
    }

    /**
     * 记录短信验证码发送记录并判断是否超出发送限制
     * @param string $mobile
     * @return bool
     */
    private function record(string $mobile): bool
    {
        // 获取发送记录缓存
        $record = Cache::get("sendCaptchaSMS.$mobile");
        // 写入缓存:记录剩余发送次数
        if (empty($record)) {
            Cache::set("sendCaptchaSMS.$mobile", ['times' => $this->sendTimes - 1], $this->safeTime);
            return true;
        }
        // 判断发送次数是否合法
        if ($record['times'] <= 0) {
            $this->error = '很抱歉，已超出今日最大发送次数限制';
            return false;
        }
        // 发送次数递减
        Cache::update("sendCaptchaSMS.$mobile", ['times' => $record['times'] - 1]);
        return true;
    }

    /**
     * 数据验证
     * @param array $data
     * @throws BaseException
     */
    private function validate(array $data)
    {
        // 数据验证
        $validate = new ValidateSmsCaptcha;
        if (!$validate->check($data)) {
            throwError($validate->getError());
        }
        // 验证图形验证码
        if (!CaptchaApi::check($data['captchaCode'], $data['captchaKey'])) {
            throwError('很抱歉，图形验证码不正确');
        }
    }
}
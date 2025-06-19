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

namespace app\api\controller;

use think\response\Json;
use app\api\service\passport\{Captcha as CaptchaService, SmsCaptcha as SmsCaptchaService};
use cores\exception\BaseException;

/**
 * 验证码管理
 * Class Cart
 * @package app\api\controller
 */
class Captcha extends Controller
{
    /**
     * 图形验证码
     * @return Json
     */
    public function image(): Json
    {
        $CaptchaService = new CaptchaService;
        return $this->renderSuccess($CaptchaService->create());
    }

    /**
     * 发送短信验证码
     * @return Json
     * @throws BaseException
     */
    public function sendSmsCaptcha(): Json
    {
        $SmsCaptchaService = new SmsCaptchaService;
        if ($SmsCaptchaService->handle($this->postForm())) {
            return $this->renderSuccess('发送成功，请注意查收');
        }
        return $this->renderError($SmsCaptchaService->getError() ?: '短信发送失败');
    }
}
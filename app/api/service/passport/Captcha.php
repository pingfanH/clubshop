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

use app\common\service\BaseService;
use yiovo\captcha\facade\CaptchaApi;

/**
 * 服务类：图形验证码
 * Class Captcha
 * @package app\api\service\passport
 */
class Captcha extends BaseService
{
    /**
     * 图形验证码
     * @return array
     */
    public function create(): array
    {
        $data = CaptchaApi::create();
        return [
            'base64' => str_replace("\r\n", '', $data['base64']),
            'key' => $data['key'],
            'md5' => $data['md5']
        ];
    }
}
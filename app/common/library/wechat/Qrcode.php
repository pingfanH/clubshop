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

namespace app\common\library\wechat;

use app\common\library\helper;
use cores\exception\BaseException;

/**
 * 小程序二维码
 * Class Qrcode
 * @package app\common\library\wechat
 */
class Qrcode extends WxBase
{
    /**
     * 获取小程序码
     * API文档地址：https://developers.weixin.qq.com/miniprogram/dev/api-backend/open-api/qr-code/wxacode.getUnlimited.html
     * @param string $scene 场景值 (例如: uid:10001)
     * @param string|null $page 页面地址
     * @param int $width 二维码宽度
     * @return mixed
     * @throws BaseException
     */
    public function getQrcode(string $scene, string $page = null, int $width = 430)
    {
        // 微信接口url
        $accessToken = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token={$accessToken}";
        // 构建请求
        $data = compact('scene', 'width');
        !is_null($page) && $data['page'] = $page;
        // 返回结果
        $result = $this->post($url, helper::jsonEncode($data));
        // 记录日志
        log_record([
            'name' => '获取小程序码',
            'params' => $data,
            'result' => !strpos($result, 'errcode') ? 'true' : $result
        ]);
        if (!strpos($result, 'errcode')) {
            return $result;
        }
        $data = helper::jsonDecode($result);
        $error = '小程序码获取失败 ' . $data['errmsg'];
        if ($data['errcode'] == 41030) {
            $error = '小程序页面不存在，请先发布上线后再生成';
        }
        throwError($error);
    }
}
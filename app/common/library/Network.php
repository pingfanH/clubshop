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

namespace app\common\library;

use cores\exception\BaseException;

/**
 * 网络请求工具类
 * Class Network
 * @package app\common\library
 */
class Network
{
    /**
     * GET请求
     * @param string $url
     * @param array $query
     * @param array $headers
     * @return bool|string
     * @throws BaseException
     */
    public static function curlGet(string $url, array $query = [], array $headers = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, !empty($query) ? $url . '?' . http_build_query($query) : $url);
        if ($headers) {
            if (isset($headers['user_agent'])) {
                curl_setopt($ch, CURLOPT_USERAGENT, $headers['user_agent']);
            }
            if (isset($headers['refer'])) {
                curl_setopt($ch, CURLOPT_REFERER, $headers['refer']);
            }
            if (isset($headers['cookie'])) {
                curl_setopt($ch, CURLOPT_COOKIE, $headers['cookie']);
            }
            curl_setopt($ch, CURLOPT_HEADER, $headers); // 设置header
        }
        // 返回最后的Location
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            $errorMessage = serialize(curl_error($ch));
            throwError("访问网络[{$url}]出错(GET)：" . $errorMessage);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode != 200) {
            throwError("访问网络[{$url}]出错(GET)：" . $result);
        }
        if (empty($headers)) {
            return $result;
        }
        [, $body] = explode("\r\n\r\n", $result, 2);
        return $body;
    }

    /**
     * POST请求
     * @param string $url
     * @param array $data 提交的内容
     * @param array $headers 头部信息
     * @param string $cert 证书
     * @param string $certKey 证书秘钥
     * @return bool|string
     * @throws BaseException
     */
    public static function curlPost(string $url, array $data, array $headers = [], string $cert = '', string $certKey = '')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, $headers); // 设置header
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        // 证书默认格式为PEM
        if ($cert && $certKey) {
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLCERT, $cert);
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLKEY, $certKey);
        }
        curl_setopt($ch, CURLOPT_POST, 1); //post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            $errorMessage = serialize(curl_error($ch));
            throwError("访问网络[{$url}]出错(GET)：" . $errorMessage);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode != 200) {
            throwError("访问网络[{$url}]出错(GET)：" . $result);
        }
        return $result;
    }
}
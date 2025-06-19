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

namespace app\common\library\express\provider\driver;

use app\common\library\helper;
use app\common\library\express\provider\Driver;
use cores\exception\BaseException;

/**
 * 阿里云物流查询驱动
 * Class Aliyun
 * 接口文档: https://market.aliyun.com/products/57126001/cmapi023201.html
 * @package app\common\library\express\provider\driver
 */
class Aliyun extends Driver
{
    // API地址
    const API_URL = 'https://wdexpress.market.alicloudapi.com/gxali';

    /**
     * 查询物流轨迹
     * @param string $code 快递公司的编码
     * @param string $expressNo 查询的快递单号
     * @param array $extra 附加数据
     * @return array
     * @throws BaseException
     */
    public function query(string $code, string $expressNo, array $extra = []): array
    {
        // 授权参数
        $appCode = $this->options['appCode'];
        $headers = ["Authorization: APPCODE " . $appCode];
        // 查询顺丰和中通时 物流单号需要加上手机尾号
        if (\in_array($code, ['SF', 'ZTO'])) {
            $lastPhoneNumber = \mb_substr($extra['phone'], -4);
            $expressNo = "{$expressNo}:$lastPhoneNumber";
        }
        // 物流查询参数
        $querys = ['n' => $expressNo, 't' => $code];
        // 请求API
        $result = $this->curlGet(self::API_URL, $headers, $querys);
        $data = helper::jsonDecode($result);
        // 记录日志
        log_record(['name' => '查询物流轨迹', 'provider' => 'aliyun', 'param' => $querys, 'result' => $data]);
        // 错误信息
        if ($data['State'] <= 0 || !$data['Success']) {
            throwError('阿里云物流查询API失败：' . $data['Reason']);
        }
        // 格式化返回的数据
        return $this->formatTraces($data['Traces']);
    }

    /**
     * 格式化返回的数据
     * @param array $source
     * @return array
     */
    private function formatTraces(array $source): array
    {
        return \array_map(fn($item) => ['time' => $item['AcceptTime'], 'context' => $item['AcceptStation']]
            , array_reverse($source));
    }

    /**
     * curl请求指定url
     * @param string $url
     * @param array $headers
     * @param array $querys
     * @return bool|mixed|string
     * @throws BaseException
     */
    protected function curlGet(string $url, array $headers, array $querys)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($querys));
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        [$header, $body] = explode("\r\n\r\n", $result, 2);
        curl_close($ch);
        if ($httpCode == 200) {
            return $body;
        }
        $message = '参数名错误或其他错误';
        if ($httpCode == 400 && strpos($header, "Invalid Param Location") !== false) {
            $message = '参数错误';
        } elseif ($httpCode == 400 && strpos($header, "Invalid AppCode") !== false) {
            $message = 'AppCode错误';
        } elseif ($httpCode == 400 && strpos($header, "Invalid Url") !== false) {
            $message = '请求的 Method、Path 或者环境错误';
        } elseif ($httpCode == 403 && strpos($header, "Unauthorized") !== false) {
            $message = '服务未被授权（或URL和Path不正确）';
        } elseif ($httpCode == 403 && strpos($header, "Quota Exhausted") !== false) {
            $message = '套餐包次数用完';
        } elseif ($httpCode == 500) {
            $message = 'API网关错误';
        } elseif ($httpCode == 0) {
            $message = 'URL错误';
        }
        throwError('阿里云物流查询API失败：' . $message);
    }
}
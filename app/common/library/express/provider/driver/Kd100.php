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
 * 快递100物流查询驱动
 * Class Kd100
 * 接口文档: https://api.kuaidi100.com/document/5f0ffb5ebc8da837cbd8aefc
 * @package app\common\library\express\provider\driver
 */
class Kd100 extends Driver
{
    // API地址
    const API_URL = 'https://poll.kuaidi100.com/poll/query.do';

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
        // 参数设置
        $param = [
            'customer' => $this->options['customer'],
            'param' => helper::jsonEncode([
                'resultv2' => '1',
                'com' => $code,
                'num' => $expressNo,
                // 顺丰参数 (需传参收、寄件人的电话号码)
                'phone' => $code === 'shunfeng' ? $extra['phone'] : ''
            ])
        ];
        $param['sign'] = strtoupper(md5($param['param'] . $this->options['key'] . $param['customer']));
        // 请求API
        $result = $this->curlPost(self::API_URL, $param);
        $data = helper::jsonDecode($result);
        // 记录日志
        log_record(['name' => '查询物流轨迹', 'provider' => 'kd100', 'param' => $param, 'result' => $data]);
        // 错误信息
        if (isset($data['returnCode']) || !isset($data['data'])) {
            throwError('快递100物流查询API失败：' . $data['message']);
        }
        // 格式化返回的数据
        return $this->formatTraces($data['data']);
    }

    /**
     * 格式化返回的数据
     * @param array $source
     * @return array
     */
    private function formatTraces(array $source): array
    {
        return \array_map(fn($item) => ['time' => $item['ftime'], 'context' => $item['context']], $source);
    }

    /**
     * curl请求指定url
     * @param string $url
     * @param array $param
     * @return bool|string
     */
    protected function curlPost(string $url, array $param = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($param));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}
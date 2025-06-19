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

namespace app\common\library\printer\engine;

use app\common\library\helper;
use app\common\library\printer\party\FeieHttpClient;

/**
 * 飞鹅打印机API引擎
 * Class Feie
 * @package app\common\library\printer\engine
 */
class Feie extends Basics
{
    /** @const IP 接口IP或域名 */
    const IP = 'api.feieyun.cn';

    /** @const PORT 接口IP端口 */
    const PORT = 80;

    /** @const PATH 接口路径 */
    const PATH = '/Api/Open/';

    /**
     * 执行订单打印
     * @param string $content
     * @return bool
     */
    public function printTicket(string $content): bool
    {
        // 构建请求参数
        $params = $this->getParams($content);
        // API请求：开始打印
        $client = new FeieHttpClient(self::IP, self::PORT);
        if (!$client->post(self::PATH, $params)) {
            $this->error = $client->getError();
            return false;
        }
        // 处理返回结果
        $result = helper::jsonDecode($client->getContent());
        // 记录日志
        log_record($result);
        // 返回状态
        if ($result['ret'] != 0) {
            $this->error = $result['msg'];
            return false;
        }
        return true;
    }

    /**
     * 构建Api请求参数
     * @param $content
     * @return array
     */
    private function getParams(&$content): array
    {
        $time = time();
        return [
            'user' => $this->config['USER'],
            'stime' => $time,
            'sig' => sha1("{$this->config['USER']}{$this->config['UKEY']}{$time}"),
            'apiname' => 'Open_printMsg',
            'sn' => $this->config['SN'],
            'content' => $content,
            'times' => $this->times    // 打印次数
        ];
    }
}
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

/**
 * 365云打印引擎
 */
class PrintCenter extends Basics
{
    /** @const API地址 */
    const API = 'http://open.printcenter.cn:8080/addOrder';

    /**
     * 执行订单打印
     * @param string $content
     * @return bool
     */
    public function printTicket(string $content): bool
    {
        // 构建请求参数
        $context = stream_context_create([
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded ",
                'method' => 'POST',
                'content' => http_build_query([
                    'deviceNo' => $this->config['deviceNo'],
                    'key' => $this->config['key'],
                    'printContent' => $content,
                    'times' => $this->times
                ]),
            ]
        ]);
        // API请求：开始打印
        $result = file_get_contents(self::API, false, $context);
        // 处理返回结果
        $result = helper::jsonDecode($result);
        log_record($result);
        // 返回状态
        if ($result['responseCode'] != 0) {
            $this->error = $result['msg'];
            return false;
        }
        return true;
    }
}
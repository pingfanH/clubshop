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

namespace cores\middleware;

use think\Response;
use think\facade\Log as FacadeLog;
use app\common\library\Log;

/**
 * 中间件：应用日志
 */
class AppLog
{
    // 访问日志
    private static string $beginLog = '';

    /**
     * 前置中间件
     * @param \think\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(\think\Request $request, \Closure $next)
    {
        // 记录访问日志
        if (env('begin_log')) {
            $log = $this->getVisitor($request);
            $log .= "\r\n" . '[ header ] ' . print_r($request->header(), true);
            $log .= "" . '[ param ] ' . print_r($request->param(), true);
            $log .= '--------------------------------------------------------------------------------------------';
            static::$beginLog = $log;
        }
        return $next($request);
    }

    /**
     * 记录访问日志
     * @param Response $response
     */
    public function end(Response $response)
    {
        FacadeLog::record(static::$beginLog, 'begin');
        Log::end();
    }

    /**
     * 获取请求路径信息
     * @param \think\Request $request
     * @return string
     */
    private function getVisitor(\think\Request $request): string
    {
        $data = [$request->ip(), $request->method(), $request->url(true)];
        return implode(' ', $data);
    }
}
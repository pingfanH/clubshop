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

use Closure;
use think\Config;
use think\Request;
use think\Response;

/**
 * 跨域请求支持
 * Class AllowCrossDomain
 * @package cores\middleware
 */
class AllowCrossDomain
{
    // cookie的所属域名
    protected $cookieDomain;

    /**
     * 构造方法
     * AllowCrossDomain constructor.
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->cookieDomain = $config->get('cookie.domain', '');
    }

    /**
     * 获取允许跨域的header参数 [自定义]
     * @return array
     */
    private function getCustomHeader(): array
    {
        return [
            'Access-Token',
            'storeId',
            'store-id',
            'platform',
            'domain',
        ];
    }

    /**
     * 获取允许跨域的header参数
     * @return array
     */
    private function getHeader(): array
    {
        $headers = array_merge([
            'Authorization', 'Content-Type', 'X-CSRF-TOKEN', 'X-Requested-With',
            'If-Match', 'If-Modified-Since', 'If-None-Match', 'If-Unmodified-Since'
        ], $this->getCustomHeader());

        return [
            // 允许cookie跨域访问
            'Access-Control-Allow-Credentials' => 'true',
            // 预检请求的有效期（设为0禁止缓存，避免CORS策略更新不及时）
            'Access-Control-Max-Age' => 0,
            // 允许跨域的方法
            'Access-Control-Allow-Methods' => 'GET, POST, PATCH, PUT, DELETE, OPTIONS',
            // 跨域请求header头
            'Access-Control-Allow-Headers' => implode(',', $headers),
        ];
    }

    /**
     * 允许跨域请求
     * @access public
     * @param Request $request
     * @param Closure $next
     * @param array|null $header
     * @return Response
     */
    public function handle(Request $request, Closure $next, ?array $header = []): Response
    {
        $header = !empty($header) ? array_merge($this->getHeader(), $header) : $this->getHeader();
        // 动态设置 Origin（带认证信息时不能用 *）
        $origin = $request->header('origin');
        if ($origin) {
            $header['Access-Control-Allow-Origin'] = $origin;
            $header['Vary'] = 'Origin';
        } else {
            $header['Access-Control-Allow-Origin'] = '*';
        }
        // OPTIONS预检请求直接返回
        if ($request->method(true) === 'OPTIONS') {
            return \think\Response::create('', 'html', 204)->header($header);
        }
        return $next($request)->header($header);
    }
}

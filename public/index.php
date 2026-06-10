<?php

// [ 应用入口文件 ]
namespace think;

// 检测PHP环境
if (version_compare(PHP_VERSION, '7.1.0', '<')) die('require PHP > 7.1.0 !');

// 处理OPTIONS预检请求，在框架启动前直接返回CORS头
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Authorization, Content-Type, X-CSRF-TOKEN, X-Requested-With, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, Access-Token, storeId, store-id, platform, domain');
    header('Access-Control-Max-Age: 0');
    header('Vary: Origin');
    http_response_code(204);
    exit;
}

// 加载核心文件
require __DIR__ . '/../vendor/autoload.php';

// 执行HTTP应用并响应
$http = (new App())->http;

$response = $http->run();

$response->send();

$http->end($response);


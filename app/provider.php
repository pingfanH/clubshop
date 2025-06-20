<?php

use cores\Request;
use cores\ExceptionHandle;

// 容器Provider定义文件
return [
    'think\Request' => Request::class,
    'think\exception\Handle' => ExceptionHandle::class,
];

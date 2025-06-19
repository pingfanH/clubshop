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

use cores\traits\ErrorTrait;

/**
 * 小票打印机驱动基类
 * Class Basics
 * @package app\common\library\printer\engine
 */
abstract class Basics
{
    use ErrorTrait;

    /**
     * 打印机配置
     * @var array
     */
    protected array $config;

    /**
     * 打印联数(次数)
     * @var int
     */
    protected int $times;

    /**
     * 构造函数
     * Basics constructor.
     * @param array $config 打印机配置
     * @param int $times 打印联数(次数)
     */
    public function __construct(array $config, int $times)
    {
        $this->config = $config;
        $this->times = $times;
    }

    /**
     * 执行打印请求
     * @param string $content
     * @return bool
     */
    abstract protected function printTicket(string $content): bool;
}
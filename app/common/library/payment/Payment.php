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
namespace app\common\library\payment;

use think\Manager;
use app\common\library\payment\gateway\Driver;

/**
 * 第三方支付扩展
 * Class Payment
 * @package app\common\library\payment
 */
class Payment extends Manager
{
    /**
     * 驱动的命名空间
     * @var string
     */
    protected $namespace = '\\app\\common\\library\\payment\\gateway\\driver\\';

    /**
     * 默认驱动
     */
    public function getDefaultDriver()
    {
    }

    /**
     * 连接或者切换驱动
     * @access public
     * @param string|null $name 驱动名称
     * @return Driver
     */
    public function store(string $name = null): Driver
    {
        return $this->driver($name);
    }

    /**
     * 设置支付配置参数
     * @param array $options
     * @param string $client
     * @return Driver|null
     */
    public function setOptions(array $options, string $client): ?Driver
    {
        $this->store()->setOptions($options, $client);
        return $this->store();
    }
}
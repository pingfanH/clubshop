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

namespace app\common\library\express\provider;

use cores\traits\ErrorTrait;
use cores\exception\BaseException;

/**
 * 物流查询驱动基类
 * Class Driver
 * @package app\common\library\express\provider\driver
 */
abstract class Driver
{
    use ErrorTrait;

    /**
     * 驱动句柄
     * @var Driver
     */
    protected $handler = null;

    /**
     * api配置参数
     * @var array
     */
    protected $options = [];

    /**
     * 查询物流轨迹
     * @param string $code 快递公司的编码
     * @param string $expressNo 查询的快递单号
     * @param array $extra 附加数据
     * @return array
     */
    abstract function query(string $code, string $expressNo, array $extra = []): array;

    /**
     * 设置api配置参数
     * @param array $options 配置信息
     * @return static|null
     */
    public function setOptions(array $options): ?Driver
    {
        $this->options = $options;
        return $this;
    }
}
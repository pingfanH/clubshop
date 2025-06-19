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

namespace cores\exception;

/**
 * 自定义异常类：调试输出
 * Class DebugException
 * @package cores\exception
 */
class DebugException extends BaseException
{
    // 附加的数据
    public array $extend = [];

    /**
     * 构造函数，接收一个关联数组
     * @param array $params 关联数组只应包含status、msg、data，且不应该是空值
     * @param mixed $debug 调试输出的数据
     */
    public function __construct(array $params = [], $debug = null)
    {
        parent::__construct($params);
        $this->status = config('status.success');
        $this->message = '-- 调试输出 --';
        $this->extend = ['debug' => $debug];
    }
}


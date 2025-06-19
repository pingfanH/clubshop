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

namespace cores\traits;

/**
 * 错误信息Trait类
 * Trait ErrorTrait
 * @package cores\traits
 */
trait ErrorTrait
{
    /**
     * 错误信息
     * @var string
     */
    protected string $error = '';

    /**
     * 设置错误信息
     * @param string $error
     * @return bool
     */
    protected function setError(string $error): bool
    {
        $this->error = $error ?: '未知错误';
        return false;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * 是否存在错误信息
     * @return bool
     */
    public function hasError(): bool
    {
        return !empty($this->error);
    }
}
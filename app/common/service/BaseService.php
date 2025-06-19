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

namespace app\common\service;

use think\facade\Request;
use cores\traits\ErrorTrait;

/**
 * 系统服务基础类
 * Class BaseService
 * @package app\common\service
 */
class BaseService
{
    use ErrorTrait;

    // 请求管理类
    /* @var $request \cores\Request */
    protected $request;

    // 当前访问的商城ID
    protected ?int $storeId;

    /**
     * 构造方法
     * BaseService constructor.
     */
    public function __construct()
    {
        // 请求管理类
        $this->request = Request::instance();
        // 获取当前操作的商城ID
        $this->getStoreId();
        // 执行子类的构造方法
        $this->initialize();
    }

    /**
     * 手动设置商城ID
     * @param int $storeId
     * @return static
     */
    public function setStoreId(int $storeId): self
    {
        $this->storeId = $storeId;
        return $this;
    }

    /**
     * 构造方法 (供继承的子类使用)
     */
    protected function initialize()
    {
    }

    /**
     * 获取当前操作的商城ID
     * @return int|null
     */
    protected function getStoreId(): ?int
    {
        if (empty($this->storeId)) {
            $this->storeId = \getStoreId();
        }
        return $this->storeId;
    }
}

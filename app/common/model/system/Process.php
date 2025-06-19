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

namespace app\common\model\system;

use cores\BaseModel;

/**
 * 系统进程模型
 * Class Process
 * @package app\common\model\system
 */
class Process extends BaseModel
{
    // 定义表名
    protected $name = 'system_process';

    // 定义主键
    protected $pk = 'key';

    // 不允许全局查询store_id
    protected bool $isGlobalScopeStoreId = false;

    protected $createTime = false;
    protected $updateTime = false;

}
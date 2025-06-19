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

namespace app\common\model;

use cores\BaseModel;

/**
 * 物流公司模型
 * Class Express
 * @package app\common\model
 */
class Express extends BaseModel
{
    // 定义表名
    protected $name = 'express';

    // 定义主键
    protected $pk = 'express_id';

    /**
     * 获取全部记录
     * @param array $param
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAll(array $param = []): \think\Collection
    {
        // 检索查询条件
        $filter = $this->getFilter($param);
        // 查询列表数据
        return $this->where($filter)
            ->where('is_delete', '=', 0)
            ->order(['sort', $this->getPk()])
            ->select();
    }

    /**
     * 获取列表
     * @param array $param
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getList(array $param = []): \think\Paginator
    {
        // 检索查询调价你
        $filter = $this->getFilter($param);
        // 查询列表数据
        return $this->where($filter)
            ->where('is_delete', '=', 0)
            ->order(['sort', $this->getPk()])
            ->paginate(15);
    }

    /**
     * 检索查询条件
     * @param array $param
     * @return array
     */
    private function getFilter(array $param = []): array
    {
        // 默认查询条件
        $params = $this->setQueryDefaultValue($param, ['search' => '']);
        // 检索查询条件
        $filter = [];
        !empty($params['search']) && $filter[] = ['express_name', 'like', "%{$params['search']}%"];
        return $filter;
    }

    /**
     * 物流公司详情
     * @param int $expressId
     * @return static|array|null
     */
    public static function detail(int $expressId)
    {
        return self::get($expressId);
    }
}

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

namespace app\store\model;

use app\common\model\Express as ExpressModel;

/**
 * 物流公司模型
 * Class Goods
 * @package app\store\model
 */
class Express extends ExpressModel
{
    /**
     * 添加新记录
     * @param array $data
     * @return bool|false
     */
    public function add(array $data): bool
    {
        $data['store_id'] = self::$storeId;
        return $this->save($data);
    }

    /**
     * 编辑记录
     * @param array $data
     * @return bool
     */
    public function edit(array $data): bool
    {
        return $this->save($data);
    }

    /**
     * 删除记录
     * @return bool
     */
    public function remove(): bool
    {
        return $this->save(['is_delete' => 1]);
    }

    /**
     * 根据物流公司名称获取ID集
     * @param array $expressNames
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getExpressIds(array $expressNames): array
    {
        $list = (new static)->getListByExpressName($expressNames);
        $data = [];
        foreach ($list as $item) {
            $data[$item['express_name']] = $item['express_id'];
        }
        return $data;
    }

    /**
     * 根据物流公司名称获取列表记录
     * @param array $expressNames
     * @return Express[]|array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getListByExpressName(array $expressNames)
    {
        return (new static)->where('express_name', 'in', $expressNames)
            ->where('is_delete', '=', 0)
            ->select();
    }
}
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

namespace app\store\model\store;

use app\common\library\helper;
use app\common\model\store\Api as ApiModel;

/**
 * 商家用户权限模型
 * Class Api
 * @package app\store\model\store
 */
class Api extends ApiModel
{
    /**
     * 获取权限列表 jstree格式
     * @param int|null $roleId 当前角色id
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getJsTree(int $roleId = null): string
    {
        $apiIds = is_null($roleId) ? [] : RoleAccess::getAccessIds($roleId);
        $jsTree = [];
        foreach ($this->getAll() as $item) {
            $jsTree[] = [
                'id' => $item['api_id'],
                'parent' => $item['parent_id'] > 0 ? $item['parent_id'] : '#',
                'text' => $item['name'],
                'state' => [
                    'selected' => (in_array($item['api_id'], $apiIds) && !$this->hasChildren($item['api_id']))
                ]
            ];
        }
        return helper::jsonEncode($jsTree);
    }

    /**
     * 是否存在子集
     * @param $apiId
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function hasChildren($apiId): bool
    {
        foreach (self::getAll() as $item) {
            if ($item['parent_id'] == $apiId)
                return true;
        }
        return false;
    }
}
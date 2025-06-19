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

use app\common\model\store\Menu as MenuModel;

/**
 * 商家后台菜单模型
 * Class Menu
 * @package app\store\model\store
 */
class Menu extends MenuModel
{
    // 隐藏的字段
    protected $hidden = [
        'action_mark',
        'sort',
        'create_time',
        'update_time'
    ];

    /**
     * 根据菜单ID集获取列表
     * @param array $menuIds
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getListByIds(array $menuIds)
    {
        // 菜单列表
        $list = static::getAll([['menu_id', 'in', $menuIds]]);
        // 获取树状菜单列表
        return (new static)->getTreeData($list);
    }

    /**
     * 获取菜单列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(): array
    {
        // 获取所有菜单
        $menuList = $this->getTreeData(static::getAll()->toArray());
        // 过滤空子项的菜单
        return $this->filterEmptyChild($menuList);
    }

    /**
     * 过滤空子项的菜单
     * @param array $menuList
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private static function filterEmptyChild(array $menuList): array
    {
        foreach ($menuList as $key => &$item) {
            // 判断当前菜单不是页面, 并且子集为空
            if (!$item['is_page'] && empty($item['children'])) {
                unset($menuList[$key]);
                continue;
            }
            // 递归处理下级
            if (!empty($item['children'])) {
                $item['children'] = self::filterEmptyChild($item['children']);
            }
        }
        return \array_values($menuList);
    }
}

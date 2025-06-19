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

namespace app\common\model\store;

use cores\BaseModel;
use app\common\library\helper;
use think\model\relation\HasMany;

/**
 * 商家后台菜单模型
 * Class Menu
 * @package app\common\model\admin
 */
class Menu extends BaseModel
{
    // 定义表名
    protected $name = 'store_menu';

    // 定义表主键
    protected $pk = 'menu_id';

    /**
     * 关联操作权限
     * @return HasMany
     */
    public function menuApi(): HasMany
    {
        return $this->hasMany('MenuApi', 'menu_id');
    }

    /**
     * 获取所有菜单
     * @param array $where
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected static function getAll(array $where = []): \think\Collection
    {
        // 菜单列表
        $model = (new static)->addHidden(['menuApi']);
        $list = static::withoutGlobalScope()
            ->with(['menuApi'])
            ->where($where)
            ->order(['sort' => 'asc', 'create_time' => 'asc'])
            ->select();
        // 整理菜单绑定的apiID集
        return $model->getMenuApiIds($list);
    }

    /**
     * 获取树状菜单列表
     * @param $menuList
     * @param int $parentId
     * @return array
     */
    protected function getTreeData($menuList, int $parentId = 0): array
    {
        $data = [];
        foreach ($menuList as $key => $item) {
            if ($item['parent_id'] == $parentId) {
                $children = $this->getTreeData($menuList, (int)$item['menu_id']);
                !empty($children) && $item['children'] = $children;
                $data[] = $item;
                unset($menuList[$key]);
            }
        }
        return $data;
    }

    /**
     * 整理菜单的api ID集
     * @param $menuList
     * @return mixed
     */
    private function getMenuApiIds($menuList)
    {
        foreach ($menuList as &$item) {
            if (!empty($item['menuApi'])) {
                $item['apiIds'] = helper::getArrayColumn($item['menuApi'], 'api_id');
            }
        }
        return $menuList;
    }

    /**
     * 菜单信息
     * @param int|array $where
     * @return static|array|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function detail($where)
    {
        $query = static::withoutGlobalScope();
        is_array($where) ? $query->where($where) : $query->where('menu_id', '=', $where);
        return $query->find();
    }
}

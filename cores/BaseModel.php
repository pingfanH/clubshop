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

namespace cores;

use think\Model;
use think\db\Query;
use think\helper\Str;
use app\common\library\helper;
use cores\traits\ErrorTrait;

/**
 * 模型基类
 * Class BaseModel
 * @package app\common\model
 */
abstract class BaseModel extends Model
{
    use ErrorTrait;

    // 当前访问的商城ID
    public static ?int $storeId = null;

    // 定义表名
    protected $name;

    // 模型别名
    protected string $alias = '';

    // 定义全局的查询范围
    protected $globalScope = ['store_id'];

    // 是否允许全局查询store_id
    protected bool $isGlobalScopeStoreId = true;

    /**
     * 模型基类初始化
     */
    public static function init()
    {
        parent::init();
        // 绑定store_id
        self::getStoreId();
    }

    /**
     * 获取当前操作的商城ID
     * @return int|null
     */
    private static function getStoreId(): ?int
    {
        if (empty(self::$storeId) && \in_array(\app_name(), ['store', 'api'])) {
            self::$storeId = \getStoreId();
        }
        return self::$storeId;
    }

    /**
     * 获取当前调用来源的应用名称
     * 例如：admin, api, store
     * @return string
     */
    protected final static function getCalledModule(): string
    {
        if (preg_match('/app\\\(\w+)/', get_called_class(), $class)) {
            return $class[1];
        }
        return 'common';
    }

    /**
     * 查找单条记录
     * @param mixed $data 查询条件
     * @param array $with 关联查询
     * @return array|static|null
     */
    public static function get($data, array $with = [])
    {
        try {
            $query = (new static)->with($with);
            return is_array($data) ? $query->where($data)->find() : $query->find((int)$data);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 获取当前表名称 (不含前缀)
     * @return string
     */
    public static final function getTableName(): string
    {
        $model = new static;
        return Str::snake($model->name);
    }

    /**
     * 定义全局的查询范围
     * @param Query $query
     */
    public function scopeStore_id(Query $query)
    {
        if (!$this->isGlobalScopeStoreId) return;
        $storeId = self::getStoreId();
        $storeId > 0 && $query->where($query->getTable() . '.store_id', $storeId);
    }

    /**
     * 设置默认的检索数据
     * @param array $query
     * @param array $default
     * @return array
     */
    public function setQueryDefaultValue(array $query, array $default = []): array
    {
        return helper::setQueryDefaultValue($query, $default);
    }

    /**
     * 设置基础查询条件（用于简化基础alias和field）
     * @param string $alias
     * @param array $join
     * @return $this
     */
    public function setBaseQuery(string $alias = '', array $join = [])
    {
        // 设置别名
        $aliasValue = $alias ?: $this->alias;
        $query = $this->alias($aliasValue)->field("{$aliasValue}.*");
        // join条件
        if (!empty($join)) : foreach ($join as $item):
            $query->join($item[0], "{$item[0]}.{$item[1]}={$aliasValue}." . ($item[2] ?? $item[1]));
        endforeach; endif;
        return $query;
    }

    /**
     * 批量更新多条数据
     * @param iterable $dataSet [0 => ['data'=>[], 'where'=>[]]]
     * @return array|false
     */
    public function updateAll(iterable $dataSet)
    {
        if (empty($dataSet)) {
            return false;
        }
        return $this->transaction(function () use ($dataSet) {
            $result = [];
            foreach ($dataSet as $key => $item) {
                $result[$key] = self::updateBase($item['data'], $item['where']);
            }
            return $result;
        });
    }

    /**
     * 批量新增数据
     * @param iterable $dataSet [0 => ['id'=>10001, 'name'=>'wang']]
     * @return array|false
     */
    public function addAll(iterable $dataSet)
    {
        if (empty($dataSet)) {
            return false;
        }
        return $this->transaction(function () use ($dataSet) {
            $result = [];
            foreach ($dataSet as $key => $item) {
                $result[$key] = self::create($item, $this->field);
            }
            return $result;
        });
    }

    /**
     * 删除记录
     * @param array $where
     *  方式1: ['goods_id' => $goodsId]
     *  方式2: [
     *           ['store_user_id', '=', $storeUserId],
     *           ['role_id', 'in', $deleteRoleIds]
     *        ]
     * @return bool|int 这里实际返回的是数量int
     */
    public static function deleteAll(array $where)
    {
        return (new static)->where($where)->delete();
    }

    /**
     * 字段值增长
     * @param array|int|bool $where
     * @param string $field
     * @param float $step
     * @return mixed
     */
    protected function setInc($where, string $field, float $step = 1)
    {
        if (is_numeric($where)) {
            $where = [$this->getPk() => (int)$where];
        }
        return $this->where($where)->inc($field, $step)->update();
    }

    /**
     * 字段值消减
     * @param array|int|bool $where
     * @param string $field
     * @param float $step
     * @return mixed
     */
    protected function setDec($where, string $field, float $step = 1)
    {
        if (is_numeric($where)) {
            $where = [$this->getPk() => (int)$where];
        }
        return $this->where($where)->dec($field, $step)->update();
    }

    /**
     * 实例化新查询对象
     * @return \think\db\BaseQuery
     */
    protected function getNewQuery(): \think\db\BaseQuery
    {
        return $this->db();
    }

    /**
     * 新增hidden属性
     * @param array $hidden
     * @return $this
     */
    protected function addHidden(array $hidden): BaseModel
    {
        $this->hidden = array_merge($this->hidden, $hidden);
        return $this;
    }

    /**
     * 生成字段列表(字段加上$alias别名)
     * @param string $alias 别名
     * @param array $withoutFields 排除的字段
     * @return array
     */
    protected function getAliasFields(string $alias, array $withoutFields = []): array
    {
        $fields = array_diff($this->getTableFields(), $withoutFields);
        foreach ($fields as &$field) {
            $field = "$alias.$field";
        }
        return $fields;
    }

    /**
     * 更新数据[批量]
     * @param array $data 更新的数据内容
     * @param array|int $where 更新条件
     * @param array $allowField 允许的字段
     * @return bool
     */
    public static function updateBase(array $data, $where, array $allowField = []): bool
    {
        $model = new static;
        if (!empty($allowField)) {
            $model->allowField($allowField);
        }
        if (is_numeric($where)) {
            $where = [$model->getPk() => $where];
        }
        return $model->mySetUpdateWhere($where)->exists(true)->save($data);
    }

    /**
     * 设置模型的更新条件
     * @access protected
     * @param mixed $where 更新条件
     * @return static
     */
    public function mySetUpdateWhere($where): BaseModel
    {
        $this->setUpdateWhere($where);
        return $this;
    }

    /**
     * 获取隐藏的属性
     * @param array $hidden 合并的隐藏属性
     * @return array
     */
    public static function getHidden(array $hidden = []): array
    {
        return array_merge((new static)->hidden, $hidden);
    }
}

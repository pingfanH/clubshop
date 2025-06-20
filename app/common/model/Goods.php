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
use think\model\relation\BelongsTo;
use think\model\relation\HasMany;
use think\Paginator;
use think\model\Collection;
use think\model\relation\HasOne;
use app\store\model\GoodsCategoryRel as GoodsCategoryRelModel;
use app\common\library\helper;
use app\common\enum\goods\Status as GoodsStatusEnum;
use app\common\enum\order\DeliveryType as DeliveryTypeEnum;

/**
 * 商品模型
 * Class Goods
 * @package app\common\model
 */
class Goods extends BaseModel
{
    // 定义表名
    protected $name = 'goods';

    // 定义主键
    protected $pk = 'goods_id';

    // 追加字段
    protected $append = ['goods_sales'];

    /**
     * 关联模型：主图视频文件
     * @return HasOne
     */
    public function video(): HasOne
    {
        return $this->hasOne('UploadFile', 'file_id', 'video_id');
    }

    /**
     * 关联模型：主图视频封面图片文件
     * @return HasOne
     */
    public function videoCover(): HasOne
    {
        return $this->hasOne('UploadFile', 'file_id', 'video_cover_id');
    }

    /**
     * 获取器：用户端显示的商品销量 (实际销量+初始销量)
     * @param $value
     * @param $data
     * @return mixed
     */
    public function getGoodsSalesAttr($value, $data)
    {
        return is_null($value) ? $data['sales_initial'] + $data['sales_actual'] : $value;
    }

    /**
     * 商品详情：HTML实体转换回普通字符
     * @param $value
     * @return string
     */
    public function getContentAttr($value): string
    {
        return htmlspecialchars_decode($value);
    }

    /**
     * 获取器：单独设置折扣的配置
     * @param $json
     * @return mixed
     */
    public function getAloneGradeEquityAttr($json)
    {
        return helper::jsonDecode($json);
    }

    /**
     * 获取器：商品配送方式
     * 如果配送方式为空,默认返回所有配送方式（用于后台商品管理时默认选中）
     * @param $json
     * @return mixed
     */
    public function getDeliveryTypeAttr($json)
    {
        $values = helper::jsonDecode($json);
        return $values ?: array_keys(DeliveryTypeEnum::data());
    }

    /**
     * 修改器：单独设置折扣的配置
     * @param $data
     * @return false|string
     */
    public function setAloneGradeEquityAttr($data)
    {
        return helper::jsonEncode($data);
    }

    /**
     * 修改器：商品配送方式
     * @param $data
     * @return false|string
     */
    public function setDeliveryTypeAttr($data)
    {
        return helper::jsonEncode($data);
    }

    /**
     * 关联商品规格表
     * @return HasMany
     */
    public function skuList(): HasMany
    {
        return $this->hasMany('GoodsSku')->order(['id' => 'asc']);
    }

    /**
     * 关联商品规格关系表
     * @return HasMany
     */
    public function specRel(): HasMany
    {
        return $this->hasMany('GoodsSpecRel');
    }

    /**
     * 关联商品图片表
     * @return HasMany
     */
    public function images(): HasMany
    {
        return $this->hasMany('GoodsImage')->order(['id']);
    }

    /**
     * 关联运费模板表
     * @return BelongsTo
     */
    public function delivery(): BelongsTo
    {
        return $this->BelongsTo('Delivery');
    }

    /**
     * 关联订单评价表
     * @return HasMany
     */
    public function commentData(): HasMany
    {
        return $this->hasMany('Comment');
    }

    /**
     * 获取商品列表
     * @param array $param 查询条件
     * @param int $listRows 分页数量
     * @return mixed
     * @throws \think\db\exception\DbException
     */
    public function getList(array $param = [], int $listRows = 15)
    {
        // 筛选条件
        $query = $this->getQueryFilter($param);
        // 设置显示的销量goods_sales (此处无法省略因为需要根据goods_sales排序)
        $query->field(['(sales_initial + sales_actual) as goods_sales']);
        // 排序条件
        $sort = $this->setQuerySort($param);
        // 执行查询
        $list = $query->with(['images.file'])
            ->alias($this->name)
            ->field($this->getAliasFields($this->name, ['content']))
            ->where('is_delete', '=', 0)
            ->order($sort)
            ->paginate($listRows);
        // 整理列表数据并返回
        return $this->setGoodsListData($list);
    }

    /**
     * 检索排序条件
     * @param array $param
     * @return array|string[]
     */
    private function setQuerySort(array $param = []): array
    {
        $params = $this->setQueryDefaultValue($param, [
            'sortType' => 'all',    // 排序类型 (all默认 sales销量 price价格)
            'sortPrice' => false,   // 价格排序 (true高到低 false低到高)
        ]);
        // 排序规则
        $sort = [];
        if ($params['sortType'] === 'all') {
            $sort = ['sort' => 'asc'];
        } elseif ($params['sortType'] === 'sales') {
            $sort = ['goods_sales' => 'desc'];
        } elseif ($params['sortType'] === 'price') {
            $sort = $params['sortPrice'] ? ['goods_price_max' => 'desc'] : ['goods_price_min' => 'asc'];
        }
        return \array_merge($sort, [$this->getPk() => 'desc']);
    }

    /**
     * 检索查询条件
     * @param array $param
     * @return \think\db\BaseQuery
     */
    private function getQueryFilter(array $param): \think\db\BaseQuery
    {
        // 商品列表获取条件
        $params = $this->setQueryDefaultValue($param, [
            'listType' => 'all',     // 列表模式 (全部:all 出售中:on_sale 已下架:off_sale 已售罄:sold_out)
            'categoryId' => null,    // 商品分类ID
            'goodsName' => null,     // 商品名称
            'goodsNo' => null,       // 商品编码
            'status' => 0,           // 商品状态(0全部 10上架 20下架)
        ]);
        // 实例化新查询对象
        $query = $this->getNewQuery();
        // 筛选条件
        $filter = [];
        // 列表模式
        if ($params['listType'] === 'on_sale') {
            $filter[] = ['status', '=', GoodsStatusEnum::ON_SALE];        // 出售中
        } elseif ($params['listType'] === 'off_sale') {
            $filter[] = ['status', '=', GoodsStatusEnum::OFF_SALE];        // 已下架
        } elseif ($params['listType'] === 'sold_out') {
            $filter[] = ['stock_total', '=', 0];    // 已售罄
        }
        // 商品状态
        $params['status'] > 0 && $filter[] = ['status', '=', (int)$params['status']];
        // 商品分类
        if ($params['categoryId'] > 0) {
            // 关联商品与分类关系记录表
            $GoodsCategoryRelName = (new GoodsCategoryRelModel)->getName();
            $query->join($GoodsCategoryRelName, "{$GoodsCategoryRelName}.goods_id = {$this->name}.goods_id");
            // 设置分类ID条件
            $query->where('goods_category_rel.category_id', '=', (int)$params['categoryId']);
        }
        // 商品名称
        !empty($params['goodsName']) && $filter[] = ['goods_name', 'like', "%{$params['goodsName']}%"];
        // 商品编码
        !empty($params['goodsNo']) && $filter[] = ['goods_no', 'like', "%{$params['goodsNo']}%"];
        // 实例化新查询对象
        return $query->where($filter);
    }

    /**
     * 设置商品展示的数据
     * @param Collection|Paginator $list 商品列表
     * @param callable|null $callback 回调函数
     * @return mixed
     */
    protected function setGoodsListData($list, callable $callback = null)
    {
        if ($list->isEmpty()) return $list;
        // 遍历商品列表整理数据
        foreach ($list as &$goods) {
            $goods = $this->setGoodsData($goods, $callback);
        }
        return $list;
    }

    /**
     * 整理商品数据
     * @param Collection|static $goodsInfo
     * @param callable|null $callback
     * @return mixed
     */
    protected function setGoodsData($goodsInfo, callable $callback = null)
    {
        // 商品图片列表
        $goodsInfo['goods_images'] = helper::getArrayColumn($goodsInfo['images'], 'file');
        // 商品主图
        $goodsInfo['goods_image'] = current($goodsInfo['goods_images'])['preview_url'];
        // 回调函数
        is_callable($callback) && call_user_func($callback, $goodsInfo);
        return $goodsInfo;
    }

    /**
     * 根据商品id集获取商品列表
     * @param array $goodsIds
     * @param null $status
     * @return array|mixed
     */
    public function getListByIds(array $goodsIds, $status = null)
    {
        // 筛选条件
        $filter = [['goods_id', 'in', $goodsIds]];
        // 商品状态
        $status > 0 && $filter[] = ['status', '=', $status];
        // 获取商品列表数据
        $data = $this->withoutField(['content'])
            ->with(['images.file'])
            ->where($filter)
            ->where('is_delete', '=', 0)
            ->orderRaw('field(goods_id, ' . implode(',', $goodsIds) . ')')
            ->select();
        // 整理列表数据并返回
        return $this->setGoodsListData($data);
    }

    /**
     * 获取商品记录
     * @param int $goodsId
     * @param array $with
     * @return static|array|null
     */
    public static function detail(int $goodsId, array $with = [])
    {
        return static::get($goodsId, $with);
    }
}

<?php
declare (strict_types=1);

namespace app\api\controller;

use app\store\model\Goods as GoodsModel; // 使用 store 模块的模型以复用 add/edit 逻辑
use app\api\model\Goods as ApiGoodsModel; // 用于 list
use app\common\enum\goods\Status as GoodsStatusEnum;

/**
 * 商家商品管理
 */
class MerchantGoods extends Controller
{
    /**
     * 我的商品列表
     */
    public function list()
    {
        $user = $this->getLoginUser();
        if (!$user['is_merchant']) return $this->renderError('无权访问');
        
        $model = new ApiGoodsModel;
        $params = $this->request->param();
        
        // 自定义查询逻辑
        $query = $model->where('merchant_id', $user['merchant_id'])
                      ->where('store_id', $this->storeId)
                      ->where('is_delete', 0)
                      ->order(['create_time' => 'desc']);
                      
        // 状态过滤
        if (isset($params['status']) && $params['status'] > 0) {
             $query->where('status', $params['status']);
        }
        
        $list = $query->paginate($params['listRows'] ?? 15);
        return $this->renderSuccess(compact('list'));
    }
    
    /**
     * 添加商品
     */
    public function add()
    {
        $user = $this->getLoginUser();
        if (!$user['is_merchant']) return $this->renderError('无权访问');
        
        $data = $this->request->post();
        // 强制设置 merchant_id
        $data['merchant_id'] = $user['merchant_id'];
        $data['store_id'] = $this->storeId;
        
        // 简单的验证
        if (empty($data['goods_name'])) return $this->renderError('请输入商品名称');
        
        // 这里使用 store 模块的模型
        $model = new GoodsModel;
        
        try {
            if ($model->add($data)) {
                // 如果 add 方法内部覆盖了 merchant_id，我们需要手动更新一下
                // 但由于 add 方法调用了 save($data)，如果 $data 中包含 merchant_id 且未被 unset，应该会保存
                return $this->renderSuccess([], '添加成功');
            }
        } catch (\Exception $e) {
            return $this->renderError($e->getMessage());
        }
        return $this->renderError($model->getError() ?: '添加失败');
    }
    
    /**
     * 编辑商品
     */
    public function edit($goodsId)
    {
        $user = $this->getLoginUser();
        if (!$user['is_merchant']) return $this->renderError('无权访问');
        
        // 获取详情
        $model = GoodsModel::detail($goodsId);
        if (!$model || $model['merchant_id'] != $user['merchant_id']) {
             return $this->renderError('商品不存在或无权编辑');
        }
        
        if ($this->request->isGet()) {
            // 获取关联数据用于回显
            $detail = $model->getDetail((int)$goodsId);
            return $this->renderSuccess(compact('detail'));
        }
        
        try {
            $data = $this->request->post();
            // 确保 merchant_id 不被修改，或者强制设置
            $data['merchant_id'] = $user['merchant_id'];
            
            if ($model->edit($data)) {
                return $this->renderSuccess([], '更新成功');
            }
        } catch (\Exception $e) {
            return $this->renderError($e->getMessage());
        }
        return $this->renderError($model->getError() ?: '更新失败');
    }
    
    /**
     * 删除商品
     */
    public function delete($goodsId)
    {
        $user = $this->getLoginUser();
        if (!$user['is_merchant']) return $this->renderError('无权访问');
        
        $model = GoodsModel::detail($goodsId);
        if (!$model || $model['merchant_id'] != $user['merchant_id']) {
             return $this->renderError('商品不存在或无权删除');
        }
        
        // setDelete 接受数组
        if ($model->setDelete([(int)$goodsId])) {
            return $this->renderSuccess([], '删除成功');
        }
        return $this->renderError('删除失败');
    }
}

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

namespace app\api\controller;

use think\response\Json;
use app\api\model\Order as OrderModel;
use app\api\service\Cart as CartService;
use app\api\service\order\Checkout as CheckoutService;
use app\api\validate\order\Checkout as CheckoutValidate;

/**
 * 订单结算控制器
 * Class Checkout
 * @package app\api\controller
 */
class Checkout extends Controller
{
    // 结算台验证器
    private ?CheckoutValidate $validate = null;

    /**
     * 结算台订单信息
     * @param string $mode
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \cores\exception\BaseException
     */
    public function order(string $mode = 'buyNow'): Json
    {
        if ($mode === 'buyNow') {
            return $this->buyNow();
        } elseif ($mode === 'cart') {
            return $this->cart();
        }
        return $this->renderError('结算模式不合法');
    }

    /**
     * 订单提交
     * @param string $mode
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \cores\exception\BaseException
     */
    public function submit(string $mode = 'buyNow'): Json
    {
        return $this->order($mode);
    }

    /**
     * 订单确认-立即购买
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \cores\exception\BaseException
     */
    private function buyNow(): Json
    {
        // 实例化结算台服务
        $Checkout = new CheckoutService;
        // 订单结算api参数
        $params = $Checkout->setParam($this->getParam([
            'goodsId' => 0,
            'goodsSkuId' => '',
            'goodsNum' => 0,
        ]));
        // 表单验证
        if (!$this->getValidate()->scene('buyNow')->check($params)) {
            return $this->renderError($this->getValidate()->getError(), ['isCreated' => false]);
        }
        // 立即购买：获取订单商品列表
        $model = new OrderModel;
        $goodsList = $model->getOrderGoodsListByNow(
            (int)$params['goodsId'],
            (string)$params['goodsSkuId'],
            (int)$params['goodsNum']
        );
        // 获取订单确认信息
        $orderInfo = $Checkout->onCheckout($goodsList);
        if ($this->request->isGet()) {
            return $this->renderSuccess([
                'order' => $orderInfo,
                'personal' => $Checkout->getPersonal(),
                'setting' => $Checkout->getSetting(),
            ]);
        }
        // 验证订单是否存在错误
        if ($Checkout->hasError()) {
            return $this->renderError($Checkout->getError(), ['isCreated' => false]);
        }
        // 创建订单
        if (!$Checkout->createOrder($orderInfo)) {
            return $this->renderError($Checkout->getError() ?: '订单创建失败', ['isCreated' => false]);
        }
        // 返回状态
        return $this->renderSuccess(['orderId' => (int)$Checkout->model['order_id']], '订单创建成功');
    }

    /**
     * 订单确认-购物车结算
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \cores\exception\BaseException
     */
    private function cart(): Json
    {
        // 实例化结算台服务
        $Checkout = new CheckoutService;
        // 订单结算api参数
        $params = $Checkout->setParam($this->getParam());
        // 购物车ID集
        $cartIds = $this->getCartIds();
        // 商品结算信息
        $CartModel = new CartService;
        // 购物车商品列表
        $goodsList = $CartModel->getOrderGoodsList($cartIds);
        // 获取订单结算信息
        $orderInfo = $Checkout->onCheckout($goodsList);
        if ($this->request->isGet()) {
            return $this->renderSuccess([
                'order' => $orderInfo,
                'personal' => $Checkout->getPersonal(),
                'setting' => $Checkout->getSetting(),
            ]);
        }
        // 验证订单是否存在错误
        if ($Checkout->hasError()) {
            return $this->renderError($Checkout->getError(), ['isCreated' => false]);
        }
        // 创建订单
        if (!$Checkout->createOrder($orderInfo)) {
            return $this->renderError($Checkout->getError() ?: '订单创建失败');
        }
        // 移出购物车中已下单的商品
        $CartModel->clear($cartIds);
        // 返回状态
        return $this->renderSuccess(['orderId' => $Checkout->model['order_id']], '订单创建成功');
    }

    /**
     * 获取结算台验证器
     * @return CheckoutValidate
     */
    private function getValidate(): CheckoutValidate
    {
        if (\is_null($this->validate)) {
            $this->validate = new CheckoutValidate;
        }
        return $this->validate;
    }

    /**
     * 获取购物车ID集
     * @return false|string[]
     */
    private function getCartIds()
    {
        $cartIds = $this->request->param('cartIds');
        return \explode(',', $cartIds);
    }

    /**
     * 订单结算提交的参数
     * @param array $define
     * @return array
     */
    private function getParam(array $define = []): array
    {
        return \array_merge($define, $this->request->param());
    }
}
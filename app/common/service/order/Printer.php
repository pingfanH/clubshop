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

namespace app\common\service\order;

use app\common\model\OrderAddress;
use app\common\model\Store as StoreModel;
use app\common\model\Printer as PrinterModel;
use app\common\model\store\Setting as SettingModel;
use app\common\enum\order\DeliveryType as DeliveryTypeEnum;
use app\common\library\printer\Driver as PrinterDriver;
use app\common\service\BaseService;
use app\common\service\Goods as GoodsService;
use cores\exception\BaseException;

/**
 * 订单打印服务类
 * Class Printer
 * @package app\common\service\order
 */
class Printer extends BaseService
{
    /**
     * 执行订单打印 (手动)
     * @param mixed $order 订单信息
     * @param int $printerId 打印机ID
     * @return bool|mixed
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function printEvent($order, int $printerId)
    {
        // 获取当前的打印机
        $printer = PrinterModel::detail($printerId);
        if (empty($printer) || $printer['is_delete']) {
            return false;
        }
        // 实例化打印机驱动
        $PrinterDriver = new PrinterDriver($printer);
        // 获取订单打印内容
        $content = $this->getPrintContent($order);
        // 执行打印请求
        $status = $PrinterDriver->printTicket($content);
        if ($status === false) {
            $this->error = $PrinterDriver->getError();
        }
        return $status;
    }

    /**
     * 执行订单打印 (自动)
     * @param mixed $order 订单信息
     * @param int $scene 场景值
     * @return bool
     * @throws BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function printTicket($order, int $scene): bool
    {
        // 打印机设置
        $printerConfig = SettingModel::getItem('printer', $order['store_id']);
        // 判断是否开启打印设置
        if (!$printerConfig['is_open']
            || !$printerConfig['printer_id']
            || !in_array($scene, $printerConfig['order_status'])) {
            return false;
        }
        // 获取当前的打印机
        $printer = PrinterModel::detail($printerConfig['printer_id']);
        if (empty($printer) || $printer['is_delete']) {
            return false;
        }
        // 实例化打印机驱动
        $PrinterDriver = new PrinterDriver($printer);
        // 获取订单打印内容
        $content = $this->getPrintContent($order);
        // 执行打印请求
        return $PrinterDriver->printTicket($content);
    }

    /**
     * 构建订单打印的内容
     * @param $order
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getPrintContent($order): string
    {
        // 商城信息
        $storeInfo = StoreModel::detail($order['store_id']);
        // 收货地址
        /* @var OrderAddress $address */
        $address = $order['address'];
        // 拼接模板内容
        $content = "<CB>{$storeInfo['store_name']}</CB><BR>";
        $content .= '--------------------------------<BR>';
        $content .= "昵称：{$order['user']['nick_name']} [{$order['user_id']}]<BR>";
        $content .= "订单号：{$order['order_no']}<BR>";
        $content .= "付款时间：{$order['pay_time']}<BR>";
        // 收货人信息
        if ($order['delivery_type'] == DeliveryTypeEnum::EXPRESS) {
            $content .= "--------------------------------<BR>";
            $content .= "收货人：{$address['name']}<BR>";
            $content .= "联系电话：{$address['phone']}<BR>";
            $content .= '收货地址：' . $address->getFullAddress() . '<BR>';
        }
        // 商品信息
        $content .= '=========== 商品信息 ===========<BR>';
        foreach ($order['goods'] as $key => $goods) {
            $content .= ($key + 1) . ".商品名称：{$goods['goods_name']}<BR>";
            if (!empty($goods['goods_props'])) {
                $content .= '　商品规格：' . GoodsService::goodsPropsToAttr($goods['goods_props']) . '<BR>';
            }
            $content .= "　购买数量：{$goods['total_num']}<BR>";
            $content .= "　商品总价：{$goods['total_price']}元<BR>";
            $content .= '--------------------------------<BR>';
        }
        // 买家备注
        if (!empty($order['buyer_remark'])) {
            $content .= '============ 买家备注 ============<BR>';
            $content .= "<B>{$order['buyer_remark']}</B><BR>";
            $content .= '--------------------------------<BR>';
        }
        // 订单金额
        if ($order['coupon_money'] > 0) {
            $content .= "优惠券：-{$order['coupon_money']}元<BR>";
        }
        if ($order['points_num'] > 0) {
            $content .= "积分抵扣：-{$order['points_money']}元<BR>";
        }
        if ($order['update_price']['value'] != '0.00') {
            $content .= "后台改价：{$order['update_price']['symbol']}{$order['update_price']['value']}元<BR>";
        }
        // 运费
        if ($order['delivery_type'] == DeliveryTypeEnum::EXPRESS) {
            $content .= "运费：{$order['express_price']}元<BR>";
            $content .= '------------------------------<BR>';
        }
        // 实付款
        $content .= "<RIGHT>实付款：<BOLD><B>{$order['pay_price']}</B></BOLD>元</RIGHT><BR>";
        return $content;
    }
}

## 本文件是v2.1.2版本的数据库修改记录，通过查看version.json文件确定当前系统版本号
## 说明：如果你当前的版本号小于v2.1.2，那么在升级时需要执行本文件的sql内容


# 物流公司记录表 - 是否删除
ALTER TABLE `yoshop_express`
ADD COLUMN `is_delete`  tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否删除' AFTER `sort`;

# 订单记录表 - 是否已同步微信小程序发货信息管理
ALTER TABLE `yoshop_order`
ADD COLUMN `sync_weixin_shipping`  tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否已同步微信小程序发货信息管理' AFTER `delivery_time`;

# 用户优惠券记录表 - 优惠券描述
ALTER TABLE `yoshop_user_coupon`
ADD COLUMN `describe` varchar(500) NOT NULL DEFAULT '' COMMENT '优惠券描述' AFTER `apply_range_config`;

# 商城支付模板记录表 - 微信支付V3平台证书序号
ALTER TABLE `yoshop_payment_template`
MODIFY COLUMN `wechatpay_serial`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '微信支付V3平台证书序号或微信支付公钥ID' AFTER `remarks`;

INSERT INTO `yoshop_store_api` VALUES ('11367', '查询物流跟踪信息', '/order.delivery/traces', '11318', '125', '1614556800', '1614556800');

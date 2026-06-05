-- 商品表添加定金字段
ALTER TABLE `yoshop_goods`
ADD COLUMN `pay_type` tinyint(3) unsigned NOT NULL DEFAULT '10' COMMENT '支付方式 10全款 20定金' AFTER `status`,
ADD COLUMN `deposit_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '定金金额' AFTER `pay_type`;

-- 订单表添加定金字段
ALTER TABLE `yoshop_order`
ADD COLUMN `pay_type` tinyint(3) unsigned NOT NULL DEFAULT '10' COMMENT '支付方式 10全款 20定金' AFTER `pay_method`,
ADD COLUMN `deposit_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '定金金额' AFTER `pay_type`,
ADD COLUMN `deposit_pay_status` tinyint(3) unsigned NOT NULL DEFAULT '10' COMMENT '定金支付状态 10未付 20已付' AFTER `deposit_price`,
ADD COLUMN `deposit_pay_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '定金支付时间' AFTER `deposit_pay_status`,
ADD COLUMN `final_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '尾款金额' AFTER `deposit_pay_time`,
ADD COLUMN `final_pay_status` tinyint(3) unsigned NOT NULL DEFAULT '10' COMMENT '尾款支付状态 10未付 20已付' AFTER `final_price`,
ADD COLUMN `final_pay_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '尾款支付时间' AFTER `final_pay_status`;

CREATE TABLE IF NOT EXISTS `yoshop_merchant` (
  `merchant_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '关联用户ID',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '商户名称',
  `logo_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'Logo文件ID',
  `description` varchar(500) NOT NULL DEFAULT '' COMMENT '简介',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '10' COMMENT '状态(10申请中 20营业中 30已拒绝 40已下架)',
  `store_id` int(11) unsigned NOT NULL DEFAULT '10001' COMMENT '商城ID',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0',
  `delete_time` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`merchant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商户表';

CREATE TABLE IF NOT EXISTS `yoshop_chat_message` (
  `message_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `merchant_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户ID',
  `sender_type` tinyint(3) unsigned NOT NULL DEFAULT '10' COMMENT '发送者(10用户 20商户)',
  `content` text COMMENT '消息内容',
  `type` tinyint(3) unsigned NOT NULL DEFAULT '10' COMMENT '消息类型(10文本 20图片 30商品)',
  `is_read` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否已读',
  `store_id` int(11) unsigned NOT NULL DEFAULT '10001' COMMENT '商城ID',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='聊天记录表';

ALTER TABLE `yoshop_goods` ADD COLUMN `merchant_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户ID' AFTER `goods_id`;
ALTER TABLE `yoshop_order` ADD COLUMN `merchant_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户ID' AFTER `order_id`;

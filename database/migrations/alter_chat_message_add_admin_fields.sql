-- 聊天消息表添加管理员相关字段
ALTER TABLE `yoshop_chat_message` 
ADD COLUMN `store_user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '回复的管理员ID' AFTER `merchant_id`,
ADD COLUMN `sender_name` varchar(100) NOT NULL DEFAULT '' COMMENT '发送者名称' AFTER `sender_type`,
ADD COLUMN `sender_avatar` varchar(500) NOT NULL DEFAULT '' COMMENT '发送者头像' AFTER `sender_name`;

-- 添加索引
ALTER TABLE `yoshop_chat_message` ADD INDEX `store_user_id` (`store_user_id`);

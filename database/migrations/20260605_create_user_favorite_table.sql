-- 商品收藏表
CREATE TABLE IF NOT EXISTS `yoshop_user_favorite` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `goods_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品ID',
  `store_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商城ID',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `goods_id` (`goods_id`),
  KEY `store_id` (`store_id`),
  UNIQUE KEY `user_goods` (`user_id`, `goods_id`, `store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商品收藏表';

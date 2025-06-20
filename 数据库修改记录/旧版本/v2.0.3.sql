
## 本文件是v2.0.3版本的数据库修改记录，通过查看version.json文件确定当前系统版本号
## 说明：如果你当前的版本号小于v2.0.3，那么在升级时需要执行本文件的sql内容


# v2.0.3
# 修改时间：2021-10-19
DROP TABLE IF EXISTS `yoshop_order_export`;
CREATE TABLE `yoshop_order_export` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `start_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '下单时间(开始)',
  `end_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '下单时间(结束)',
  `file_path` varchar(255) NOT NULL DEFAULT '' COMMENT 'excel文件路径',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '导出状态(10进行中 20已完成 30失败)',
  `store_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商城ID',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `store_id` (`store_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10001 DEFAULT CHARSET=utf8 COMMENT='订单导出Excel记录表';


# v2.0.3
# 修改时间：2021-10-19
INSERT INTO `yoshop_store_api` VALUES ('11185', '删除订单', '/order.event/delete', '11132', '135', '1614556800', '1614556800');
INSERT INTO `yoshop_store_menu` VALUES ('10141', '20', '删除订单', '', 'delete', '10051', '115', '1614556800', '1614556800');
INSERT INTO `yoshop_store_menu_api` VALUES ('10661', '10141', '11185', '1614556800');
INSERT INTO `yoshop_store_menu_api` VALUES ('10662', '10141', '11132', '1614556800');
INSERT INTO `yoshop_store_menu_api` VALUES ('10663', '10141', '11076', '1614556800');


# v2.0.3
# 修改时间：2021-10-19
ALTER TABLE `yoshop_order`
ADD COLUMN `platform`  varchar(20) NOT NULL DEFAULT '' COMMENT '来源客户端 (APP、H5、小程序等)' AFTER `order_source_id`;

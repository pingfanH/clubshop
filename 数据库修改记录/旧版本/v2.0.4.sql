
## 本文件是v2.0.4版本的数据库修改记录，通过查看version.json文件确定当前系统版本号
## 说明：如果你当前的版本号小于v2.0.4，那么在升级时需要执行本文件的sql内容


# v2.0.4
# 修改时间：2021-11-29
ALTER TABLE `yoshop_wxapp` COMMENT='微信小程序记录表（已废弃）';

CREATE TABLE `yoshop_wxapp_setting` (
  `key` varchar(30) NOT NULL DEFAULT '' COMMENT '设置项标示',
  `describe` varchar(255) NOT NULL DEFAULT '' COMMENT '设置项描述',
  `values` mediumtext NOT NULL COMMENT '设置内容(json格式)',
  `store_id` int unsigned NOT NULL DEFAULT '0' COMMENT '商城ID',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  UNIQUE KEY `unique_key` (`key`,`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='微信小程序设置表';


CREATE TABLE `yoshop_h5_setting` (
  `key` varchar(30) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '设置项标示',
  `describe` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '设置项描述',
  `values` mediumtext CHARACTER SET utf8 NOT NULL COMMENT '设置内容(json格式)',
  `store_id` int unsigned NOT NULL DEFAULT '0' COMMENT '商城ID',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  UNIQUE KEY `unique_key` (`key`,`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='H5端设置表';


UPDATE `yoshop_store_api` SET `url`='/client.wxapp.setting/update', `sort`='105' WHERE (`api_id`='11153');
UPDATE `yoshop_store_api` SET `url`='/client.wxapp.setting/detail', `sort`='100' WHERE (`api_id`='11182');

INSERT INTO `yoshop_store_api` VALUES ('11270', 'H5端', '-', '11151', '105', '1614556800', '1614556800');
INSERT INTO `yoshop_store_api` VALUES ('11271', '获取设置项', '/client.h5.setting/detail', '11270', '100', '1614556800', '1614556800');
INSERT INTO `yoshop_store_api` VALUES ('11272', '更新设置项', '/client.h5.setting/update', '11270', '105', '1614556800', '1614556800');

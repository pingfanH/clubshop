
## 本文件是v2.0.7版本的数据库修改记录，通过查看version.json文件确定当前系统版本号
## 说明：如果你当前的版本号小于v2.0.7，那么在升级时需要执行本文件的sql内容


# v2.0.7
# 修改时间：2023-02-20
UPDATE `yoshop_region` SET `name`='北京市' WHERE (`id`='1');
UPDATE `yoshop_region` SET `name`='天津市' WHERE (`id`='19');
UPDATE `yoshop_region` SET `name`='上海市' WHERE (`id`='782');
UPDATE `yoshop_region` SET `name`='重庆市' WHERE (`id`='2223');
UPDATE `yoshop_region` SET `name`='北京市' WHERE (`id`='1');

# 修改时间：2022-04-18
INSERT INTO `yoshop_store_api` VALUES ('11186', '上传视频文件', '/upload/video', '11008', '112', '1614556800', '1614556800');

# 修改时间：2022-03-03
INSERT INTO `yoshop_store_menu_api` VALUES ('10664', '10015', '11025', '1614556800');
INSERT INTO `yoshop_store_menu_api` VALUES ('10665', '10015', '11022', '1614556800');
INSERT INTO `yoshop_store_menu_api` VALUES ('10666', '10015', '11020', '1614556800');


## 本文件是v2.0.5版本的数据库修改记录，通过查看version.json文件确定当前系统版本号
## 说明：如果你当前的版本号小于v2.0.5，那么在升级时需要执行本文件的sql内容


# v2.0.5
# 修改时间：2022-01-24
ALTER TABLE `yoshop_goods`
ADD COLUMN `video_id`  int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '主图视频ID' AFTER `goods_no`,
ADD COLUMN `video_cover_id`  int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '主图视频ID' AFTER `video_id`;

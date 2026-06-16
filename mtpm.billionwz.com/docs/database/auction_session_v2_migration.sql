-- 限时拍周期 v2（若未执行 v3，可先执行本文件；推荐直接执行 auction_session_v3_migration.sql）
-- v3 起：周期用 cate_a_id/cate_b_id，category 不再使用 session_id
-- 执行前请备份；表前缀默认 eb_

-- 周期表：去掉状态与日期（若列不存在可忽略报错）
ALTER TABLE `eb_store_auction_session`
  DROP COLUMN `start_date`,
  DROP COLUMN `end_date`,
  DROP COLUMN `status`;

CREATE TABLE IF NOT EXISTS `eb_store_auction_session` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL DEFAULT '' COMMENT '周期名称',
  `add_time` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='限时拍拍卖周期(绑定最多2个专场)';

-- 号牌表：移除 session_id（每专场 user_id+cate_id 一条）
ALTER TABLE `eb_store_number_plate` DROP INDEX `idx_user_session`;
ALTER TABLE `eb_store_number_plate` DROP COLUMN `session_id`;

-- 唯一约束：同一用户在同一专场仅一条号牌记录（同周期两场 = 两条，号牌号码可相同）
-- 若已有重复数据，先执行 number_plate_unique_migration.sql 中的清理示例再执行本句
ALTER TABLE `eb_store_number_plate`
  ADD UNIQUE KEY `uk_user_cate` (`user_id`, `cate_id`);

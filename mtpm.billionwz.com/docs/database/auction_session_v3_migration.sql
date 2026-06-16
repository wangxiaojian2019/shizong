-- 限时拍周期 v3：周期表用 cate_a_id / cate_b_id 绑定两个专场，category 表不再使用 session_id
-- 执行前请备份；表前缀默认 eb_

-- 1. 周期表增加专场 A、专场 B（必须绑定两个不同专场，由应用层校验）
ALTER TABLE `eb_store_auction_session`
  ADD COLUMN `cate_a_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '专场A(store_category.id)' AFTER `name`,
  ADD COLUMN `cate_b_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '专场B(store_category.id)' AFTER `cate_a_id`;

-- 若表不存在则创建（仅名称 + 两场专场 + 创建时间）
CREATE TABLE IF NOT EXISTS `eb_store_auction_session` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL DEFAULT '' COMMENT '周期名称',
  `cate_a_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '专场A',
  `cate_b_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '专场B',
  `add_time` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_cate_a` (`cate_a_id`),
  KEY `idx_cate_b` (`cate_b_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='限时拍周期(专场A+专场B共用号牌)';

-- 2. 从 category.session_id 迁移到周期表（若曾使用 v1/v2 的 session_id 字段，按需执行一次）
-- INSERT 新周期或 UPDATE 需根据现网数据手工处理；示例（请按实际 session/category 关系改写）：
-- UPDATE eb_store_auction_session s
-- INNER JOIN (
--   SELECT session_id, MIN(id) AS cate_a_id, MAX(id) AS cate_b_id
--   FROM eb_store_category WHERE session_id > 0 GROUP BY session_id HAVING COUNT(*) >= 2
-- ) t ON s.id = t.session_id
-- SET s.cate_a_id = t.cate_a_id, s.cate_b_id = t.cate_b_id;

-- 3. 移除 category 表上的 session_id（不再在专场表存周期）
ALTER TABLE `eb_store_category` DROP COLUMN `session_id`;

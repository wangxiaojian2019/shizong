-- 号牌表防重复登记（执行前请备份）
-- uk_user_cate：同一用户 + 同一专场(cate_id) 唯一一条；同周期两场共用号牌号码但各有一条记录
-- 若已存在重复 (user_id, cate_id) 需先清理再执行

-- 示例：保留 id 最大的一条
-- DELETE p1 FROM eb_store_number_plate p1
-- INNER JOIN eb_store_number_plate p2
--   ON p1.user_id = p2.user_id AND p1.cate_id = p2.cate_id AND p1.id < p2.id;

ALTER TABLE `eb_store_number_plate`
  ADD UNIQUE KEY `uk_user_cate` (`user_id`, `cate_id`);

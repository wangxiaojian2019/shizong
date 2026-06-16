-- 号牌获得类型：0=暂无 1=管理员设置 2=保证金缴纳
-- 执行前请备份；表前缀默认 eb_

ALTER TABLE `eb_store_number_plate`
  ADD COLUMN `plate_source` tinyint(1) NOT NULL DEFAULT 0 COMMENT '号牌获得类型 0暂无 1管理员 2保证金' AFTER `paystatus`;

-- 历史数据回填：已支付视为保证金获得，有号牌未支付视为管理员设置
UPDATE `eb_store_number_plate`
SET `plate_source` = 2
WHERE `number_plate` > 0 AND `paystatus` = 1;

UPDATE `eb_store_number_plate`
SET `plate_source` = 1
WHERE `number_plate` > 0 AND `paystatus` <> 1 AND `plate_source` = 0;

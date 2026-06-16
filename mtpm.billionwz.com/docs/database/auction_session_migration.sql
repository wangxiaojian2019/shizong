-- 限时拍双场并行 · 号牌通用 · 数据库变更
-- 执行前请备份；表前缀默认 eb_，若不同请替换

CREATE TABLE IF NOT EXISTS `eb_store_auction_session` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL DEFAULT '' COMMENT '周期名称',
  `start_date` date NOT NULL COMMENT '第1天',
  `end_date` date NOT NULL COMMENT '第2天',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0未开始 1进行中 2已结束',
  `add_time` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='限时拍拍卖周期(前后两天)';

CREATE TABLE IF NOT EXISTS `eb_store_timed_deposit` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT 0 COMMENT '用户ID',
  `paid_total` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '实缴金额，足额5000',
  `paystatus` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0未缴满 1已缴满 2已退',
  `application` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0无 1退款申请中',
  `update_time` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='限时拍用户保证金账户';

ALTER TABLE `eb_store_category`
  ADD COLUMN `session_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '拍卖周期ID，0=单场现网模式' AFTER `model`;

ALTER TABLE `eb_store_number_plate`
  ADD COLUMN `session_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '拍卖周期ID，0=按cate_id' AFTER `cate_id`,
  ADD KEY `idx_user_session` (`user_id`, `session_id`);

-- 限时拍保证金金额（后台可配，默认 5000）
INSERT INTO `eb_system_config` (`menu_name`, `type`, `input_type`, `config_tab_id`, `parameter`, `upload_type`, `required`, `width`, `high`, `value`, `info`, `desc`, `sort`, `status`, `level`, `link_id`, `link_value`)
SELECT 'timed_auction_deposit_amount', 'text', 'input', 0, '', 1, '', 100, 0, '5000', '限时拍保证金金额', '限时拍用户保证金金额（元），后台可修改', 0, 1, 0, 0, 0
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `eb_system_config` WHERE `menu_name` = 'timed_auction_deposit_amount');

-- 限时拍保证金默认金额改为 1 元
-- 执行前请备份；表前缀默认 eb_
-- 说明：仅修改系统配置项 timed_auction_deposit_amount；已上线环境执行本脚本即可，无需重跑 auction_session_migration.sql

-- 已存在配置项：更新为 1
UPDATE `eb_system_config`
SET `value` = '1',
    `info` = '限时拍保证金金额',
    `desc` = '限时拍用户保证金金额（元），后台可修改，默认1'
WHERE `menu_name` = 'timed_auction_deposit_amount';

-- 不存在时：插入默认 1
INSERT INTO `eb_system_config` (
  `menu_name`, `type`, `input_type`, `config_tab_id`, `parameter`, `upload_type`,
  `required`, `width`, `high`, `value`, `info`, `desc`, `sort`, `status`, `level`, `link_id`, `link_value`
)
SELECT
  'timed_auction_deposit_amount', 'text', 'input', 0, '', 1,
  '', 100, 0, '1', '限时拍保证金金额', '限时拍用户保证金金额（元），后台可修改，默认1', 0, 1, 0, 0, 0
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `eb_system_config` WHERE `menu_name` = 'timed_auction_deposit_amount'
);

-- 拍卖周期「修改 / 删除」接口权限（已执行过 auction_admin_menu_migration.sql 的库补跑此文件）
INSERT INTO `eb_system_menus` (`pid`, `menu_name`, `module`, `api_url`, `methods`, `params`, `sort`, `is_show`, `is_show_path`, `access`, `auth_type`, `unique_auth`, `is_del`)
SELECT p.`id`, '修改拍卖周期', 'admin', 'product/auction_session/<id>', 'PUT', '[]', 0, 1, 0, 1, 2, 'product-auction-session-update', 0
FROM `eb_system_menus` p
WHERE p.`unique_auth` = 'product-auction-session' AND p.`is_del` = 0
  AND NOT EXISTS (SELECT 1 FROM `eb_system_menus` c WHERE c.`unique_auth` = 'product-auction-session-update' AND c.`is_del` = 0)
LIMIT 1;

INSERT INTO `eb_system_menus` (`pid`, `menu_name`, `module`, `api_url`, `methods`, `params`, `sort`, `is_show`, `is_show_path`, `access`, `auth_type`, `unique_auth`, `is_del`)
SELECT p.`id`, '删除拍卖周期', 'admin', 'product/auction_session/<id>', 'DELETE', '[]', 0, 1, 0, 1, 2, 'product-auction-session-delete', 0
FROM `eb_system_menus` p
WHERE p.`unique_auth` = 'product-auction-session' AND p.`is_del` = 0
  AND NOT EXISTS (SELECT 1 FROM `eb_system_menus` c WHERE c.`unique_auth` = 'product-auction-session-delete' AND c.`is_del` = 0)
LIMIT 1;

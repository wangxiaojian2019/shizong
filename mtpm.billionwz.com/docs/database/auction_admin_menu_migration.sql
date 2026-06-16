-- 限时拍后台菜单：拍卖周期 + 保证金设置
-- 执行前请备份；表前缀默认 eb_
-- 父菜单「商品」pid=1，若贵司商品菜单 id 不是 1，请先：
-- SELECT id, menu_name FROM eb_system_menus WHERE pid=0 AND menu_name='商品';

-- ========== 1. 限时拍拍卖周期 ==========
INSERT INTO `eb_system_menus` (
  `pid`, `icon`, `menu_name`, `module`, `controller`, `action`,
  `api_url`, `methods`, `params`, `sort`, `is_show`, `is_show_path`, `access`,
  `menu_path`, `path`, `auth_type`, `header`, `is_header`, `unique_auth`, `is_del`, `mark`
)
SELECT
  1, '', '限时拍拍卖周期', 'admin', '', '',
  '', '', '[]', 54, 1, 1, 1,
  '/product/auction_session', '1', 1, 'product', 0, 'product-auction-session', 0, '绑定双场限时拍周期'
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `eb_system_menus` WHERE `unique_auth` = 'product-auction-session' AND `is_del` = 0
);

INSERT INTO `eb_system_menus` (`pid`, `menu_name`, `module`, `api_url`, `methods`, `params`, `sort`, `is_show`, `is_show_path`, `access`, `auth_type`, `unique_auth`, `is_del`)
SELECT p.`id`, '拍卖周期列表', 'admin', 'product/auction_session', 'GET', '[]', 0, 1, 0, 1, 2, 'product-auction-session-list', 0
FROM `eb_system_menus` p
WHERE p.`unique_auth` = 'product-auction-session' AND p.`is_del` = 0
  AND NOT EXISTS (SELECT 1 FROM `eb_system_menus` c WHERE c.`unique_auth` = 'product-auction-session-list' AND c.`is_del` = 0)
LIMIT 1;

INSERT INTO `eb_system_menus` (`pid`, `menu_name`, `module`, `api_url`, `methods`, `params`, `sort`, `is_show`, `is_show_path`, `access`, `auth_type`, `unique_auth`, `is_del`)
SELECT p.`id`, '可选限时拍专场', 'admin', 'product/auction_session/timed_categories', 'GET', '[]', 0, 1, 0, 1, 2, 'product-auction-session-cates', 0
FROM `eb_system_menus` p
WHERE p.`unique_auth` = 'product-auction-session' AND p.`is_del` = 0
  AND NOT EXISTS (SELECT 1 FROM `eb_system_menus` c WHERE c.`unique_auth` = 'product-auction-session-cates' AND c.`is_del` = 0)
LIMIT 1;

INSERT INTO `eb_system_menus` (`pid`, `menu_name`, `module`, `api_url`, `methods`, `params`, `sort`, `is_show`, `is_show_path`, `access`, `auth_type`, `unique_auth`, `is_del`)
SELECT p.`id`, '创建拍卖周期', 'admin', 'product/auction_session', 'POST', '[]', 0, 1, 0, 1, 2, 'product-auction-session-create', 0
FROM `eb_system_menus` p
WHERE p.`unique_auth` = 'product-auction-session' AND p.`is_del` = 0
  AND NOT EXISTS (SELECT 1 FROM `eb_system_menus` c WHERE c.`unique_auth` = 'product-auction-session-create' AND c.`is_del` = 0)
LIMIT 1;

INSERT INTO `eb_system_menus` (`pid`, `menu_name`, `module`, `api_url`, `methods`, `params`, `sort`, `is_show`, `is_show_path`, `access`, `auth_type`, `unique_auth`, `is_del`)
SELECT p.`id`, '绑定限时拍专场', 'admin', 'product/auction_session/bind', 'POST', '[]', 0, 1, 0, 1, 2, 'product-auction-session-bind', 0
FROM `eb_system_menus` p
WHERE p.`unique_auth` = 'product-auction-session' AND p.`is_del` = 0
  AND NOT EXISTS (SELECT 1 FROM `eb_system_menus` c WHERE c.`unique_auth` = 'product-auction-session-bind' AND c.`is_del` = 0)
LIMIT 1;

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

-- ========== 2. 限时拍保证金设置 ==========
INSERT INTO `eb_system_menus` (
  `pid`, `icon`, `menu_name`, `module`, `controller`, `action`,
  `api_url`, `methods`, `params`, `sort`, `is_show`, `is_show_path`, `access`,
  `menu_path`, `path`, `auth_type`, `header`, `is_header`, `unique_auth`, `is_del`, `mark`
)
SELECT
  1, '', '限时拍保证金设置', 'admin', '', '',
  '', '', '[]', 55, 1, 1, 1,
  '/product/timed_deposit_config', '1', 1, 'product', 0, 'product-timed-deposit-config', 0, '限时拍保证金金额'
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `eb_system_menus` WHERE `unique_auth` = 'product-timed-deposit-config' AND `is_del` = 0
);

INSERT INTO `eb_system_menus` (`pid`, `menu_name`, `module`, `api_url`, `methods`, `params`, `sort`, `is_show`, `is_show_path`, `access`, `auth_type`, `unique_auth`, `is_del`)
SELECT p.`id`, '读取限时拍保证金配置', 'admin', 'product/timed_deposit/config', 'GET', '[]', 0, 1, 0, 1, 2, 'product-timed-deposit-config-get', 0
FROM `eb_system_menus` p
WHERE p.`unique_auth` = 'product-timed-deposit-config' AND p.`is_del` = 0
  AND NOT EXISTS (SELECT 1 FROM `eb_system_menus` c WHERE c.`unique_auth` = 'product-timed-deposit-config-get' AND c.`is_del` = 0)
LIMIT 1;

INSERT INTO `eb_system_menus` (`pid`, `menu_name`, `module`, `api_url`, `methods`, `params`, `sort`, `is_show`, `is_show_path`, `access`, `auth_type`, `unique_auth`, `is_del`)
SELECT p.`id`, '保存限时拍保证金配置', 'admin', 'product/timed_deposit/config', 'POST', '[]', 0, 1, 0, 1, 2, 'product-timed-deposit-config-save', 0
FROM `eb_system_menus` p
WHERE p.`unique_auth` = 'product-timed-deposit-config' AND p.`is_del` = 0
  AND NOT EXISTS (SELECT 1 FROM `eb_system_menus` c WHERE c.`unique_auth` = 'product-timed-deposit-config-save' AND c.`is_del` = 0)
LIMIT 1;

-- 执行后：超级管理员角色一般自动拥有新菜单；其他角色请在「权限管理」中勾选上述菜单并保存。

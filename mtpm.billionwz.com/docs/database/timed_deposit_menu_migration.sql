-- 限时拍保证金设置 · 后台菜单与接口权限
-- 执行前请备份；表前缀默认 eb_
-- 父菜单「商品」pid=1，若贵司商品菜单 id 不是 1，请先查询：SELECT id, menu_name FROM eb_system_menus WHERE pid=0 AND menu_name='商品';

-- 1) 页面菜单（商品 → 限时拍保证金设置）
INSERT INTO `eb_system_menus` (
  `pid`, `icon`, `menu_name`, `module`, `controller`, `action`,
  `api_url`, `methods`, `params`, `sort`, `is_show`, `is_show_path`, `access`,
  `menu_path`, `path`, `auth_type`, `header`, `is_header`, `unique_auth`, `is_del`, `mark`
)
SELECT
  1, '', '限时拍保证金设置', 'admin', '', '',
  '', '', '[]', 55, 1, 1, 1,
  '/product/timed_deposit_config', '1', 1, 'product', 0, 'product-timed-deposit-config', 0, '限时拍保证金设置'
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `eb_system_menus` WHERE `unique_auth` = 'product-timed-deposit-config' AND `is_del` = 0
);

-- 2) 接口权限：读取配置
INSERT INTO `eb_system_menus` (
  `pid`, `icon`, `menu_name`, `module`, `controller`, `action`,
  `api_url`, `methods`, `params`, `sort`, `is_show`, `is_show_path`, `access`,
  `menu_path`, `path`, `auth_type`, `header`, `is_header`, `unique_auth`, `is_del`, `mark`
)
SELECT
  p.`id`, '', '读取限时拍保证金配置', 'admin', '', '',
  'product/timed_deposit/config', 'GET', '[]', 0, 1, 0, 1,
  '', '', 2, '', 0, 'product-timed-deposit-config-get', 0, ''
FROM `eb_system_menus` p
WHERE p.`unique_auth` = 'product-timed-deposit-config' AND p.`is_del` = 0
  AND NOT EXISTS (
    SELECT 1 FROM `eb_system_menus` c
    WHERE c.`unique_auth` = 'product-timed-deposit-config-get' AND c.`is_del` = 0
  )
LIMIT 1;

-- 3) 接口权限：保存配置
INSERT INTO `eb_system_menus` (
  `pid`, `icon`, `menu_name`, `module`, `controller`, `action`,
  `api_url`, `methods`, `params`, `sort`, `is_show`, `is_show_path`, `access`,
  `menu_path`, `path`, `auth_type`, `header`, `is_header`, `unique_auth`, `is_del`, `mark`
)
SELECT
  p.`id`, '', '保存限时拍保证金配置', 'admin', '', '',
  'product/timed_deposit/config', 'POST', '[]', 0, 1, 0, 1,
  '', '', 2, '', 0, 'product-timed-deposit-config-save', 0, ''
FROM `eb_system_menus` p
WHERE p.`unique_auth` = 'product-timed-deposit-config' AND p.`is_del` = 0
  AND NOT EXISTS (
    SELECT 1 FROM `eb_system_menus` c
    WHERE c.`unique_auth` = 'product-timed-deposit-config-save' AND c.`is_del` = 0
  )
LIMIT 1;

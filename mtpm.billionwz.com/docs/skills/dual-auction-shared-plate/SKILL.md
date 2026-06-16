---
name: dual-auction-shared-plate
description: >-
  限时拍双场最终方案：前后两天session、1牌2场、保证金5000独立、现网1场1牌兼容。
---

# 限时拍双场 · 最终方案

**定稿文档：** `mtpm.billionwz.com/docs/号牌通用与双场拍卖-最终方案需求.md`

## 核心规则

| 项 | 规则 |
|----|------|
| 周期 | 前后 **2 天** = `auction_session`，最多 **2 场**限时拍 |
| 号牌 B | session 内 **1 牌 2 场**；新 session **新牌** |
| 号牌 A | 无 session：**1 场 1 牌**（现网，必须兼容） |
| 保证金 | **仅 5000**；与号牌独立；不足须缴；未退可继续拍 |
| 代理 | 两场可同时代理；无 remaining |
| 同步拍 | **不改** |

## 参拍

```
has_plate AND paid_total==5000 AND paystatus==1
```

## 接口分支

```
cate_id → session_id 双场? → 模式B : 模式A(现网)
```

## 实施

P1 session+保证金 → P2 号牌双模式 → P3 双场列表+代理 → P4 后台

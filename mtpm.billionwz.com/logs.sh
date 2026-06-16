#!/bin/bash

PROJECT_LOG_DIR="/www/wwwroot/mtpm.billionwz.com/runtime/log"
STATE_FILE="/tmp/mtpm_log_watch.offset"
ALERT_TMP="/tmp/mtpm_log_watch.alert"

KEYWORDS="error|Borrow the connection timeout|PHP Fatal|Uncaught|SQLSTATE|Too many connections"

# 实时监控模式
monitor_logs() {
  while true; do
    LATEST_LOG=$(ls -t ${PROJECT_LOG_DIR}/*_error.log 2>/dev/null | head -n 1)
    [ -z "$LATEST_LOG" ] && sleep 5 && continue

    if [ ! -f "$STATE_FILE" ]; then
      echo "0" > "$STATE_FILE"
    fi

    LAST_POS=$(cat "$STATE_FILE")
    CUR_LINES=$(wc -l < "$LATEST_LOG")

    if [ "$CUR_LINES" -lt "$LAST_POS" ]; then
      LAST_POS=0
    fi

    sed -n "$((LAST_POS+1)),${CUR_LINES}p" "$LATEST_LOG" | egrep -i "$KEYWORDS" > "$ALERT_TMP"

    echo "$CUR_LINES" > "$STATE_FILE"

    if [ -s "$ALERT_TMP" ]; then
      echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
      echo "⚠️  [$(date '+%Y-%m-%d %H:%M:%S')] 发现告警！"
      echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
      cat "$ALERT_TMP"
      echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
      echo ""
    fi

    sleep 5  # 每5秒检查一次
  done
}

monitor_logs

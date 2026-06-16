/** 限时拍同周期最多展示场次数（与后台 TIMED_DISPLAY_MAX 一致） */
export const TIMED_AUCTION_MAX_DISPLAY = 2;

/** 同周期多专场时的说明文案 */
export const TIMED_AUCTION_TIP =
	'同一周期最多展示2场限时拍；号牌周期内共用。保证金独立：未退且足额可继续参拍，退清需重缴';

export const DEFAULT_TIMED_META = {
	max_display: TIMED_AUCTION_MAX_DISPLAY,
	display_count: 0,
	tip: TIMED_AUCTION_TIP,
};

/** 是否同周期多专场（需展示周期说明） */
export function isSameSessionMultiDisplay(sessionCount) {
	return Number(sessionCount) > 1;
}

/** 仅同期多场时返回说明文案，单场/非周期专场返回空串 */
export function resolveTimedTipForDisplay(sessionCount, tip) {
	if (!isSameSessionMultiDisplay(sessionCount)) {
		return '';
	}
	if (tip !== undefined && tip !== null) {
		return tip;
	}
	return TIMED_AUCTION_TIP;
}

/** 是否展示同周期说明（优先读接口 is_session_group） */
export function shouldShowTimedSessionTip(sessionCount, meta = {}) {
	if (meta.is_session_group === false) {
		return false;
	}
	if (meta.is_session_group === true) {
		return isSameSessionMultiDisplay(sessionCount);
	}
	if (meta.tip === '') {
		return false;
	}
	return isSameSessionMultiDisplay(sessionCount);
}

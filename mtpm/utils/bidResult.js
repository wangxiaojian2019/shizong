import { needAssignPlate } from '@/utils/timedDeposit.js';

/** 解析 request 拒绝时的出价错误文案 */
export function normalizeBidMessage(err) {
	if (!err) {
		return '出价失败';
	}
	if (typeof err === 'string') {
		return err;
	}
	if (err.msg) {
		return err.msg;
	}
	return '出价失败';
}

function showBidToast(msg) {
	uni.showToast({
		title: msg || '出价失败',
		icon: 'none',
		duration: 2500
	});
}

function promptBidAction(content, confirmText, onConfirm) {
	uni.showModal({
		title: '无法出价',
		content: content || '出价失败',
		confirmText: confirmText || '我知道了',
		showCancel: !!onConfirm,
		success(res) {
			if (res.confirm && onConfirm) {
				onConfirm();
			}
		}
	});
}

function shouldRefreshAuction(msg) {
	const keys = [
		'出价失败',
		'已被超越',
		'无需出价',
		'已结拍',
		'号牌状态已变更',
		'号牌无效',
		'拍品不存在'
	];
	return keys.some((k) => msg.indexOf(k) !== -1);
}

function guidePlateOrDeposit(vm, msg) {
	if (msg.indexOf('请先办理号牌') !== -1) {
		promptBidAction(msg, '办理号牌', () => vm.addNumberPlate && vm.addNumberPlate());
		return true;
	}
	if (msg.indexOf('请缴纳保证金') !== -1) {
		promptBidAction(msg, '去缴纳', () => {
			if (vm.pay) {
				if (vm.pay.length) {
					vm.pay(1);
				} else {
					vm.pay();
				}
			}
		});
		return true;
	}
	if (msg.indexOf('号牌待分配') !== -1) {
		promptBidAction(msg, needAssignPlate(vm) ? '获取号牌' : '去缴纳', () => {
			if (needAssignPlate(vm) && vm.retryAssignPlate) {
				vm.retryAssignPlate();
			} else if (vm.pay) {
				vm.pay.length ? vm.pay(1) : vm.pay();
			}
		});
		return true;
	}
	if (msg.indexOf('号牌状态已变更') !== -1 || msg.indexOf('号牌无效') !== -1) {
		promptBidAction(msg, '刷新', () => {
			vm.getPlateNumberdetails && vm.getPlateNumberdetails();
		});
		return true;
	}
	return false;
}

/**
 * 限时拍手动出价（auction 接口）结果处理
 */
export function handleTimedManualBidResult(vm, msg) {
	const text = normalizeBidMessage(msg);
	if (text === '出价成功') {
		showBidToast(text);
		vm.refreshAuctionState && vm.refreshAuctionState();
		return;
	}

	if (guidePlateOrDeposit(vm, text)) {
		if (shouldRefreshAuction(text)) {
			vm.refreshAuctionState && vm.refreshAuctionState();
		}
		return;
	}

	showBidToast(text);

	if (shouldRefreshAuction(text)) {
		vm.refreshAuctionState && vm.refreshAuctionState();
	}

	if (text.indexOf('已被超越') !== -1) {
		setTimeout(() => {
			uni.showModal({
				title: '提示',
				content: '当前价已更新，是否按最新价重新出价？',
				confirmText: '重新出价',
				success(res) {
					if (res.confirm && vm.reset) {
						vm.reset();
					}
				}
			});
		}, 600);
		return;
	}

	if (text === '出价失败') {
		setTimeout(() => {
			uni.showModal({
				title: '出价失败',
				content: '系统繁忙或他人抢先出价，已为您刷新最新价格，请稍后重试',
				showCancel: false
			});
		}, 600);
		return;
	}

	if (text.indexOf('已结拍') !== -1) {
		setTimeout(() => {
			vm.promptGoNextLotIfNeeded && vm.promptGoNextLotIfNeeded();
		}, 800);
	}
}

/**
 * 同步拍手动出价（synchronousAddPrice 接口）结果处理
 */
export function handleSyncManualBidResult(vm, msg) {
	const text = normalizeBidMessage(msg);
	if (text === '出价成功') {
		showBidToast(text);
		vm.productDetails && vm.productDetails();
		vm.getPlateNumberdetails && vm.getPlateNumberdetails(0);
		return;
	}

	if (guidePlateOrDeposit(vm, text)) {
		if (shouldRefreshAuction(text)) {
			vm.productDetails && vm.productDetails();
			vm.getPlateNumberdetails && vm.getPlateNumberdetails(0);
		}
		return;
	}

	showBidToast(text);

	if (shouldRefreshAuction(text)) {
		vm.productDetails && vm.productDetails();
		vm.getPlateNumberdetails && vm.getPlateNumberdetails(0);
	}

	if (text.indexOf('已被超越') !== -1 || text === '出价失败') {
		setTimeout(() => {
			uni.showModal({
				title: '提示',
				content: text.indexOf('已被超越') !== -1
					? '当前价已更新，请查看最新价后重新出价'
					: '出价未成功，已刷新最新价格，请稍后重试',
				showCancel: false
			});
		}, 600);
	}
}

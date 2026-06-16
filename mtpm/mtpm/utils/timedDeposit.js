import { getTimedDepositAmount, getNumberdetails } from '@/api/store.js';

/** 解析后台配置的限时拍保证金（元） */
export function parseDepositAmount(amount, fallback = 1) {
	const amt = Number(amount);
	if (!isNaN(amt) && amt > 0) {
		return amt;
	}
	return fallback;
}

/** 写入页面 data：deposit_amount / margin / need_pay（可选） */
export function applyTimedDepositToVm(vm, amount, options = {}) {
	const { updateNeedPay = false } = options;
	const fallback = vm.deposit_amount > 0 ? vm.deposit_amount : 1;
	const amt = parseDepositAmount(amount, fallback);
	vm.deposit_amount = amt;
	if (vm.margin !== undefined) {
		vm.margin = amt;
	}
	if (updateNeedPay && (vm.need_pay == null || vm.need_pay === '' || vm.need_pay === fallback)) {
		vm.need_pay = amt;
	}
	return amt;
}

/** 从 category/details 的 timed_deposit 字段应用 */
export function applyTimedDepositFromCategoryRes(vm, resData) {
	if (resData && resData.timed_deposit && resData.timed_deposit.deposit_amount != null) {
		applyTimedDepositToVm(vm, resData.timed_deposit.deposit_amount);
	}
}

/** 公开接口拉取限时拍保证金配置（新接口失败时回退 numberPlate/details，兼容未部署 timed_deposit/amount 的环境） */
export function loadTimedDepositAmount(vm, cateId) {
	const applyFromRes = (res) => {
		if (res && res.data && res.data.deposit_amount != null) {
			applyTimedDepositToVm(vm, res.data.deposit_amount, { updateNeedPay: true });
		}
	};
	return getTimedDepositAmount()
		.then(applyFromRes)
		.catch(() => {
			if (!cateId) {
				return;
			}
			return getNumberdetails(cateId).then(applyFromRes).catch(() => {});
		});
}

/** 合并 numberPlate/details 返回的用户保证金状态 */
export function mergePlateDepositDetails(vm, data) {
	if (!data) {
		return;
	}
	const fallback = vm.deposit_amount > 0 ? vm.deposit_amount : 1;
	const amt = parseDepositAmount(data.deposit_amount, fallback);
	vm.deposit_amount = amt;
	vm.need_pay = data.need_pay != null ? data.need_pay : amt;
	if (vm.margin !== undefined) {
		vm.margin = amt;
	}
	if (data.openid) {
		vm.openId = (data.openid && data.openid.openid) ? data.openid.openid : '';
	}
	if (data.orderId !== undefined) {
		vm.orderId = data.orderId;
	}
}

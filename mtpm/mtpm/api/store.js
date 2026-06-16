// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2024 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

import request from "@/utils/request.js";
import {
	DEFAULT_TIMED_META,
	TIMED_AUCTION_TIP,
	TIMED_AUCTION_MAX_DISPLAY,
	resolveTimedTipForDisplay,
} from '@/utils/timedAuctionMeta.js';

/**
 * 获取产品详情
 * @param int id
 * 
 */
export function getProductDetail(id) {
	return request.get('product/detail/' + id, {}, {
		noAuth: true
	});
}

/**
 * 产品分享二维码 推广员
 * @param int id
 */
// #ifdef H5  || APP-PLUS
export function getProductCode(id) {
	return request.get('product/code/' + id, {}, {
		noAuth: true
	});
}
// #endif
// #ifdef MP
export function getProductCode(id) {
	return request.get('product/code/' + id, {
		user_type: 'routine',
	}, {
		noAuth: true
	});
}
// #endif

/**
 * 添加收藏
 * @param int id
 * @param string category product=普通产品,product_seckill=秒杀产品
 */
export function collectAdd(id, category) {
	return request.post('collect/add', {
		id: id,
		'product': category === undefined ? 'product' : category
	});
}

/**
 * 删除收藏产品
 * @param int id
 * @param string category product=普通产品,product_seckill=秒杀产品
 */
export function collectDel(id, category) {
	return request.post('collect/del', {
		id: id,
		category: category === undefined ? 'product' : category
	});
}

/**
 * 购车添加
 * 
 */
export function postCartAdd(data) {
	return request.post('cart/add', data);
}

/**
 * 获取分类列表
 * 
 */
export function getCategoryList() {
	return request.get('category', {}, {
		noAuth: true
	});
}

/**
 * 获取产品列表
 * @param object data
 */
export function getProductslist(data) {
	return request.get('products', data, {
		noAuth: true
	});
}



/**
 * 获取推荐产品
 * 
 */
export function getProductHot(page, limit) {
	return request.get("product/hot", {
		page: page === undefined ? 1 : page,
		limit: limit === undefined ? 4 : limit
	}, {
		noAuth: true
	});
}
/**
 * 批量收藏
 * 
 * @param object id  产品编号 join(',') 切割成字符串
 * @param string category 
 */
export function collectAll(id, category) {
	return request.post('collect/all', {
		id: id,
		category: category === undefined ? 'product' : category
	});
}

/**
 * 首页产品的轮播图和产品信息
 * @param int type 
 * 
 */
export function getGroomList(type, data) {
	return request.get('groom/list/' + type, data, {
		noAuth: true
	});
}

/**
 * 获取收藏列表
 * @param object data
 */
export function getCollectUserList(data) {
	return request.get('collect/user', data)
}

/**
 * 获取产品评论
 * @param int id
 * @param object data
 * 
 */
export function getReplyList(id, data) {
	return request.get('reply/list/' + id, data)
}

/**
 * 产品评价数量和好评度
 * @param int id
 */
export function getReplyConfig(id) {
	return request.get('reply/config/' + id);
}

/**
 * 获取搜索关键字获取
 * 
 */
export function getSearchKeyword() {
	return request.get('search/keyword', {}, {
		noAuth: true
	});
}

/**
 * 门店列表
 * @returns {*}
 */
export function storeListApi(data) {
	return request.get("store_list", data);
}

/**
 * 套餐列表
 * @param int id
 * 
 */
export function storeDiscountsList(id) {
	return request.get('store_discounts/list/' + id, {}, {
		noAuth: true
	});
}

/**
 * 购车添加、减少、修改
 * 
 */
export function postCartNum(data) {
	return request.post('v2/set_cart_num', data);
}
/**
 * 代理商申请
 * 
 */
export function create(data) {
	return request.post(`agent/apply/${data.id}`, data);
}

/**
 * 代理商规则
 * @param object data
 */
export function getAgentAgreement(data) {
	return request.get('agent/get_agent_agreement', {}, {
		noAuth: true
	});
}

/**
 * h5用户发送验证码
 * @param data object 用户手机号
 */
export function registerVerify(data) {
	return request.post("register/verify", data, {
		noAuth: true
	});
}

/**
 * 验证码key
 */
export function getCodeApi() {
	return request.get("verify_code", {}, {
		noAuth: true
	});
}
/**
 * 获取代理商表单信息
 */
export function getHistoryData() {
	return request.get("agent/apply/info", {}, {
		noAuth: true
	});
}

/**
 * 获取首页的属性
 * @returns {*}
 */
export function getAttr(id, type) {
	return request.get("v2/get_attr/" + id + "/" + type);
}
/**
 * 获取首页商品列表（所有活动的）
 * @param object data
 */
export function getHomeProducts(data) {
	return request.get('home/products', data, {
		noAuth: true
	});
}

/**
 * 预售详情
 * @returns {*}
 */
export function getPresellProductDetail(id) {
	return request.get("advance/detail/" + id);
}

/**
 * 获取浏览记录列表
 * @param object data
 */
export function getVisitList(data) {
	return request.get('user/visit_list', data)
}

/**
 * 获取浏览记录列表-删除 
 * @param object data
 */
export function deleteVisitList(data) {
	return request.delete('user/visit', data)
}

/**
 * 申请分销员详情接口
 *
 */
export function userSpreadInfo() {
	return request.get("user/spread/apply/info");
}

/**
 * 分销员申请
 * @param data
 * 
 */
export function spreadCreateApi(id, data) {
	return request.post(`user/spread/apply/${id}`, data);
}

/**
 * 到手价获取
 * 
 */
export function realPrice(id, unique) {
	return request.get(`product/real_price/${id}/${unique}`, {}, {
		noAuth: true
	});
}

export function getCategorydDetails(id) {
	return request.get('/category/details/'+ id, {}, {
		noAuth: true
	});
}


/**
 * 办理号牌
 * 
 */
export function getNumberPlate(id) {
	return request.get('numberPlate/add/'+ id, {}, {
		noAuth: true
	});
}

export function getNumberdetails(id) {
	return request.get('numberPlate/details/'+ id, {}, {
		noAuth: true
	});
}

export function getAgentPrices(product_id,agent_price,plate,model) {
	return request.get('agentPrice/add', {
		product_id: product_id,
		agent_price: agent_price,
		plate: plate,
		model: model
	}, {
		noAuth: true
	});
}

export function updateAgentPrices(product_id,agent_price) {
	return request.get('agentPrice/updateAgent', {
		product_id: product_id,
		agent_price: agent_price
	}, {
		noAuth: true
	});
}

export function cancelAgentPrices(product_id) {
	return request.get('agentPrice/cancelAgent', {
		product_id: product_id,
		
	}, {
		noAuth: true
	});
}

export function addView(id) {
	return request.get('category/addView/'+ id, {}, {
		noAuth: true
	});
}

export function addPrice(product_id,plate,price) {
	return request.get('auction', {
		product_id: product_id,
		plate: plate,
		price: price
	}, {
		noAuth: true
	});
}

export function getcatList(){
	return request.get('category/getcatlist', {}, {
		noAuth: true
	});
}

/**
 * 解析限时拍专场列表（兼容数组与 { list, timed_meta }）
 */
function normalizeTimedCatList(data) {
	if (Array.isArray(data)) {
		return data;
	}
	if (!data || typeof data !== 'object') {
		return [];
	}
	if (Array.isArray(data.list)) {
		return data.list;
	}
	return Object.values(data).filter((item) => item && typeof item === 'object' && item.id != null);
}

export function parseTimedCatResponse(res) {
	let data = res && res.data !== undefined ? res.data : res;
	const defaultMeta = Object.assign({}, DEFAULT_TIMED_META, {
		tip: TIMED_AUCTION_TIP,
		max_display: TIMED_AUCTION_MAX_DISPLAY,
	});
	// 兼容误写入缓存的双层 { list: { list, timed_meta } }
	if (data && !Array.isArray(data) && data.list && data.list.list && data.list.timed_meta) {
		data = data.list;
	}
	const list = normalizeTimedCatList(data);
	const meta = (!Array.isArray(data) && data && data.timed_meta) ? data.timed_meta : {};
	const displayCount = meta.display_count != null ? meta.display_count : list.length;
	const isSessionGroup = meta.is_session_group === true;
	const tipCount = isSessionGroup ? displayCount : (meta.is_session_group === false ? 0 : displayCount);
	return {
		list,
		meta: Object.assign({}, defaultMeta, meta, {
			display_count: displayCount,
			tip: meta.tip !== undefined && meta.tip !== null
				? meta.tip
				: resolveTimedTipForDisplay(tipCount),
		}),
	};
}

export function getproductDetails(id) {
	return request.get('category/getproductDetails/' + id, {
		
	}, {
		noAuth: true
	});
}

export function storeCount(){
	return request.get('agentPrice/storeCount', {
		
	}, {
		noAuth: true
	})
}

export function getBidding(page){
	return request.get('getBidding', {
		page: page
	}, {
		noAuth: true
	})
}

export function getPlate(page){
	return request.get('getPlate', {
		page: page
	}, {
		noAuth: true
	})
}

export function auctionDetails(id){
	
	return request.get('category/auctionDetails', {
		id: id
	}, {
		noAuth: true
	})
}

export function agentPriceDetail(id){
	
	return request.get('agentPrice/detail/' + id, {
		
	}, {
		noAuth: true
	})
}


export function getbiddingList(id, refresh = false){
	return request.get('productBiddingList/' + id, {
		refresh: refresh ? 1 : 0
	},{noAuth: true})
	
}


export function getwxpay(openid,cate_id,price){
	return request.get('wxpay', {
		openid: openid,
		cate_id: cate_id,
		price: price
	},{noAuth: true})
	
}


export function getProductList(page){
	return request.get('getProductList', {
		page: page
	}, {
		noAuth: true
	})
}

export function numberPlateRefund(cate_id){
	return request.get('numberPlate/refund', {
		cate_id: cate_id || 0
	}, {
		noAuth: true
	})
}

export function getTimedDepositStatus(){
	return request.get('numberPlate/deposit', {}, {
		noAuth: true
	})
}

export function getTimedDepositAmount() {
	return request.get('timed_deposit/amount', {}, {
		noAuth: true
	});
}

export function synchronization(){
	return request.get('getsynchronization', {
		
	}, {
		noAuth: true
	})
}

export function synchronizationlist(id){
	return request.get('category/getSynchronizationlist/' + id, {
		
	}, {
		noAuth: true
	})
}


export function addSynchronousPrice(product_id,plate,price) {
	return request.get('synchronousAddPrice', {
		product_id: product_id,
		plate: plate,
		price: price
	}, {
		noAuth: true
	});
}
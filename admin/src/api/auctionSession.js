import request from '@/libs/request';

/** 拍卖周期列表（含已绑定专场） */
export function getAuctionSessionListApi() {
  return request({
    url: 'product/auction_session',
    method: 'get',
  });
}

/** 可选限时拍专场（绑定用）；session_id 为当前周期，已绑定其它周期的专场不返回 */
export function getTimedCategoryOptionsApi(sessionId = 0) {
  return request({
    url: 'product/auction_session/timed_categories',
    method: 'get',
    params: { session_id: sessionId },
  });
}

/** 创建拍卖周期 */
export function createAuctionSessionApi(data) {
  return request({
    url: 'product/auction_session',
    method: 'post',
    data,
  });
}

/** 绑定 1～2 个限时拍专场到周期 */
export function bindAuctionSessionApi(data) {
  return request({
    url: 'product/auction_session/bind',
    method: 'post',
    data,
  });
}

/** 修改拍卖周期 */
export function updateAuctionSessionApi(id, data) {
  return request({
    url: `product/auction_session/${id}`,
    method: 'put',
    data,
  });
}

/** 删除拍卖周期 */
export function deleteAuctionSessionApi(id) {
  return request({
    url: `product/auction_session/${id}`,
    method: 'delete',
  });
}

import request from '@/libs/request';

/** 读取限时拍保证金金额 */
export function getTimedDepositConfigApi() {
  return request({
    url: 'product/timed_deposit/config',
    method: 'get',
  });
}

/** 保存限时拍保证金金额 */
export function saveTimedDepositConfigApi(data) {
  return request({
    url: 'product/timed_deposit/config',
    method: 'post',
    data,
  });
}

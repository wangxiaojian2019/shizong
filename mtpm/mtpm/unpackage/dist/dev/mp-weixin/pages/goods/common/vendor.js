(global["webpackJsonp"] = global["webpackJsonp"] || []).push([["pages/goods/common/vendor"],{

/***/ 1553:
/*!********************************************************************************************************!*\
  !*** C:/Users/25038/Desktop/小程序/mtpm/template/mtpm/pages/goods/components/lottery/js/grids_lottery.js ***!
  \********************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function LotteryDraw(obj, callback) {
  this.timer = null; //计时器
  this.startIndex = obj.startIndex - 1 || 0; //从第几个位置开始抽奖 [默认为零]
  this.count = 0; //计数，跑的圈数
  this.winingIndex = obj.winingIndex || 0; //获奖的位置
  this.totalCount = obj.totalCount || 6; //抽奖跑的圈数
  this.speed = obj.speed || 100;
  this.domData = obj.domData;
  this.rollFn();
  this.callback = callback;
}
LotteryDraw.prototype = {
  rollFn: function rollFn() {
    var that = this;
    // 活动index值增加，即移动到下一个格子
    this.startIndex++;

    //startIndex是最后一个时一圈走完，重新开始
    if (this.startIndex >= this.domData.length - 1) {
      this.startIndex = 0;
      this.count++;
    }

    // 当跑的圈数等于设置的圈数，且活动的index值是奖品的位置时停止
    if (this.count >= this.totalCount && this.startIndex === this.winingIndex) {
      if (typeof this.callback === 'function') {
        setTimeout(function () {
          that.callback(that.startIndex, that.count); //执行回调函数，抽奖完成的相关操作
        }, 400);
      }
      clearInterval(this.timer);
    } else {
      //重新开始一圈
      if (this.count >= this.totalCount - 1) {
        this.speed += 30;
      }
      this.timer = setTimeout(function () {
        that.callback(that.startIndex, that.count);
        that.rollFn();
      }, this.speed);
    }
  }
};
module.exports = LotteryDraw;

/***/ }),

/***/ 315:
/*!********************************************************************!*\
  !*** C:/Users/25038/Desktop/小程序/mtpm/template/mtpm/api/lottery.js ***!
  \********************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _interopRequireDefault = __webpack_require__(/*! @babel/runtime/helpers/interopRequireDefault */ 4);
Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.getLotteryData = getLotteryData;
exports.getLotteryList = getLotteryList;
exports.receiveLottery = receiveLottery;
exports.startLottery = startLottery;
var _request = _interopRequireDefault(__webpack_require__(/*! @/utils/request.js */ 39));
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2024 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

/**
 * 获取抽奖详情信息
 * 
 */
function getLotteryData(type, lottery_id) {
  return _request.default.get("v2/lottery/info/".concat(type).concat(lottery_id ? '/' + lottery_id : ''));
}

/**
 * 参与抽奖
 * 
 */
function startLottery(data) {
  return _request.default.post("v2/lottery", data);
}

/**
 * 领奖
 * 
 */
function receiveLottery(data) {
  return _request.default.post("v2/lottery/receive", data);
}

/**
 * 获取中奖记录
 * 
 */
function getLotteryList(data) {
  return _request.default.get("v2/lottery/record", data);
}

/***/ }),

/***/ 382:
/*!************************************************************************!*\
  !*** C:/Users/25038/Desktop/小程序/mtpm/template/mtpm/mixins/debounce.js ***!
  \************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2024 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------
var _default = {
  data: function data() {
    return {};
  },
  created: function created() {},
  methods: {
    Debounce: function Debounce(fn, t) {
      var delay = t || 500;
      var timer;
      return function () {
        var _this = this;
        var args = arguments;
        if (timer) {
          clearTimeout(timer);
        }
        timer = setTimeout(function () {
          timer = null;
          fn.apply(_this, args);
        }, delay);
      };
    }
  }
};
exports.default = _default;

/***/ })

}]);
//# sourceMappingURL=../../../../.sourcemap/mp-weixin/pages/goods/common/vendor.js.map
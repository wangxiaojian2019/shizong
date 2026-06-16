(global["webpackJsonp"] = global["webpackJsonp"] || []).push([["common/main"],{

/***/ 0:
/*!**************************************************************!*\
  !*** /Users/shijie/mac/vscode/company/shi-boss/mtpm/main.js ***!
  \**************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/* WEBPACK VAR INJECTION */(function(wx, createApp) {

var _interopRequireDefault = __webpack_require__(/*! @babel/runtime/helpers/interopRequireDefault */ 4);
var _defineProperty2 = _interopRequireDefault(__webpack_require__(/*! @babel/runtime/helpers/defineProperty */ 11));
__webpack_require__(/*! uni-pages */ 30);
var _vue = _interopRequireDefault(__webpack_require__(/*! vue */ 25));
var _App = _interopRequireDefault(__webpack_require__(/*! ./App */ 31));
var _store = _interopRequireDefault(__webpack_require__(/*! ./store */ 38));
var _cache = _interopRequireDefault(__webpack_require__(/*! ./utils/cache */ 47));
var _util = _interopRequireDefault(__webpack_require__(/*! utils/util */ 69));
var _app = _interopRequireDefault(__webpack_require__(/*! ./config/app.js */ 44));
var _new_chat = _interopRequireDefault(__webpack_require__(/*! ./libs/new_chat.js */ 71));
var _lang = _interopRequireDefault(__webpack_require__(/*! ./utils/lang.js */ 45));
var _permission = _interopRequireDefault(__webpack_require__(/*! ./libs/permission.js */ 72));
var _uviewUi = _interopRequireDefault(__webpack_require__(/*! uview-ui */ 73));
var _validate = __webpack_require__(/*! @/utils/validate.js */ 52);
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { (0, _defineProperty2.default)(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
// @ts-ignore
wx.__webpack_require_UNI_MP_PLUGIN__ = __webpack_require__; // +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2024 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

_vue.default.prototype.$util = _util.default;
_vue.default.prototype.$config = _app.default;
_vue.default.prototype.$Cache = _cache.default;
_vue.default.prototype.$eventHub = new _vue.default();
_vue.default.prototype.$socket = new _new_chat.default();
_vue.default.config.productionTip = false;
var pageLoading = function pageLoading() {
  __webpack_require__.e(/*! require.ensure | components/pageLoading */ "components/pageLoading").then((function () {
    return resolve(__webpack_require__(/*! ./components/pageLoading.vue */ 1128));
  }).bind(null, __webpack_require__)).catch(__webpack_require__.oe);
};
var skeleton = function skeleton() {
  __webpack_require__.e(/*! require.ensure | components/skeleton/index */ "components/skeleton/index").then((function () {
    return resolve(__webpack_require__(/*! ./components/skeleton/index.vue */ 1135));
  }).bind(null, __webpack_require__)).catch(__webpack_require__.oe);
};
var easyLoadimage = function easyLoadimage() {
  __webpack_require__.e(/*! require.ensure | components/easy-loadimage/easy-loadimage */ "components/easy-loadimage/easy-loadimage").then((function () {
    return resolve(__webpack_require__(/*! @/components/easy-loadimage/easy-loadimage.vue */ 1142));
  }).bind(null, __webpack_require__)).catch(__webpack_require__.oe);
};
var BaseMoney = function BaseMoney() {
  __webpack_require__.e(/*! require.ensure | components/BaseMoney */ "components/BaseMoney").then((function () {
    return resolve(__webpack_require__(/*! ./components/BaseMoney.vue */ 1149));
  }).bind(null, __webpack_require__)).catch(__webpack_require__.oe);
};
var BaseTag = function BaseTag() {
  __webpack_require__.e(/*! require.ensure | components/BaseTag */ "components/BaseTag").then((function () {
    return resolve(__webpack_require__(/*! ./components/BaseTag.vue */ 1156));
  }).bind(null, __webpack_require__)).catch(__webpack_require__.oe);
};
_vue.default.component('skeleton', skeleton);
_vue.default.component('pageLoading', pageLoading);
_vue.default.component('easyLoadimage', easyLoadimage);
_vue.default.component('BaseMoney', BaseMoney);
_vue.default.component('BaseTag', BaseTag);
_vue.default.prototype.$permission = _permission.default;
_vue.default.use(_uviewUi.default);
_vue.default.prototype.$Debounce = _validate.Debounce;
_App.default.mpType = 'app';
var app = new _vue.default(_objectSpread(_objectSpread({}, _App.default), {}, {
  store: _store.default,
  Cache: _cache.default,
  i18n: _lang.default
}));
createApp(app).$mount();
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./node_modules/@dcloudio/uni-mp-weixin/dist/wx.js */ 1)["default"], __webpack_require__(/*! ./node_modules/@dcloudio/uni-mp-weixin/dist/index.js */ 2)["createApp"]))

/***/ }),

/***/ 31:
/*!**************************************************************!*\
  !*** /Users/shijie/mac/vscode/company/shi-boss/mtpm/App.vue ***!
  \**************************************************************/
/*! no static exports found */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _App_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./App.vue?vue&type=script&lang=js& */ 32);
/* harmony reexport (unknown) */ for(var __WEBPACK_IMPORT_KEY__ in _App_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__) if(["default"].indexOf(__WEBPACK_IMPORT_KEY__) < 0) (function(key) { __webpack_require__.d(__webpack_exports__, key, function() { return _App_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__[key]; }) }(__WEBPACK_IMPORT_KEY__));
/* harmony import */ var _App_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./App.vue?vue&type=style&index=0&lang=scss& */ 66);
/* harmony import */ var _Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../../../../../../Applications/HBuilderX.app/Contents/HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib/runtime/componentNormalizer.js */ 68);
var render, staticRenderFns, recyclableRender, components
var renderjs





/* normalize component */

var component = Object(_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _App_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"],
  render,
  staticRenderFns,
  false,
  null,
  null,
  null,
  false,
  components,
  renderjs
)

component.options.__file = "App.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ 32:
/*!***************************************************************************************!*\
  !*** /Users/shijie/mac/vscode/company/shi-boss/mtpm/App.vue?vue&type=script&lang=js& ***!
  \***************************************************************************************/
/*! no static exports found */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_babel_loader_lib_index_js_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_13_1_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_script_js_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_App_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../../Applications/HBuilderX.app/Contents/HBuilderX/plugins/uniapp-cli/node_modules/babel-loader/lib!../../../../../../../Applications/HBuilderX.app/Contents/HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/webpack-preprocess-loader??ref--13-1!../../../../../../../Applications/HBuilderX.app/Contents/HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/webpack-uni-mp-loader/lib/script.js!../../../../../../../Applications/HBuilderX.app/Contents/HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib??vue-loader-options!../../../../../../../Applications/HBuilderX.app/Contents/HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/webpack-uni-mp-loader/lib/style.js!./App.vue?vue&type=script&lang=js& */ 33);
/* harmony import */ var _Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_babel_loader_lib_index_js_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_13_1_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_script_js_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_App_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_babel_loader_lib_index_js_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_13_1_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_script_js_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_App_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__);
/* harmony reexport (unknown) */ for(var __WEBPACK_IMPORT_KEY__ in _Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_babel_loader_lib_index_js_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_13_1_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_script_js_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_App_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__) if(["default"].indexOf(__WEBPACK_IMPORT_KEY__) < 0) (function(key) { __webpack_require__.d(__webpack_exports__, key, function() { return _Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_babel_loader_lib_index_js_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_13_1_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_script_js_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_App_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__[key]; }) }(__WEBPACK_IMPORT_KEY__));
 /* harmony default export */ __webpack_exports__["default"] = (_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_babel_loader_lib_index_js_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_13_1_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_script_js_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_App_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ 33:
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib!./node_modules/@dcloudio/vue-cli-plugin-uni/packages/webpack-preprocess-loader??ref--13-1!./node_modules/@dcloudio/webpack-uni-mp-loader/lib/script.js!./node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib??vue-loader-options!./node_modules/@dcloudio/webpack-uni-mp-loader/lib/style.js!/Users/shijie/mac/vscode/company/shi-boss/mtpm/App.vue?vue&type=script&lang=js& ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/* WEBPACK VAR INJECTION */(function(uni, wx) {

var _interopRequireDefault = __webpack_require__(/*! @babel/runtime/helpers/interopRequireDefault */ 4);
Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;
var _regenerator = _interopRequireDefault(__webpack_require__(/*! @babel/runtime/regenerator */ 34));
var _asyncToGenerator2 = _interopRequireDefault(__webpack_require__(/*! @babel/runtime/helpers/asyncToGenerator */ 36));
var _login = __webpack_require__(/*! ./libs/login */ 37);
var _app = __webpack_require__(/*! ./config/app */ 44);
var _public = __webpack_require__(/*! @/api/public */ 54);
var _wechat = _interopRequireDefault(__webpack_require__(/*! @/libs/wechat.js */ 55));
var _routine = _interopRequireDefault(__webpack_require__(/*! ./libs/routine.js */ 53));
var _utils = __webpack_require__(/*! @/utils */ 56);
var _api = __webpack_require__(/*! @/api/api.js */ 57);
var _user = __webpack_require__(/*! @/api/user.js */ 42);
var _vuex = __webpack_require__(/*! vuex */ 39);
var _color = _interopRequireDefault(__webpack_require__(/*! @/mixins/color.js */ 59));
var _cache = _interopRequireDefault(__webpack_require__(/*! @/utils/cache */ 47));
var _theme = _interopRequireDefault(__webpack_require__(/*! @/utils/theme */ 60));
var _util = __webpack_require__(/*! util */ 61);
var _default = {
  globalData: {
    spid: 0,
    code: 0,
    isLogin: false,
    userInfo: uni.getStorageSync('userInfo'),
    MyMenus: [],
    globalData: false,
    isIframe: false,
    tabbarShow: true,
    windowHeight: 0,
    locale: '',
    socketTask: null,
    timer: null,
    wsReconnectTimer: null,
    wsReconnectDelay: 5000,
    type: ""
  },
  mixins: [_color.default],
  computed: (0, _vuex.mapGetters)(['isLogin', 'cartNum']),
  watch: {
    isLogin: {
      deep: true,
      //深度监听设置为 true
      handler: function handler(newV, oldV) {
        if (newV) {
          // this.getCartNum()
        } else {
          this.$store.commit('indexData/setCartNum', '');
        }
      }
    },
    cartNum: function cartNum(newCart, b) {
      this.$store.commit('indexData/setCartNum', newCart + '');
      if (newCart > 0) {
        uni.setTabBarBadge({
          index: Number(uni.getStorageSync('FOOTER_ADDCART')) || 2,
          text: newCart + ''
        });
      } else {
        uni.hideTabBarRedDot({
          index: Number(uni.getStorageSync('FOOTER_ADDCART')) || 2
        });
      }
    }
  },
  onShow: function onShow() {
    var queryData = uni.getEnterOptionsSync(); // uni-app版本 3.5.1+ 支持
    if (queryData.query.spread) {
      this.$Cache.set('spread', queryData.query.spread);
      this.globalData.spid = queryData.query.spread;
      this.globalData.pid = queryData.query.spread;
      (0, _utils.silenceBindingSpread)(this.globalData);
    }
    if (queryData.query.spid) {
      this.$Cache.set('spread', queryData.query.spid);
      this.globalData.spid = queryData.query.spid;
      this.globalData.pid = queryData.query.spid;
      (0, _utils.silenceBindingSpread)(this.globalData);
    }
    if (queryData.query.agent_id) {
      this.$Cache.set('agent_id', queryData.query.agent_id);
      this.globalData.agent_id = queryData.query.agent_id;
      (0, _utils.silenceBindingSpread)(this.globalData);
    }
    console.log(this.$u.config.v);
    if (queryData.query.scene) {
      var param = this.$util.getUrlParams(decodeURIComponent(queryData.query.scene));
      if (param.pid) {
        this.$Cache.set('spread', param.pid);
        this.globalData.spid = param.pid;
        this.globalData.pid = param.pid;
      } else {
        switch (queryData.scene) {
          //扫描小程序码
          case 1047:
            this.globalData.code = queryData.query.scene;
            break;
          //长按图片识别小程序码
          case 1048:
            this.globalData.code = queryData.query.scene;
            break;
          //手机相册选取小程序码
          case 1049:
            this.globalData.code = queryData.query.scene;
            break;
          //直接进入小程序
          case 1001:
            this.globalData.spid = queryData.query.scene;
            break;
        }
      }
      (0, _utils.silenceBindingSpread)(this.globalData);
    }
  },
  onLaunch: function onLaunch(option) {
    var _this = this;
    return (0, _asyncToGenerator2.default)( /*#__PURE__*/_regenerator.default.mark(function _callee() {
      var that, updateManager, startParamObj, _updateManager, menuButtonInfo, version;
      return _regenerator.default.wrap(function _callee$(_context) {
        while (1) {
          switch (_context.prev = _context.next) {
            case 0:
              uni.hideTabBar();
              that = _this;
              (0, _public.basicConfig)().then(function (res) {
                uni.setStorageSync('BASIC_CONFIG', res.data);
              });
              (0, _api.colorChange)('color_change').then(function (res) {
                uni.setStorageSync('is_diy', res.data.is_diy);
                uni.$emit('is_diy', res.data.is_diy);
                var themeMap = {
                  1: 'blue',
                  2: 'green',
                  3: 'red',
                  4: 'pink',
                  5: 'orange'
                };
                var status = res.data.status;
                var themeKey = themeMap[status] || 'red'; // 默认使用红色
                var selectedTheme = _theme.default[themeKey];
                uni.setStorageSync('color_status', res.data.status);
                uni.setStorageSync('viewColor', selectedTheme);
                uni.$emit('ok', selectedTheme, status);
              });
              (0, _user.getLangVersion)().then(function (res) {
                var version = res.data.version;
                if (version != uni.getStorageSync('LANG_VERSION')) {
                  (0, _user.getLangJson)().then(function (res) {
                    var value = Object.keys(res.data)[0];
                    _cache.default.set('locale', Object.keys(res.data)[0]);
                    _this.$i18n.setLocaleMessage(value, res.data[value]);
                    uni.setStorageSync('localeJson', res.data);
                  });
                }
                uni.setStorageSync('LANG_VERSION', version);
              });
              if (!(_app.HTTP_REQUEST_URL == '')) {
                _context.next = 8;
                break;
              }
              console.error("请配置根目录下的config.js文件中的 'HTTP_REQUEST_URL'\n\n请修改开发者工具中【详情】->【AppID】改为自己的Appid\n\n请前往后台【小程序】->【小程序配置】填写自己的 appId and AppSecret");
              return _context.abrupt("return", false);
            case 8:
              updateManager = wx.getUpdateManager();
              startParamObj = wx.getEnterOptionsSync();
              if (wx.canIUse('getUpdateManager') && startParamObj.scene != 1154) {
                _updateManager = wx.getUpdateManager();
                _updateManager.onCheckForUpdate(function (res) {
                  if (res.hasUpdate) {
                    _updateManager.onUpdateFailed(function () {
                      return that.Tips({
                        title: '新版本下载失败'
                      });
                    });
                    _updateManager.onUpdateReady(function () {
                      wx.showModal({
                        title: '更新提示',
                        content: '新版本已经下载好，是否重启当前应用？',
                        success: function success(res) {
                          if (res.confirm) {
                            _updateManager.applyUpdate();
                          }
                        }
                      });
                    });
                    _updateManager.onUpdateFailed(function () {
                      wx.showModal({
                        title: '发现新版本',
                        content: '请删除当前小程序，重启搜索打开...'
                      });
                    });
                  }
                });
              }

              // getShopConfig().then(res => {
              // 	this.$store.commit('SETPHONESTATUS', res.data.status);
              // });
              // 获取导航高度；
              uni.getSystemInfo({
                success: function success(res) {
                  that.globalData.navHeight = res.statusBarHeight * (750 / res.windowWidth) + 91;
                }
              });
              menuButtonInfo = uni.getMenuButtonBoundingClientRect();
              that.globalData.navH = menuButtonInfo.top * 2 + menuButtonInfo.height / 2;
              version = uni.getSystemInfoSync().SDKVersion;
              if (_routine.default.compareVersion(version, '2.21.3') >= 0) {
                that.$Cache.set('MP_VERSION_ISNEW', true);
              } else {
                that.$Cache.set('MP_VERSION_ISNEW', false);
              }

              // 小程序静默授权
              // if (!this.$store.getters.isLogin) {
              // 	Routine.getCode()
              // 		.then(code => {
              // 			this.silenceAuth(code);
              // 		})
              // 		.catch(res => {
              // 			uni.hideLoading();
              // 		});
              // }

              (0, _api.getCrmebCopyRight)().then(function (res) {
                uni.setStorageSync('copyRight', res.data);
              });
              _this.initWebSocket();
            case 18:
            case "end":
              return _context.stop();
          }
        }
      }, _callee);
    }))();
  },
  methods: {
    initWebSocket: function initWebSocket() {
      var _this2 = this;
      if (this.globalData.wsReconnectTimer) {
        clearTimeout(this.globalData.wsReconnectTimer);
        this.globalData.wsReconnectTimer = null;
      }
      if (this.globalData.socketTask) {
        try {
          this.globalData.socketTask.close({});
        } catch (e) {}
        this.globalData.socketTask = null;
      }
      if (this.globalData.timer) {
        clearInterval(this.globalData.timer);
        this.globalData.timer = null;
      }
      // 拍卖长连接固定 /ws；WORKERMAN_URL 为客服 /msg，不可混用
      var wsUrl = 'wss://mtpm.billionwz.com/ws';
      if (!wsUrl) {
        return;
      }
      this.globalData.socketTask = uni.connectSocket({
        url: wsUrl,
        success: function success() {
          console.log('WebSocket 连接创建成功');
        },
        fail: function fail(err) {
          console.error('连接失败:', err);
          _this2.scheduleWsReconnect();
        }
      });
      this.globalData.socketTask.onOpen(function (res) {
        _this2.globalData.wsReconnectDelay = 5000;
        console.log('WebSocket 连接成功');
        if (_this2.globalData.type) {
          _this2.globalData.socketTask.send({
            data: JSON.stringify(_this2.globalData.type),
            success: function success() {
              console.log("发送成功1");
            },
            fail: function fail(err) {
              console.error('消息发送失败', err);
            }
          });
        }
      });

      // 监听消息
      this.globalData.socketTask.onMessage(function (res) {
        var myArray = JSON.parse(res.data);
        //console.log(myArray);
        if (myArray["type"] == "category") {
          //const myArray1 = JSON.parse(myArray.data);
          uni.$emit('socketMessage', myArray);
        } else {
          uni.$emit('socketMessage', myArray);
        }

        //uni.$emit('socketMessage', myArray1)
      });

      this.globalData.socketTask.onClose(function () {
        clearInterval(_this2.globalData.timer);
        _this2.globalData.timer = null;
        _this2.scheduleWsReconnect();
      });
      this.globalData.socketTask.onError(function () {
        _this2.scheduleWsReconnect();
      });
      this.globalData.timer = setInterval(function () {
        if (_this2.globalData.socketTask && _this2.globalData.socketTask.readyState === _this2.globalData.socketTask.OPEN) {
          _this2.globalData.socketTask.send({
            data: JSON.stringify({
              type: 'ping'
            })
          });
        }
      }, 30000);
    },
    scheduleWsReconnect: function scheduleWsReconnect() {
      var _this3 = this;
      if (this.globalData.wsReconnectTimer) {
        return;
      }
      var delay = this.globalData.wsReconnectDelay || 5000;
      this.globalData.wsReconnectTimer = setTimeout(function () {
        _this3.globalData.wsReconnectTimer = null;
        _this3.globalData.wsReconnectDelay = Math.min(delay * 2, 60000);
        _this3.initWebSocket();
      }, delay);
    },
    remoteRegister: function remoteRegister(remote_token) {
      var _this4 = this;
      (0, _public.remoteRegister)({
        remote_token: remote_token
      }).then(function (res) {
        var data = res.data;
        if (data.get_remote_login_url) {
          location.href = data.get_remote_login_url;
        } else {
          _this4.$store.commit('LOGIN', {
            token: data.token,
            time: data.expires_time - _this4.$Cache.time()
          });
          _this4.$store.commit('SETUID', data.userInfo.uid);
          location.reload();
        }
      });
    } // 小程序静默授权
    // silenceAuth(code) {
    // 	let that = this;
    // 	let spread = that.globalData.spid ? that.globalData.spid : '';
    // 	silenceAuth({
    // 			code: code,
    // 			spread_spid: spread,
    // 			spread_code: that.globalData.code
    // 		})
    // 		.then(res => {
    // 			if (res.data.token !== undefined && res.data.token) {
    // 				uni.hideLoading();
    // 				let time = res.data.expires_time - this.$Cache.time();
    // 				that.$store.commit('LOGIN', {
    // 					token: res.data.token,
    // 					time: time
    // 				});
    // 				that.$store.commit('SETUID', res.data.userInfo.uid);
    // 				that.$store.commit('UPDATE_USERINFO', res.data.userInfo);
    // 			}
    // 		})
    // 		.catch(res => {});
    // },
  }
};
exports.default = _default;
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./node_modules/@dcloudio/uni-mp-weixin/dist/index.js */ 2)["default"], __webpack_require__(/*! ./node_modules/@dcloudio/uni-mp-weixin/dist/wx.js */ 1)["default"]))

/***/ }),

/***/ 66:
/*!************************************************************************************************!*\
  !*** /Users/shijie/mac/vscode/company/shi-boss/mtpm/App.vue?vue&type=style&index=0&lang=scss& ***!
  \************************************************************************************************/
/*! no static exports found */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_stylePostLoader_js_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_8_oneOf_1_2_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_3_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_sass_loader_dist_cjs_js_ref_8_oneOf_1_4_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_8_oneOf_1_5_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_App_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../../Applications/HBuilderX.app/Contents/HBuilderX/plugins/uniapp-cli/node_modules/mini-css-extract-plugin/dist/loader.js??ref--8-oneOf-1-0!../../../../../../../Applications/HBuilderX.app/Contents/HBuilderX/plugins/uniapp-cli/node_modules/css-loader/dist/cjs.js??ref--8-oneOf-1-1!../../../../../../../Applications/HBuilderX.app/Contents/HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib/loaders/stylePostLoader.js!../../../../../../../Applications/HBuilderX.app/Contents/HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/webpack-preprocess-loader??ref--8-oneOf-1-2!../../../../../../../Applications/HBuilderX.app/Contents/HBuilderX/plugins/uniapp-cli/node_modules/postcss-loader/src??ref--8-oneOf-1-3!../../../../../../../Applications/HBuilderX.app/Contents/HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/sass-loader/dist/cjs.js??ref--8-oneOf-1-4!../../../../../../../Applications/HBuilderX.app/Contents/HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/webpack-preprocess-loader??ref--8-oneOf-1-5!../../../../../../../Applications/HBuilderX.app/Contents/HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib??vue-loader-options!../../../../../../../Applications/HBuilderX.app/Contents/HBuilderX/plugins/uniapp-cli/node_modules/@dcloudio/webpack-uni-mp-loader/lib/style.js!./App.vue?vue&type=style&index=0&lang=scss& */ 67);
/* harmony import */ var _Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_stylePostLoader_js_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_8_oneOf_1_2_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_3_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_sass_loader_dist_cjs_js_ref_8_oneOf_1_4_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_8_oneOf_1_5_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_App_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_stylePostLoader_js_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_8_oneOf_1_2_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_3_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_sass_loader_dist_cjs_js_ref_8_oneOf_1_4_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_8_oneOf_1_5_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_App_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* harmony reexport (unknown) */ for(var __WEBPACK_IMPORT_KEY__ in _Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_stylePostLoader_js_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_8_oneOf_1_2_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_3_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_sass_loader_dist_cjs_js_ref_8_oneOf_1_4_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_8_oneOf_1_5_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_App_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__) if(["default"].indexOf(__WEBPACK_IMPORT_KEY__) < 0) (function(key) { __webpack_require__.d(__webpack_exports__, key, function() { return _Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_stylePostLoader_js_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_8_oneOf_1_2_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_3_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_sass_loader_dist_cjs_js_ref_8_oneOf_1_4_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_8_oneOf_1_5_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_App_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__[key]; }) }(__WEBPACK_IMPORT_KEY__));
 /* harmony default export */ __webpack_exports__["default"] = (_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_loaders_stylePostLoader_js_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_8_oneOf_1_2_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_3_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_sass_loader_dist_cjs_js_ref_8_oneOf_1_4_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_webpack_preprocess_loader_index_js_ref_8_oneOf_1_5_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_vue_cli_plugin_uni_packages_vue_loader_lib_index_js_vue_loader_options_Applications_HBuilderX_app_Contents_HBuilderX_plugins_uniapp_cli_node_modules_dcloudio_webpack_uni_mp_loader_lib_style_js_App_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ 67:
/*!****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/mini-css-extract-plugin/dist/loader.js??ref--8-oneOf-1-0!./node_modules/css-loader/dist/cjs.js??ref--8-oneOf-1-1!./node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/@dcloudio/vue-cli-plugin-uni/packages/webpack-preprocess-loader??ref--8-oneOf-1-2!./node_modules/postcss-loader/src??ref--8-oneOf-1-3!./node_modules/@dcloudio/vue-cli-plugin-uni/packages/sass-loader/dist/cjs.js??ref--8-oneOf-1-4!./node_modules/@dcloudio/vue-cli-plugin-uni/packages/webpack-preprocess-loader??ref--8-oneOf-1-5!./node_modules/@dcloudio/vue-cli-plugin-uni/packages/vue-loader/lib??vue-loader-options!./node_modules/@dcloudio/webpack-uni-mp-loader/lib/style.js!/Users/shijie/mac/vscode/company/shi-boss/mtpm/App.vue?vue&type=style&index=0&lang=scss& ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin
    if(false) { var cssReload; }
  

/***/ })

},[[0,"common/runtime","common/vendor"]]]);
//# sourceMappingURL=../../.sourcemap/mp-weixin/common/main.js.map
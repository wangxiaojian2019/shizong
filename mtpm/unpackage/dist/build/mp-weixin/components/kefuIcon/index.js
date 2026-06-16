(global["webpackJsonp"]=global["webpackJsonp"]||[]).push([["components/kefuIcon/index"],{"0af2":function(t,n,e){"use strict";e.d(n,"b",(function(){return u})),e.d(n,"c",(function(){return c})),e.d(n,"a",(function(){}));var u=function(){var t=this.$createElement;this._self._c},c=[]},"13c9":function(t,n,e){"use strict";var u=e("7f75"),c=e.n(u);c.a},"2fc2":function(t,n,e){"use strict";e.r(n);var u=e("62cc"),c=e.n(u);for(var o in u)["default"].indexOf(o)<0&&function(t){e.d(n,t,(function(){return u[t]}))}(o);n["default"]=c.a},"5a0b":function(t,n,e){"use strict";e.r(n);var u=e("0af2"),c=e("2fc2");for(var o in c)["default"].indexOf(o)<0&&function(t){e.d(n,t,(function(){return c[t]}))}(o);e("13c9");var f=e("828b"),i=Object(f["a"])(c["default"],u["b"],u["c"],!1,null,null,null,!1,u["a"],void 0);n["default"]=i.exports},"62cc":function(t,n,e){"use strict";Object.defineProperty(n,"__esModule",{value:!0}),n.default=void 0;var u=e("4056"),c=(getApp(),{name:"kefuIcon",props:{ids:{type:Number,default:0},routineContact:{type:Number,default:0},storeInfo:{type:Object,default:function(){}},goodsCon:{type:Number,default:0}},data:function(){return{top:"480"}},mounted:function(){},methods:{goCustomer:function(){(0,u.getCustomer)("/pages/extension/customer_list/chat?productId=".concat(this.ids))},setTouchMove:function(t){t.touches[0].clientY<480&&t.touches[0].clientY>66&&(this.top=t.touches[0].clientY)}},created:function(){}});n.default=c},"7f75":function(t,n,e){}}]);
;(global["webpackJsonp"] = global["webpackJsonp"] || []).push([
    'components/kefuIcon/index-create-component',
    {
        'components/kefuIcon/index-create-component':(function(module, exports, __webpack_require__){
            __webpack_require__('df3c')['createComponent'](__webpack_require__("5a0b"))
        })
    },
    [['components/kefuIcon/index-create-component']]
]);

<template>
	<view>
		<view class="top" :style="'padding:' + safeAreaBottom + 'px 10px 60px'">
			<view class="logo">
				<view style="height: 150rpx;" class="middle">
					<u-avatar :src="userInfo.avatar" mode="circle" v-if="userInfo.avatar" :size="avatarlogo"></u-avatar>
					<u-avatar :src="avatar" mode="circle" v-else></u-avatar>
				</view>


			</view>
			<view class="text-color center nickname" v-if="userInfo.uid" @click="toseting()">{{userInfo.nickname}}
			</view>
			<view class="text-color center nickname" v-else @click="openAuto">请点击登录</view>
			<view style="padding:  20px 0 0 0;" class="flex justify-between">
				<view class="center text-color">
					<view class="bold fontSize-18">{{ depositDisplay }}</view>
					<view class="fontSize-14 pt-5" @click="tomessage()">保证金</view>
				</view>
				<view class="center text-color" @click="tobidding()">
					<view class="bold fontSize-18">{{bidding}}</view>
					<view class="fontSize-14 pt-5">我出价的</view>
				</view>
				<view class="center text-color">
					<view class="bold fontSize-18">0</view>
					<view class="fontSize-14 pt-5">我的关注</view>
				</view>
				<view class="center text-color">
					<view class="bold fontSize-18" @click="toplate()">{{plate}}</view>
					<view class="fontSize-14 pt-5">我的号牌</view>
				</view>
			</view>
		</view>

		<view style="padding: 20px 10px; ">
			<view class="flex relative1">
				<view style="width: 30px;">
					<image src="https://mtpm.billionwz.com/statics/images/logo7.png" class="w-full block"
						mode="widthFix"></image>
				</view>
				<view class="middle">主要功能</view>
			</view>
			<view style="background-color: #ffffff;border-radius: 15px;padding: 20px 0;margin-top: 15px;"
				class="w-full flex wrap">
				<view v-for="(item,index) in functions" style="width: 25%;margin-top: 20px;" class="center"
					@click="toaddress(item.address,index)">
					<view>
						<image :src="item.Image" style="height: 30px;" mode="heightFix"></image>
					</view>
					<view style="padding-top: 6px;">
						{{item.title}}
					</view>
				</view>
			</view>

			<view class="flex relative1 mt-25">
				<view style="width: 30px;">
					<image src="https://mtpm.billionwz.com/statics/images/logo14.png" class="w-full block"
						mode="widthFix"></image>
				</view>
				<view class="middle">使用指引</view>
			</view>
			<view style="padding: 20px 0;" class="w-full flex justify-between">
				<view style="width: 48%;" @click="tonewslist()">
					<image src="https://mtpm.billionwz.com/statics/images/logo15.png" class="w-full" mode="widthFix">
					</image>
				</view>
				<view style="width: 48%;" @click="tonews()">
					<image src="https://mtpm.billionwz.com/statics/images/logo16.png" class="w-full" mode="widthFix">
					</image>
				</view>
			</view>
		</view>
		<u-popup v-model="popupShow1" mode="center" width="80%" border-radius="10">
			<view style="padding: 50px 20px;line-height: 20px;" class="bold center">
				是否提交申请退还保证金
			</view>
			<view class="flex">
				<view class="bold p-10-0 center agentbtn1" @click="getnumberplateRefund(0)">取消</view>
				<view class="bold p-10-0 center text-white agentbtn2" @click="getnumberplateRefund(1)">确认</view>
			</view>
		</u-popup>
	</view>
</template>

<script>
	import {
		getUserInfo
	} from '@/api/user.js';
	import {
		mapState,
		mapGetters
	} from 'vuex';
	import {
		toLogin
	} from '@/libs/login.js';
	import {
		storeCount,
		numberPlateRefund,
		getTimedDepositStatus
	} from '@/api/store.js'
	export default {
		computed: {
			...mapGetters({
				cartNum: 'cartNum',
				isLogin: 'isLogin'
			}),
			depositDisplay() {
				if (this.depositInfo && this.depositInfo.paid_total != null) {
					return this.depositInfo.paid_total;
				}
				return this.userInfo.margin || 0;
			}
		},
		data() {
			return {
				safeAreaBottom: 0,
				userInfo: {},
				functions: [{
						Image: "https://mtpm.billionwz.com/statics/images/logo18.png",
						title: "我的中拍",
						address: "/pages/auction/index"
					},
					{
						Image: "https://mtpm.billionwz.com/statics/images/logo8.png",
						title: "待支付",
						address: "/pages/goods/order_list/index?status=0"
					},
					{
						Image: "https://mtpm.billionwz.com/statics/images/logo9.png",
						title: "待发货",
						address: "/pages/goods/order_list/index?status=1"
					},
					{
						Image: "https://mtpm.billionwz.com/statics/images/logo10.png",
						title: "待收货",
						address: "/pages/goods/order_list/index?status=2"
					},
					{
						Image: "https://mtpm.billionwz.com/statics/images/logo11.png",
						title: "全部",
						address: "/pages/goods/order_list/index",
					},
					{
						Image: "https://mtpm.billionwz.com/statics/images/logo12.png",
						title: "实名认证",
						address: "/pages/realName/index"
					},
					{
						Image: "https://mtpm.billionwz.com/statics/images/logo13.png",
						title: "收货地址",
						address: "/pages/users/user_address_list/index",
					}
				],
				avatar: "/static/images/f.png",
				avatarlogo: "large",

				bidding: 0,
				plate: 0,
				depositInfo: null,
				popupShow1: false
			}
		},
		onLoad() {
			const wx = uni.getMenuButtonBoundingClientRect();
			this.safeAreaBottom = wx.bottom + 20;

			let that = this;

			// #ifdef MP
			// 小程序静默授权
			if (!this.$store.getters.isLogin) {
				// Routine.getCode()
				// 	.then(code => {
				// 		Routine.silenceAuth(code).then(res => {
				// 			this.onLoadFun();
				// 		})
				// 	})
				// 	.catch(res => {
				// 		uni.hideLoading();
				// 	});
			}
			// #endif

			// #ifdef H5 || APP-PLUS
			// if (that.isLogin == false) {
			// 	toLogin();
			// }
			//获取用户信息回来后授权
			let cacheCode = this.$Cache.get('snsapi_userinfo_code');
			let res1 = cacheCode ? option.code != cacheCode : true;
			if (this.isWeixin && option.code && res1 && option.scope === 'snsapi_userinfo') {
				this.$Cache.set('snsapi_userinfo_code', option.code);
				Auth.auth(option.code)
					.then((res) => {
						this.getUserInfo();
					})
					.catch((err) => {});
			}
			// #endif
			// #ifdef APP-PLUS
			that.$set(that, 'pageHeight', app.globalData.windowHeight);
			// #endif

		},
		onShow: function() {
			let that = this;
			if (that.isLogin) {
				this.getUserInfo();
				this.getstoreCount();
				this.loadDepositStatus();
			}
		},
		methods: {
			/**
			 * 获取个人用户信息
			 */
			getUserInfo: function() {
				let that = this;
				getUserInfo().then((res) => {
					//console.log(res.data);
					that.userInfo = res.data;
					
					that.$store.commit('SETUID', res.data.uid);
					
					uni.stopPullDownRefresh();
				});
			},

			openAuto() {
				toLogin();
			},
			// 授权回调
			onLoadFun() {
				this.getUserInfo();
				//this.getMyMenus();
				//this.setVisit();
			},

			toaddress(url, index) {
				if (index == 5 && this.userInfo.card_id) {
					uni.showToast({
						title: "已实名",
						icon: 'none',
						duration: 2000
					});
					return;
				} else {
					uni.navigateTo({
						url: url
					})
				}

			},

			toyue() {
				uni.navigateTo({
					url: "/pages/users/user_money/index"
				})
			},

			tonews() {
				uni.navigateTo({
					url: "/pages/extension/news_list/index"
				})
			},

			tonewslist() {
				uni.navigateTo({
					url: "/pages/extension/news_lists/index"
				})
			},

			getstoreCount() {
				const that = this;
				storeCount().then((res) => {
					that.bidding = res.data.bidding;
					that.plate = res.data.plate;
				})
			},

			loadDepositStatus() {
				const that = this;
				getTimedDepositStatus().then((res) => {
					that.depositInfo = res.data || null;
				}).catch(() => {
					that.depositInfo = null;
				});
			},

			tobidding() {
				uni.navigateTo({
					url: "/pages/bidding/index"
				})
			},

			toplate() {
				console.log(1)
				uni.navigateTo({
					url: "/pages/plate/index"
				})
			},
			toseting() {
				uni.navigateTo({
					url: '/pages/users/user_info/index'
				});
			},
			
			
			
			tomessage(){
				const info = this.depositInfo;
				if (!info || !info.can_apply_refund) {
					uni.showToast({
						title: (info && info.refund_msg) ? info.refund_msg : '您没有可退款的保证金',
						icon: 'none',
						duration: 2000
					});
				} else {
					this.popupShow1 = true;
				}
			},
			
			
			getnumberplateRefund(index){
				if(index == 1){
					const cateId = this.depositInfo ? (this.depositInfo.refundable_cate_id || 0) : 0;
					numberPlateRefund(cateId).then((res)=>{
						uni.showToast({
							title: res.msg,
							icon: 'none',
							duration: 2000
						});
						this.loadDepositStatus();
					})	
				}
				this.popupShow1 = false;
			}
		}

	}
</script>

<style>
	.avter {
		width: 100%;
		height: 100%;
	}

	.text-color {
		color: #fff
	}

	.center {
		text-align: center;
	}

	.flex {
		display: flex;
	}

	.justify-between {
		justify-content: space-between
	}

	.wrap {
		flex-wrap: wrap;
	}

	.bold {
		font-weight: bold;
	}

	.fontSize-18 {
		font-size: 24px;
	}

	.fontSize-14 {
		font-size: 14px;
	}

	.w-full {
		width: 100%;
	}

	.top {
		width: 100%;
		background: linear-gradient(to bottom, #790203, #b01614);
	}

	.logo {
		width: 100%;
		height: 120rpx;
		position: relative;
	}

	.nickname {
		padding: 10px 0;
		letter-spacing: 2px;
	}

	.pt-5 {
		padding-top: 5px;
	}

	.block {
		display: block;
	}

	.middle {
		position: absolute;
		top: 50%;
		left: 50%;
		transform: translate(-50%, -50%);
	}

	.relative1 {
		position: relative;
		width: 135px;
	}

	.mt-25 {
		margin-top: 25px;
	}
	
	.center {
		text-align: center;
	}
	
	.p-10-0 {
		padding: 10px 0;
	}
	
	.agentbtn1 {
		width: 100%;
		border: 1px solid #a71f24;
	}
	
	.agentbtn2 {
		width: 100%;
		background-color: #a71f24;
	}
	
	.ml-10 {
		margin-left: 10px;
	}
	
	.text-white {
		color: #fff;
	}
</style>
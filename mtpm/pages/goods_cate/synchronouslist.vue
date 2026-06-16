<template>
	<view class="w-full relative">
		<view v-if="!load">

			<view class="content">

				<!-- 顶部信息 -->
				<view class="w-full flex justify-between">
					<view class="flex">
						<view class="preview">
							同步拍.<text v-if="details.category.status==0">预展中</text><text
								v-else-if="details.category.status==1">拍卖中</text><text v-else>已结拍</text>
						</view>
						<view class="downTime" v-if="details.category.status==0 && downTime1">
							距开拍 {{downTime1}}
						</view>


					</view>
					<view style="line-height: 30px;" :class=" scribeMessage==1 ? 'activeColor':''"
						@click="sendSubscriptionInformation()">提醒</view>
				</view>

				<!-- 专场名称 -->
				<view class="w-full cate_name">
					{{details.category.cate_name}}
				</view>

				<!-- 专场介绍 -->
				<view class="w-full mt-20 flex justify-between">
					<view class="w-38">
						<image :src="details.category.big_pic" class="w-full" mode="widthFix"
							@click="previewImage(details.category.big_pic)"></image>
					</view>
					<view class="w-58 lineH-40">
						<view>拍卖时间: {{details.category.startTime1}}</view>
						<view>保证金: {{margin}}</view>
						<view>佣金: {{commission}}</view>
						<view>围观: {{details.category.view}}</view>
						<view>出价: {{bid}}</view>
					</view>
				</view>
			</view>




			<view class="mt-15 bg">
				<view class="flex justify-between w-full productBox">
					<view class="flex productTitle">
						<view :class="active==0 ? 'active':'' " @click="changeActive(0)">拍品({{details.product.length}})
						</view>
						<view class="ml-20" :class="active==1 ? 'active':'' " @click="getAuctionDetails()">拍卖详情</view>
					</view>
				</view>
				<scroll-view scroll-y :style="'height:' + scroolH +'px'" class="bg" :scroll-into-view="scrollIntoId"
					v-if="active==0">
					<view v-for="item in details.product" class="w-full flex justify-between mt-25"
						style="padding: 0 10px;" @click="todetails(item.id)" :id="'item-'+item.id">
						<view class="w-30" :style="'height:' + imageH + 'px'">
							<image :src="item.image" class="w-full productImage" mode="aspectFill">
							</image>
						</view>

						<view class="w-66">
							<view class="bold">Lot {{item.index}} {{item.store_name}}</view>
							<view class="mt-8 bg2">
								<image :src="bg2" class="w-full" mode="widthFix"></image>
								<view class="w-full productState">
									<view class="w-full flex justify-between">
										<view class="productStatus">
											<text v-if="details.category.status==0">预展中</text>
											<text
												v-else-if="details.category.status==1 && details.category.number == item.index">拍卖中</text>
											<text
												v-else-if="details.category.status==1 && details.category.number < item.index">即将开始</text>
											<text v-else>已结拍</text>
										</view>
										<view class="w-68"
											v-if="details.category.status== 0 && details.category.startTime > now ">距开拍:
											{{downTime1}}
										</view>
									</view>
								</view>
							</view>
							<view class="w-full bold mt-8 fontSize-16">
								<view v-if="item.price==0 && details.category.number <= item.index">起拍价
									{{item.ot_price}}</view>
								<view
									v-else-if="item.price!=0 && details.category.number <=item.index && details.category.status!=2">
									当前价 {{item.price}}</view>
								<view v-else>落槌价 {{item.price}}</view>
							</view>

							<view class="w-full bold mt-8" style="font-size: 14px;color: #999999;"
								v-if="(details.category.status==0 && item.count>0) || (details.category.status==1 && details.category.number<=item.index)">
								出价次数 {{item.count}}
							</view>
							<view class="w-full bold mt-8"
								v-if="details.category.status==2 || (details.category.status==1 && details.category.number>item.index)">
								{{item.presale_end_time}} 结拍
							</view>
						</view>
					</view>
					<view style="height: 130px;width: 100%;"></view>
				</scroll-view>
				<scroll-view v-else>
					
					<mp-html :content="article.content" />
					<view style="height: 130px;width: 100%;"></view>
				</scroll-view>

			</view>

			<!-- 底部按钮 -->
			<view class="bottom w-full bg">
				<view class="bg-e w-full lineH-40 center" v-if="plate">
					号牌{{plate}},剩余可竞拍额度: <text v-if="remaining<1000000">{{remaining}}</text><text v-else>99999999</text>
				</view>
				<view style="padding: 15px 10px;">
					<view class="w-full plateBtn bg-e" v-if="details.category.status==2">已结拍</view>
					<view class="w-full plateBtn" v-else-if="!plate" @click="addNumberPlate()">办理号牌</view>
					<view class="w-full plateBtn" v-else-if="state==0" @click="marginShow()">缴纳保证金</view>
					<view class="w-full plateBtn" v-else @click="toBidding()">进入专场</view>
					<!--<view class="w-full flex justify-between" style="padding: 8px 0;">
						<view>剩余额度: 0</view>
						<view>冻结额度: 0</view>
					</view>-->
				</view>
			</view>

			<u-popup v-model="show" mode="bottom">
				<view style="padding: 100px 10px;">
					<view><u-button type="primary" @click="pay(100000)">专场固定保证金(10w)</u-button></view>
					<view style="margin-top: 20px;"><u-button type="success"
							@click="marginRatio()">比例保证金(1:5)</u-button></view>
				</view>

			</u-popup>

			<u-popup v-model="popupShow" mode="center">
				<view style="padding: 20px 10px;border-radius: 5px;">
					<view class="setAmountb">
						比例保证金(1:5)
					</view>
					<view class="flex" style="margin-top: 20px;">
						<view class="btn" @click="addValue(0)">
							-</view>
						<view class="amountb">
							<input type="number" :value="value" style="height: 50px;" :disabled="true" />
						</view>
						<view class="btn" @click="addValue(1)">
							+</view>
					</view>
					<view class="flex justify-between">
						<view class="flex justify-between w-full" style="margin-top: 20px;">
							<view class="bold p-10-0 center agentbtn1" style="width: 48%;border-radius: 5px;"
								@click="pay(0)">取消</view>
							<view class="bold p-10-0 center ml-10 text-white agentbtn2"
								style="width: 48%;border-radius: 5px;" @click="pay(value)">缴纳保证金</view>
						</view>
					</view>
				</view>

			</u-popup>

			<u-popup v-model="popupShows" mode="bottom" border-radius="10">
				<view style="padding: 20px 8px;">
					<view style="text-align: right;">
						<image src="@/static/images/close.png" style="width: 20px;" mode="widthFix" @click="noshoW()">
						</image>
					</view>
					<view style="width: 300px;margin: 0 auto;">
						<image src="https://mtpm.billionwz.com/statics/images/fwh.jpg" class="w-full" mode="widthFix"
							show-menu-by-longpress>
						</image>
					</view>
					<view style="text-align: center;color:  #a71f24;">
						长按扫码关注服务号
					</view>
				</view>
			</u-popup>
		</view>
		<view v-else class="middle">
			<u-loading mode="circle"></u-loading>
		</view>
	</view>
</template>

<script>
	import mpHtml from '@/node_modules/mp-html/dist/uni-app/components/mp-html/mp-html'
	import {
		synchronizationlist,
		auctionDetails,
		getNumberPlate,
		getNumberdetails,
		getwxpay,
		//auctionDetails,
	} from '@/api/store.js';
	import {
		getUserInfo,
		SendSubscribeMessage,
		getScribeMessage
	} from '../../api/user';
	export default {
		components: {
			mpHtml
		},
		data() {
			return {
				id: 0,
				load: true, //是否加载完成
				details: "", //详情
				bid: 0, //出家次数
				timer: null,
				downTime1: "", //倒计时
				now: 0, //现在时间
				margin: "RMB 100,000或1:5", //保证金
				commission: "13.5%-15%",
				scroolH: 0,
				plate: "", //号牌
				state: 0,
				imageH: 0,
				bg2: "https://mtpm.billionwz.com/statics/images/bg2.png",
				userInfo: "",
				uid: 0,
				remaining: 0, //剩余额度
				freeze: 0, //冻结额度
				show: false,
				//margin: 0,
				popupShow: false,
				value: 10000,
				openId: "",
				popupShows: false,
				scribeMessage: 0,
				scrollIntoId: "",
				active: 0,
				article: ""
			}
		},
		onLoad(options) {
			const system = uni.getSystemInfoSync();
			const wx = uni.getMenuButtonBoundingClientRect();
			this.id = options.id;
			this.imageH = system.windowWidth * 0.3 + 15;
			//console.log(system.windowHeight);
			this.scroolH = system.windowHeight - 80;
			this.getUserInfo();
			this.popupShows = true;
			//this.getScribeMessages()
		},
		onShow() {
			this.getPlateNumberdetails(0);
			this.getsynchronizationlist();
			getApp().globalData.type = {
				type: 'category1',
				data: {
					id: this.id,

				}
			};

			this.sendMessage({
				type: 'category1',
				data: {
					id: this.id,

				}
			});

			this.sendData({
				type: 'category1',
				data: {
					id: this.id,

				}
			})
			this.getScribeMessages()
		},
		onHide() {
			clearInterval(this.time1);
			this.time1 = null;
			getApp().globalData.type = "";
			uni.$off('socketMessage', this.handleMessage);
		},
		onUnload() {
			clearInterval(this.time1);
			this.time1 = null;
			getApp().globalData.type = "";
			uni.$off('socketMessage', this.handleMessage);

		},

		onShareAppMessage() {
			const that = this;
			return {
				title: that.details.category.cate_name, // 分享标题
				path: '/pages/goods_cate/synchronouslist?id=' + this.id, // 分享的页面路径，通常携带一些参数
				imageUrl: that.details.category.big_pic // 可选，自定义分享图片
			};
		},


		onShareTimeline() {
			const that = this;
			return {
				title: that.details.category.cate_name, // 分享标题
				path: '/pages/goods_cate/synchronouslist?id=' + this.id, // 分享的页面路径，通常携带一些参数
				imageUrl: that.details.category.big_pic // 可选，自定义分享图片
			};
		},

		methods: {
			handleMessage(data) {

				//console.log(data);
				if (data['type'] == 'category1') {
					//console.log(data);
					const data1 = JSON.parse(data.data);
					//const data1 = data.data;
					const that = this;
					that.details = data1;

					if (that.load == true) {
						that.load = false;
					}

					const date = new Date(that.details.category.startTime * 1000);
					//let year = date.getFullYear();
					let month = ('0' + (date.getMonth() + 1)).slice(-2); // 月份从0开始，需要+1
					let day = ('0' + date.getDate()).slice(-2);
					let hours = ('0' + date.getHours()).slice(-2);
					let minutes = ('0' + date.getMinutes()).slice(-2);
					//let seconds = ('0' + date.getSeconds()).slice(-2);

					that.details.category.startTime1 = `${month}月${day}日 ${hours}:${minutes}`;


					var bid1 = 0;
					for (let i = 0; i < that.details.product.length; i++) {
						that.details.product[i].index = i + 1;
						bid1 = bid1 + that.details.product[i].count;
						if (that.details.product[i].presale_end_time > 0) {
							const date = new Date(that.details.product[i].presale_end_time * 1000);
							//let year = date.getFullYear();
							let month = ('0' + (date.getMonth() + 1)).slice(-2); // 月份从0开始，需要+1
							let day = ('0' + date.getDate()).slice(-2);
							let hours = ('0' + date.getHours()).slice(-2);
							let minutes = ('0' + date.getMinutes()).slice(-2);


							that.details.product[i].presale_end_time = `${month}月${day}日 ${hours}:${minutes}`;
						}
					}
					that.bid = bid1;
					var time = Date.now();
					var downtime1 = that.details.category.startTime * 1000 - time;
					if (downtime1 > 0) {
						this.downTime();
					}

				}
			},

			sendMessage(message) {
				uni.$on('socketMessage', this.handleMessage);
				this.time1 = setInterval(() => {
					if (getApp().globalData.socketTask && getApp().globalData.socketTask.readyState === getApp()
						.globalData.socketTask.OPEN) {
						// 发送消息，通常将消息对象转换为JSON字符串
						getApp().globalData.socketTask.send({
							data: JSON.stringify(message),
							success: () => {
								console.log("发送成功");
							},
							fail: (err) => {
								console.error('消息发送失败', err);
							}
						});
					} else {
						console.warn('WebSocket 连接未建立，无法发送消息');
					}
				}, 20000)
			},


			sendData(message) {
				if (getApp().globalData.socketTask && getApp().globalData.socketTask.readyState === getApp().globalData
					.socketTask.OPEN) {
					// 发送消息，通常将消息对象转换为JSON字符串
					getApp().globalData.socketTask.send({
						data: JSON.stringify(message),
						success: () => {
							console.log("发送成功");
						},
						fail: (err) => {
							console.error('消息发送失败', err);
						}
					});
				} else {
					console.warn('WebSocket 连接未建立，无法发送消息');
				}
			},

			/*
			 * 获取用户信息
			 */
			getUserInfo: function() {
				let that = this;
				getUserInfo().then((res) => {

					that.userInfo = res.data;
					that.uid = res.data.uid;
					//console.log(res.data);
				});
			},

			/**
			 * 获取专场信息
			 */
			getsynchronizationlist() {
				const that = this;
				synchronizationlist(that.id).then((res) => {
					//	console.log(res);
					that.details = res.data;
					if (that.load == true) {
						that.load = false;
					}

					const date = new Date(that.details.category.startTime * 1000);
					//let year = date.getFullYear();
					let month = ('0' + (date.getMonth() + 1)).slice(-2); // 月份从0开始，需要+1
					let day = ('0' + date.getDate()).slice(-2);
					let hours = ('0' + date.getHours()).slice(-2);
					let minutes = ('0' + date.getMinutes()).slice(-2);
					//let seconds = ('0' + date.getSeconds()).slice(-2);

					that.details.category.startTime1 = `${month}月${day}日 ${hours}:${minutes}`;


					var bid1 = 0;
					for (let i = 0; i < that.details.product.length; i++) {
						that.details.product[i].index = i + 1;
						if (that.details.product[i].index == that.details.category.number) {
							that.scrollIntoId = "item-" + that.details.product[i].id;
						}
						bid1 = bid1 + that.details.product[i].count;
						if (that.details.product[i].presale_end_time > 0) {
							const date = new Date(that.details.product[i].presale_end_time * 1000);
							//let year = date.getFullYear();
							let month = ('0' + (date.getMonth() + 1)).slice(-2); // 月份从0开始，需要+1
							let day = ('0' + date.getDate()).slice(-2);
							let hours = ('0' + date.getHours()).slice(-2);
							let minutes = ('0' + date.getMinutes()).slice(-2);


							that.details.product[i].presale_end_time = `${month}月${day}日 ${hours}:${minutes}`;
						}
					}
					that.bid = bid1;
					var time = Date.now();
					var downtime1 = that.details.category.startTime * 1000 - time;
					if (downtime1 > 0) {
						this.downTime();
					}

				})
			},

			/**
			 * 倒计时
			 */
			downTime() {
				const that = this;
				this.timer = setInterval(() => {
					var time = Date.now();
					var downtime = that.details.category.startTime * 1000 - time;
					that.now = time / 1000;
					if (downtime <= 0) {
						that.getsynchronizationlist();
						that.downTime1 = `0天0时0分0秒` // 替换整个数组	
						clearInterval(that.timer)
						that.timer = null; // 重要：清除后重置引用

					} else if (downtime > 0) {
						const d = Math.floor(downtime / 86400000);
						const h = Math.floor((downtime % 86400000) / 3600000);
						const m = Math.floor((downtime % 3600000) / 60000);
						const s = Math.floor((downtime % 60000) / 1000);
						that.downTime1 = `${d}天${h}时${m}分${s}秒` // 替换整个数组		
					}



				}, 1000)
			},

			//办理号牌
			addNumberPlate() {
				const that = this;
				//console.log(this.userInfo);
				if (!this.userInfo) {
					this.getUserInfo();
					return;
				}

				if (!this.userInfo.card_id) {
					uni.showModal({
						title: "提示",
						content: '请先完成实名认证',
						confirmText: "去绑定",
						success: function(res) {
							if (res.confirm) {
								uni.navigateTo({
									url: "/pages/realName/index"
								})
							} else if (res.cancel) {

							}
						}
					})
					return;
				}
				getNumberPlate(that.id).then((res) => {
					//console.log(res);
					that.getPlateNumberdetails(1);
				})
			},

			//获取号牌
			getPlateNumberdetails(index) {
				const that = this;
				//console.log(that.details.category);
				getNumberdetails(that.id).then((res) => {
					that.plate = res.data.number_plate;
					that.state = res.data.state;
					that.remaining = res.data.remaining;
					that.freeze = res.data.freeze;
					//that.orderId = res.data.orderId;
					that.openId = res.data.openid.openid;
					//console.log(res.data);
					if (that.plate && index == 1) {
						uni.showToast({
							title: '您的号牌是' + that.plate,
							icon: 'none',
							duration: 2000
						});

					}
				})
			},

			//选择缴纳保证金方式
			marginShow() {
				this.show = true;
			},

			previewImage(url) {
				uni.previewImage({
					current: url, // 当前显示图片的链接
					urls: [url] // 需要预览的图片列表（支持多张）
				});
			},

			todetails(idx) {
				//console.log(idx);
				//	console.log(this.id);
				uni.navigateTo({
					url: "/pages/goods_cate/synchronous_product_details?id=" + idx + "&cate_id=" + this.id
				})
			},

			toBidding() {
				uni.navigateTo({
					url: "/pages/goods_cate/synchronous_bidding?cid=" + this.id,
				})
			},

			pay(total_fee) {
				this.show = false;
				if (total_fee == 0) {
					this.popupShow = false;
				} else {
					const that = this;
					uni.showModal({
						title: "提示",
						content: "是否支付" + total_fee + "元保证金，获得" + total_fee * 5 + "的竞拍额度",
						success(res) {
							if (res.confirm) {
								getwxpay(that.openId, that.id, total_fee).then((res) => {
									that.wxpay(res.data);
								})
							}
						}
					})
				}

			},

			wxpay(paymentParams) {
				const that = this;
				uni.requestPayment({
					// 通用参数，不同平台会自动识别所需字段
					provider: 'wxpay', // 固定为微信支付
					// 微信小程序所需参数
					timeStamp: paymentParams.timeStamp,
					nonceStr: paymentParams.nonceStr,
					package: paymentParams.package,
					signType: paymentParams.signType,
					paySign: paymentParams.paySign,
					// 微信APP支付所需参数（注意字段名可能与小程序不同，后端返回需保持一致）
					// orderInfo: paymentParams, // 有时APP支付会将所有参数放在orderInfo对象中
					// 具体参考 UniApp 官方文档: https://uniapp.dcloud.io/api/plugins/payment

					success: (res) => {
						//console.log('支付成功', res);
						//that.getPlateNumberdetails();
						uni.showToast({
							title: '支付成功'
						});
						setTimeout(() => {
							// 这里放置需要延时执行的代码
							that.getPlateNumberdetails(0);
						}, 2000);

						that.popupShow = false;
						// 支付成功后的业务逻辑，例如跳转到成功页面
					},
					fail: (err) => {
						//console.log('支付失败', err);
						// 用户取消支付或支付失败处理
						let errMsg = '支付失败';
						if (err.errMsg === 'requestPayment:fail cancel') {
							errMsg = '您已取消支付';
						}
						uni.showToast({
							title: errMsg,
							icon: 'none'
						});
					}
				});
			},


			marginRatio() {
				this.show = false;
				this.popupShow = true;
				this.value = 10000;
				//this.value = 1;
			},

			addValue(index) {
				if (index == 0) {
					if (this.value - 10000 >= 10000) {
						this.value = this.value - 10000;
					}
				} else {
					this.value = this.value + 10000;
				}
			},

			noshoW() {
				this.popupShows = false;
			},


			sendSubscriptionInformation() {
				if (this.scribeMessage == 1) {
					return;
				}
				const tmplId = 'lzI5nOgDKOhAQoUexbLXDWrK2cSU05BgLA1dEbsYuEM';
				const that = this;
				uni.requestSubscribeMessage({
					tmplIds: [tmplId], // 数组格式，一次最多3条[citation:2]
					success: (res) => {
						// res 格式示例：{ errMsg: "requestSubscribeMessage:ok", '模板ID': 'accept' }
						if (res[tmplId] === 'accept') {

							SendSubscribeMessage(tmplId, that.id).then((res) => {
								//console.log('用户同意订阅');
								uni.showToast({
									title: '用户同意订阅'
								});

								that.getScribeMessages();
							})


							// 通常此处将授权成功标识（如用户的code）发送给自己的后端服务器
						} else if (res[tmplId] === 'reject') {

							uni.showToast({
								title: '用户拒绝订阅'
							});
						} else if (res[tmplId] === 'ban') {
							uni.showToast({
								title: '已被后台封'
							});
							//console.log('已被后台封禁');
						}
					},
					fail: (err) => {
						console.error('调用授权接口失败:', err);
						// 处理失败情况，例如用户彻底关闭了通知权限 (err.errCode === 20004)[citation:7]
					}
				});
			},

			getScribeMessages() {
				getScribeMessage(this.id).then((res) => {
					if (res.msg == "true") {
						this.scribeMessage = 1;
					}
				})
			},

			getAuctionDetails() {
				const that = this;

				if (that.article) {
					that.active = 1;
					return;
				}
				console.log(that.details.category.article_id);
				auctionDetails(that.details.category.article_id).then((res) => {
					that.article = res.data;
					console.log(res.data)
					that.active = 1;
				})
			},
			
			
			changeActive(index) {
				this.active = index;
			},
		}
	}
</script>

<style>
	page {
		/*background-color: #fff;*/
		font-family: '仿宋';
		color: #000000;
		font-weight: bold;
		font-size: 14px;
	}

	.content {
		padding: 20px 10px;
		background-color: #ffffff;
		margin-bottom: 20px;
	}

	.bold {
		font-weight: bold;
	}

	.w-full {
		width: 100%;
	}

	.h-full {
		height: 100%;
	}

	.flex {
		display: flex;
	}

	.justify-between {
		justify-content: space-between;
	}

	.preview {
		background-color: #a71f24;
		color: #fff;
		border-radius: 30px;
		text-align: center;
		width: 140px;
		line-height: 30px;
	}

	.downTime {
		padding-left: 10px;
		line-height: 30px;
	}

	.cate_name {
		margin-top: 20px;
		font-size: 30px;
		letter-spacing: 3px;
		font-weight: bold;
		line-height: 40px;
	}

	.w-38 {
		width: 38%;
	}

	.w-58 {
		width: 58%;
	}

	.mt-20 {
		margin-top: 20px;
	}

	.middle {
		position: absolute;
		top: 50%;
		left: 50%;
		transform: translate(-50%, -50%);
	}

	.lineH-40 {
		line-height: 40px;
	}

	.bg {
		background-color: #fff;
	}

	.bottom {
		position: fixed;
		bottom: 0;
		border-top: 1px solid #e9e9e9;
	}

	.relative {
		position: relative;
	}

	.plateBtn {
		background-color: #a71f24;
		line-height: 40px;
		text-align: center;
		color: #ffffff;
		border-radius: 10rpx;
	}

	.bg-e {
		background-color: #e5e5e5 !important;
	}

	.w-30 {
		width: 30%;
	}

	.productImage {
		height: 100%;
		object-fit: cover;
		border-radius: 10px;
	}

	.w-66 {
		width: 66%;
	}

	.productBox {
		border-bottom: 1px solid #e9e9e9;
		padding: 20px 10px 0;
		margin-bottom: 20px;
	}

	.productTitle {
		line-height: 60px;
		font-size: 18px;
	}

	.bg2 {
		width: 250px;
		position: relative;
	}

	.productState {
		position: absolute;
		top: 0;
		line-height: 24px;
		font-size: 12px;
	}

	.productStatus {
		width: 28%;
		text-align: center;
		color: #ffffff;
	}

	.product-p {
		padding: 20px 10px;
	}

	.w-68 {
		width: 68%;
	}

	.mt-8 {
		margin-top: 8px;
	}

	.center {
		text-align: center;
	}

	.mt-25 {
		margin-top: 25px;
	}

	.setAmountb {
		font-size: 25px;
		font-weight: bold;
		text-align: center;
		width: 100%;
	}

	.btn {
		width: 45px;
		text-align: center;
		line-height: 50px;
		border-radius: 5px;
		border: 1px solid #e0e0e0;
		font-size: 20px;
		font-weight: bold;
	}


	.amountb {
		text-align: center;
		width: 170px;
		background-color: #a71f24;
		margin: 0 10px;
		color: #fff;
		border-radius: 5px;
	}

	.agentbtn1 {
		width: 130px;
		border: 1px solid #a71f24;
	}

	.agentbtn2 {
		width: 130px;
		background-color: #a71f24;
		color: #fff
	}

	.p-10-0 {
		padding: 10px 0;
	}

	.fontSize-16 {
		font-size: 16px;
	}

	.activeColor {
		color: #a71f24;
	}
	
	.active {
		border-bottom: 1px solid #000000;
		font-weight: bold;
	}
</style>
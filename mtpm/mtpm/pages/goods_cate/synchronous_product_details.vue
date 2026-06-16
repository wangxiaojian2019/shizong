<template>
	<view v-if="!load">

		<!-- 轮播 -->
		<view class="w-full">
			<swiper class="w-full" :style="'height:' + swiperH + 'px'">
				<swiper-item v-for="(item, index) in detail.slider_image" :key="index">
					<image :src="item" class="w-full" mode="widthFix" @click="previewImage(item)"></image>
				</swiper-item>
			</swiper>
		</view>

		<!--产品名称 -->
		<view class="w-full bg pb-20">
			<view class="p-20-10-10">
				<text v-if="cate.status == 0">预展中 {{cate.startTime1}} 开拍 </text>
				<text v-else-if="cate.status == 1 && cate.number < index">即将开始</text>
				<text v-else-if="cate.status == 1 && cate.number == index">拍卖中</text>
				<text v-else>已结拍</text>
			</view>

			<view class="flex justify-between p-10">
				<view class="bold">Lot {{index}}</view>
				<view :style="'width:' + lotW + 'px'">
					<scroll-view scroll-x="true" style="white-space: nowrap;" :scroll-left="scrollLeft">
						<view v-for="item in categoryProduct" style="width: auto;padding: 0 5px;color: #999;"
							class="inline-block" @click="toProduct(cate_id,item.id)">
							Lot {{item.index}}
						</view>

					</scroll-view>
				</view>
			</view>


			<view class="bold p-10 productTitle">
				{{detail.store_name}}
			</view>

			<view class="flex bold p-10">
				<text v-if="detail.price==0">起拍价 RMB {{detail.ot_price}}</text>
				<text v-else-if="detail.price!=0 && cate.status!=2 && index >= cate.number">当前价 RMB <text
						class="text-a71f24" style="margin-left: 5px">{{detail.price}}</text></text>
				<text v-else>落槌价 RMB {{detail.price}}</text>
			</view>
			<view class="bold p-0-10" v-if="agent_price">
				我的委托 RMB {{agent_price}}<text class="text-a71f24" v-if="detail.price >= agentPrices">(您的代理金额已被超越)</text>
			</view>
		</view>

		<view v-if="bidding && (cate.status == 0 || (cate.status == 1 &&  cate.number <= index))" class="biddingBox">
			<view class="flex justify-between bold">
				<view>竞拍记录</view>
				<view>{{bidding.length}}次</view>
			</view>
			<scroll-view scroll-x="true" style="white-space: nowrap; width: 100%;margin-top: 20px;">
				<view v-for="(item,index) in bidding" style="width: auto;" class="center inline-block"
					:class="index!=0 ? 'ml-10':''">
					<view v-if="index == 0" class="lead">
						{{item.plate}}号 领先
					</view>
					<view v-else class="eliminate">
						{{item.plate}}号 出局
					</view>
					<view style="letter-spacing: 1px;padding: 10px 15px;">
						<view class="bold">RMB</view>
						<view class="bold" style="font-size: 18px;">{{item.price}}</view>
					</view>
				</view>
			</scroll-view>
		</view>

		<!-- 专场介绍 -->
		<view class="bg flex warp introduction">
			<view class="flex p-10-0 half">
				<view class="w-15">
					<image src="@/static/images/logo1.png" class="w-full" mode="widthFix"></image>
				</view>
				<view class="ml-10">
					<view>保证金</view>
					<view class="bold mt-5">RMB 100,000或1:5</view>
				</view>

			</view>
			<view class="flex p-10-0 half">
				<view class="w-15">
					<image src="@/static/images/logo2.png" class="w-full" mode="widthFix"></image>
				</view>
				<view class="ml-10">
					<view>佣金</view>
					<view class="bold mt-5">13.5%-15%</view>
				</view>

			</view>
			<view class="flex p-10-0 half" @click="biddingIncrement(1)">
				<view class="w-15">
					<image src="@/static/images/logo3.png" class="w-full" mode="widthFix"></image>
				</view>
				<view class="ml-10">
					<view>竞价阶梯</view>
					<view class="bold mt-5">查看详情</view>
				</view>
			</view>
			<view class="flex p-10-0 half">
				<view class="w-15">
					<image src="@/static/images/logo4.png" class="w-full" mode="widthFix"></image>
				</view>
				<view class="ml-10">
					<view>运费</view>
					<view class="bold mt-5">到付</view>
				</view>
			</view>
		</view>

		<view class="bg flex warp productIntroduction">
			<view class="w-full bold">拍品介绍</view>
			<view class="w-full mt-20">
				<!--<rich-text :nodes="detail.description"></rich-text>-->
				<view v-if="detail.video_link" style="padding-bottom: 15px;">
					<video :src="detail.video_link" class="w-full" controls="true"></video>
				</view>
				<mp-html :content="detail.description" />
			</view>
		</view>
		<view class="bottom w-full bg">
			<view class="bg-e w-full lineH-40 center" v-if="plate">
				号牌{{plate}},剩余额度: <text v-if="remaining<1000000">{{remaining}}</text><text v-else>99999999</text> <text
					style="padding-left: 8px;color:#a71f24;" @click="marginRatio()">增加额度</text>
			</view>
			<view style="padding: 15px 10px;">

				<view class="w-full plateBtn" v-if="!plate && cate.status == 0" @click="addNumberPlate()">办理号牌</view>
				<view class="w-full plateBtn" v-else-if="plate && cate.status == 0 && state==0" @click="marginShow()">
					缴纳保证金</view>
				<view class="w-full plateBtn" v-else-if="plate && cate.status == 0 && state==1 && !agent_price"
					@click="proxyBidding()">
					代理出价</view>
				<view class="w-full plateBtn" v-else-if="plate && cate.status == 0 && state==1 && agent_price"
					@click="cancelAgent()">
					取消代理出价</view>
				<view class="w-full flex justify-between" v-else-if="cate.status == 1">
					<view class="plateBtn btn bg-e" v-if="cate.number > index && uid!=detail.uid">已结拍</view>
					<view class="plateBtn btn" v-else-if="cate.number > index && uid==detail.uid" @click="getcart()">去支付
					</view>
					<view class="plateBtn btn" v-else-if="plate && state==0 && cate.number <= index"
						@click="marginShow()">缴纳保证金</view>
					<view class="plateBtn btn" v-else-if="plate && state==1 && cate.number <= index && !agent_price"
						@click="proxyBidding()">代理出价</view>
					<view class="plateBtn btn" v-else-if="plate && state==1 && cate.number <= index && agent_price"
						@click="cancelAgent()">取消代理出价</view>
					<view class="plateBtn btn" v-else @click="addNumberPlate()">办理号牌</view>

					<view class="plateBtn w-48" @click="toBidding()">进入专场</view>
					<!--<view class="w-full plateBtn" v-else-if="state==0" @click="marginShow()">缴纳保证金</view>
					<view class="w-full plateBtn" v-else-if="cate.status == 0">代理出价</view>
					<view class="w-full plateBtn" v-else-if="cate.status == 1">进入专场</view>
					<view class="w-full plateBtn bg-e" v-else-if="uid!=detail.uid">已结拍</view>
					<view class="w-full plateBtn bg-e" v-else>去支付</view>-->
				</view>
				<view v-else-if="cate.status == 2 && uid!=detail.uid" class="w-full plateBtn bg-e">已结拍</view>
				<view v-else class="w-full plateBtn" @click="getcart()">去支付</view>
			</view>

		</view>

		<u-popup v-model="show" mode="bottom">
			<view style="padding: 100px 10px;">
				<view><u-button type="primary" @click="pay(100000)">专场固定保证金(10w)</u-button></view>
				<view style="margin-top: 20px;"><u-button type="success" @click="marginRatio()">比例保证金(1:5)</u-button>
				</view>
			</view>

		</u-popup>

		<u-popup v-model="popupShow" mode="center">
			<view style="padding: 20px 10px;border-radius: 5px;">
				<view class="setAmountb">
					比例保证金(1:5)
				</view>
				<view class="flex" style="margin-top: 20px;">
					<view class="btns" @click="addValue(0)">
						-</view>
					<view class="amountb">
						<input type="number" :value="value" style="height: 50px;" :disabled="true" />
					</view>
					<view class="btns" @click="addValue(1)">
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

		<u-popup v-model="popupShow1" mode="center">
			<view style="padding: 20px 10px;border-radius: 5px;">
				<view class="setAmountb">
					代理出价
				</view>
				<view class="flex" style="margin-top: 20px;">
					<view class="btns" @click="getPrice(0)">
						-</view>
					<view class="amountb">
						<input type="number" :value="value" style="height: 50px;" :disabled="true" />
					</view>
					<view class="btns" @click="getPrice(1)">
						+</view>
				</view>
				<view class="flex justify-between">
					<view class="flex justify-between w-full" style="margin-top: 20px;">
						<view class="bold p-10-0 center agentbtn1" style="width: 48%;border-radius: 5px;"
							@click="postagent(0)">取消</view>
						<view class="bold p-10-0 center ml-10 text-white agentbtn2"
							style="width: 48%;border-radius: 5px;" @click="postagent(1)">确认</view>
					</view>
				</view>
			</view>

		</u-popup>
	</view>
</template>

<script>
	import mpHtml from '@/node_modules/mp-html/dist/uni-app/components/mp-html/mp-html'
	import {
		getproductDetails,
		synchronizationlist,
		getNumberPlate,
		getNumberdetails,
		addSynchronousPrice,
		getAgentPrices,
		agentPriceDetail,
		cancelAgentPrices,
		getbiddingList,
		getwxpay,
		addView
	} from '@/api/store.js';
	import {
		getUserInfo,
		userShare
	} from '@/api/user.js';
	export default {
		components: {
			mpHtml
		},
		data() {
			return {
				load: true, //是否加载完成
				swiperH: 650, //swiper高度
				detail: "",
				id: 0,
				cate_id: 0,
				swiperH: 600,
				cate: "",
				categoryProduct: "",
				index: 1,
				lotW: 0,
				agent_price: "",
				bidding: "",
				plate: "", //号牌
				state: 0,
				remaining: 0, //剩余额度
				freeze: 0, //冻结额度
				userInfo: 0,
				uid: 0,
				show: false,
				popupShow: false,
				value: 10000,
				popupShow1: false,
				openId: "",
				timer: null
			}
		},
		onLoad(options) {
			this.id = options.id;
			this.cate_id = options.cate_id;
			const system = uni.getSystemInfoSync();
			//console.log(this.id);
			//console.log(this.cate_id);
			this.lotW = system.windowWidth - 70;
			this.getUserInfo();
			//this.agentW = system.windowWidth - 115;
		},
		onShow() {
			this.getDetails();
			this.productDetails();
			this.getPlateNumberdetails(0);
			getApp().globalData.type = {
				type: 'product1',
				data: {
					id: this.id
				}
			};

			this.sendMessage({
				type: 'product1',
				data: {
					id: this.id
				}
			});

			this.sendData({
				type: 'product1',
				data: {
					id: this.id
				}
			})

			this.getagentPrice();
			this.biddingList();
			this.downTime();
			setTimeout(() => {
				// 3 秒后执行的代码
				this.getview(this.cate_id);
			}, 3000);
		},
		onHide() {
			clearInterval(this.time1);
			this.time1 = null;
			getApp().globalData.type = "";
			uni.$off('socketMessage', this.handleMessage);
			clearInterval(this.timer);
			this.timer = null;
		},
		onUnload() {
			clearInterval(this.time1);
			this.time1 = null;
			getApp().globalData.type = "";
			uni.$off('socketMessage', this.handleMessage);
			clearInterval(this.timer);
			this.timer = null;
		},
		onShareAppMessage() {
			const that = this;
			return {
				title: that.detail.store_name, // 分享标题
				path: '/pages/goods_cate/synchronous_product_details?id=' + this.id + "&cate_id=" + that.cate
				.id, // 分享的页面路径，通常携带一些参数
				imageUrl: that.detail.image // 可选，自定义分享图片
			};
		},


		onShareTimeline() {
			const that = this;
			return {
				title: that.detail.store_name, // 分享标题
				path: '/pages/goods_cate/synchronous_product_details?id=' + this.id + "&cate_id=" + that.cate
				.id, // 分享的页面路径，通常携带一些参数
				imageUrl: that.detail.image // 可选，自定义分享图片
			};
		},
		methods: {
			handleMessage(data) {
				const that = this;
				//const data1 = JSON.parse(data.data);
				console.log(data);
				//console.log(1);
				if (data['type'] == "product11") {
					that.cate = data['data'][0].category;
					that.categoryProduct = data['data'][0].product;
					if (that.cate.status == 0) {
						const date = new Date(that.cate.startTime * 1000);

						let month = ('0' + (date.getMonth() + 1)).slice(-2); // 月份从0开始，需要+1
						let day = ('0' + date.getDate()).slice(-2);
						let hours = ('0' + date.getHours()).slice(-2);
						let minutes = ('0' + date.getMinutes()).slice(-2);
						let seconds = ('0' + date.getSeconds()).slice(-2);

						that.cate.startTime1 = `${month}月${day}日 ${hours}:${minutes}`;
					}

					for (let i = 0; i < that.categoryProduct.length; i++) {
						that.categoryProduct[i].index = i + 1;
						if (that.categoryProduct[i].id == that.id) {
							that.index = i + 1;
							//that.scrollLeft = i * 50;

						}
					}
				} else if (data['type'] == "product21") {
					that.detail = data['data'][0];
					that.getImageH(that.detail.slider_image[0]);
					that.load = false;
					//that.detail.uid = 3;
					that.detail.order_id = 1;
					//console.log(that.detail);
					if (that.detail.presale_end_time) {

						let data = new Date(that.detail.presale_end_time);
						data = data.getTime();
						data = data + 7 * 24 * 3600 * 1000;
						const date = new Date(data);
						let month = ('0' + (date.getMonth() + 1)).slice(-2); // 月份从0开始，需要+1
						let day = ('0' + date.getDate()).slice(-2);
						let hours = ('0' + date.getHours()).slice(-2);
						let minutes = ('0' + date.getMinutes()).slice(-2);
						that.detail.presale_end_time1 = `${month}月${day}日 ${hours}:${minutes}`;
					}
				} else if (data['type'] == "product31") {
					that.bidding = data['data'][0];
				}
			},

			previewImage(url) {
				uni.previewImage({
					current: url, // 当前显示图片的链接
					urls: [url] // 需要预览的图片列表（支持多张）
				});
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
								//console.log(message);
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
			
			getview(id) {
				addView(id).then((res) => {}).catch((err) => {
					console.error("addView 调用失败:", err);
				});
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

			biddingList() {
				const that = this;
				getbiddingList(that.id).then((res) => {

					that.bidding = res.data;
				})
			},


			/*
			 * 获取用户信息
			 */
			getUserInfo: function() {
				let that = this;
				getUserInfo().then((res) => {

					that.userInfo = res.data;
					that.uid = res.data.uid;

				});
			},

			getDetails() {
				const that = this;

				synchronizationlist(that.cate_id).then((res) => {
					that.cate = res.data.category;
					that.categoryProduct = res.data.product;
					//console.log(that.categoryProduct);
					if (that.cate.id != that.cate_id) {
						that.cate.status = 2;
						//that.navShow = 1;
					}
					//开拍时间

					if (that.cate.status == 0) {
						const date = new Date(that.cate.startTime * 1000);

						let month = ('0' + (date.getMonth() + 1)).slice(-2); // 月份从0开始，需要+1
						let day = ('0' + date.getDate()).slice(-2);
						let hours = ('0' + date.getHours()).slice(-2);
						let minutes = ('0' + date.getMinutes()).slice(-2);
						let seconds = ('0' + date.getSeconds()).slice(-2);

						that.cate.startTime1 = `${month}月${day}日 ${hours}:${minutes}`;
					}

					for (let i = 0; i < that.categoryProduct.length; i++) {
						that.categoryProduct[i].index = i + 1;
						if (that.categoryProduct[i].id == that.id) {
							that.index = i + 1;
							//that.scrollLeft = i * 50;

						}
					}
				})
			},

			productDetails() {
				const that = this;
				that.selector = [];
				getproductDetails(that.id).then((res) => {
					that.detail = res.data;
					that.getImageH(that.detail.slider_image[0]);
					that.load = false;
					//that.detail.uid = 3;
					that.detail.order_id = 1;
					//console.log(that.detail);
					if (that.detail.presale_end_time) {

						let data = new Date(that.detail.presale_end_time);
						data = data.getTime();
						data = data + 7 * 24 * 3600 * 1000;
						const date = new Date(data);
						let month = ('0' + (date.getMonth() + 1)).slice(-2); // 月份从0开始，需要+1
						let day = ('0' + date.getDate()).slice(-2);
						let hours = ('0' + date.getHours()).slice(-2);
						let minutes = ('0' + date.getMinutes()).slice(-2);
						that.detail.presale_end_time1 = `${month}月${day}日 ${hours}:${minutes}`;
					}

					// if (that.detail.presale_start_time > 0 && that.detail.presale_end_time == 0 && that.timer ==
					// 	null) {
					// 	that.downTime()
					// }
					// //that.startingBid = that.detail.ot_price.toLocaleString('en-US');
					// //that.startingBid = that.detail.ot_price
				})
			},

			//获取图片高度
			getImageH(url) {
				const system = uni.getSystemInfoSync();
				const that = this;
				uni.getImageInfo({
					src: url,
					success: function(res) {
						const originalWidth = res.width;
						const originalHeight = res.height;
						// 计算高度
						const aspectRatio = originalHeight / originalWidth;

						that.swiperH = system.windowWidth * aspectRatio;

					}
				});
			},

			downTime() {
				const that = this;
				this.timer = setInterval(() => {
					this.getPlateNumberdetails(0);
				}, 30000)

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
				getNumberPlate(that.cate_id).then((res) => {
					//console.log(res);
					that.getPlateNumberdetails(1);
				})
			},

			//获取号牌
			getPlateNumberdetails(index) {
				const that = this;
				//console.log(that.details.category);
				getNumberdetails(that.cate_id).then((res) => {
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

			toBidding() {
				uni.navigateTo({
					url: "/pages/goods_cate/synchronous_bidding?cid=" + this.cate_id,
				})
			},

			//选择缴纳保证金方式
			marginShow() {
				this.show = true;
			},

			pay(total_fee) {
				this.show = false;
				if (total_fee == 0) {
					this.popupShow = false;
				} else {
					// 	const that = this;
					// 	//console.log(that.openId);
					// 	getwxpay(that.openId, that.cate_id, total_fee).then((res) => {
					// 		that.wxpay(res.data);
					// 	})
					const that = this;
					uni.showModal({
						title: "提示",
						content: "是否支付" + total_fee + "元保证金，获得" + total_fee * 5 + "的竞拍额度",
						success(res) {
							if (res.confirm) {
								getwxpay(that.openId, that.cate_id, total_fee).then((res) => {
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
						setTimeout(() => {
							// 这里放置需要延时执行的代码
							that.getPlateNumberdetails(0);
						}, 2000);
						uni.showToast({
							title: '支付成功'
						});
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

			proxyBidding() {

				const that = this;
				var price = that.detail.price;
				if (price == 0 && that.detail.ot_price != 0) {
					if (that.detail.ot_price <= this.remaining) {
						that.value = that.detail.ot_price;
						that.popupShow1 = true;
					} else {
						uni.showToast({
							title: '出价额度不足,请先增加出价额度后出价',
							icon: 'none',
							duration: 2000
						});
					}

				} else if (price == 0 && that.detail.ot_price == 0) {
					if (this.remaining >= 2000) {
						that.value = 2000;
						that.popupShow1 = true;
					} else {
						uni.showToast({
							title: '出价额度不足,请先增加出价额度后出价',
							icon: 'none',
							duration: 2000
						});
					}
				} else {
					if (price < 100000) {
						price = (price / 1000) % 10;
						console.log(price);
						if (price == 0 || price == 8) {
							if (that.detail.price + 2000 <= this.remaining) {
								that.value = that.detail.price + 2000;
								that.popupShow1 = true;
							} else {
								uni.showToast({
									title: '出价额度不足,请先增加出价额度后出价',
									icon: 'none',
									duration: 2000
								});
							}

						} else {
							if (that.detail.price + 3000 <= this.remaining) {
								that.value = that.detail.price + 3000;
								that.popupShow1 = true;
							} else {
								uni.showToast({
									title: '出价额度不足,请先增加出价额度后出价',
									icon: 'none',
									duration: 2000
								});
							}
						}
					} else {
						if (that.cate.bidding_ladder == 0) {
							price = (price / 10000) % 10;
							if (price == 0 || price == 8) {
								if (that.detail.price + 20000 <= this.remaining) {
									that.value = that.detail.price + 20000;
									that.popupShow1 = true;
								} else {
									uni.showToast({
										title: '出价额度不足,请先增加出价额度后出价',
										icon: 'none',
										duration: 2000
									});
								}
							} else {
								if (that.detail.price + 30000 <= this.remaining) {
									that.value = that.detail.price + 30000;
									that.popupShow1 = true;
								} else {
									uni.showToast({
										title: '出价额度不足,请先增加出价额度后出价',
										icon: 'none',
										duration: 2000
									});
								}
							}
						} else {
							price = (price / 1000) % 10;
							if (price == 0 || price == 8) {
								if (that.detail.price + 2000 <= this.remaining) {
									that.value = that.detail.price + 2000;
									that.popupShow1 = true;
								} else {
									uni.showToast({
										title: '出价额度不足,请先增加出价额度后出价',
										icon: 'none',
										duration: 2000
									});
								}
							} else {
								if (that.detail.price + 3000 <= this.remaining) {
									that.value = that.detail.price + 3000;
									that.popupShow1 = true;
								} else {
									uni.showToast({
										title: '出价额度不足,请先增加出价额度后出价',
										icon: 'none',
										duration: 2000
									});
								}
							}
						}

					}
				}
			},

			getPrice(index) {
				var price;

				if (index == 1) {
					//加法
					if (this.value < 100000) {
						price = (this.value / 1000) % 10;
						if (price == 0 || price == 8) {
							if (this.value + 2000 <= this.remaining) {
								this.value = this.value + 2000;
							} else {
								uni.showToast({
									title: '出价额度不足,请先增加出价额度后出价',
									icon: 'none',
									duration: 2000
								});
							}

						} else {
							if (this.value + 3000 <= this.remaining) {
								this.value = this.value + 3000;
							} else {
								uni.showToast({
									title: '出价额度不足,请先增加出价额度后出价',
									icon: 'none',
									duration: 2000
								});
							}
						}
					} else {
						if (this.cate.bidding_ladder == 0) {
							price = (this.value / 10000) % 10;
							if (price == 0 || price == 8) {
								if (this.value + 20000 <= this.remaining) {
									this.value = this.value + 20000;
								} else {
									uni.showToast({
										title: '出价额度不足,请先增加出价额度后出价',
										icon: 'none',
										duration: 2000
									});
								}
							} else {
								if (this.value + 30000 <= this.remaining) {
									this.value = this.value + 30000;
								} else {
									uni.showToast({
										title: '出价额度不足,请先增加出价额度后出价',
										icon: 'none',
										duration: 2000
									});
								}
							}
						} else {
							price = (this.value / 1000) % 10;
							if (price == 0 || price == 8) {
								this.value = this.value + 2000;
							} else {
								this.value = this.value + 3000;
							}
						}
					}
				} else {
					//减法
					if (this.value <= 100000) {
						price = (this.value / 1000) % 10;
						if (price == 0 || price == 2) {
							if (this.value - 2000 > this.detail.price && this.value - 2000 > this.detail.ot_price) {
								this.value = this.value - 2000;
							} else {
								uni.showToast({
									title: '出价不能低于起拍价或当前价',
									icon: 'none',
									duration: 2000
								});
							}

						} else {

							if (this.value - 3000 > this.detail.price && this.value - 3000 > this.detail.ot_price) {
								this.value = this.value - 3000;
							} else {
								uni.showToast({
									title: '出价不能低于起拍价或当前价',
									icon: 'none',
									duration: 2000
								});
							}
						}
					} else {
						if (this.cate.bidding_ladder == 0) {
							price = (this.value / 10000) % 10;
							if (price == 0 || price == 2) {
								if (this.value - 20000 > this.detail.price && this.value - 20000 > this.detail.ot_price) {
									this.value = this.value - 20000;
								} else {
									uni.showToast({
										title: '出价不能低于起拍价或当前价',
										icon: 'none',
										duration: 2000
									});
								}

							} else {

								if (this.value - 30000 > this.detail.price && this.value - 30000 > this.detail.ot_price) {
									this.value = this.value - 30000;
								} else {
									uni.showToast({
										title: '出价不能低于起拍价或当前价',
										icon: 'none',
										duration: 2000
									});
								}
							}
						} else {
							price = (this.value / 1000) % 10;
							if (price == 0 || price == 2) {
								if (this.value - 2000 > this.detail.price && this.value - 2000 > this.detail.ot_price) {
									this.value = this.value - 2000;
								} else {
									uni.showToast({
										title: '出价不能低于起拍价或当前价',
										icon: 'none',
										duration: 2000
									});
								}

							} else {

								if (this.value - 3000 > this.detail.price && this.value - 3000 > this.detail.ot_price) {
									this.value = this.value - 3000;
								} else {
									uni.showToast({
										title: '出价不能低于起拍价或当前价',
										icon: 'none',
										duration: 2000
									});
								}
							}
						}
					}
				}
			},

			postagent(index) {
				if (index == 1) {
					const that = this;
					if (that.value > that.remaining) {
						uni.showToast({
							title: "您的竞价额度不足",
							icon: 'none',
							duration: 2000
						});
						return;
					}

					uni.showModal({
						title: "提示",
						content: "你的代理出价金额 RMB" + that.value,
						success(res) {
							if (res.confirm) {
								getAgentPrices(that.id, that.value, that.plate, 1).then((res) => {
									uni.showToast({
										title: '代理出价成功',
										icon: 'none',
										duration: 2000
									});
									that.getagentPrice();
									that.getPlateNumberdetails(0);
								})
							}
						}
					})
				}


				this.popupShow1 = false;
			},


			getagentPrice() {
				const that = this;
				agentPriceDetail(that.id).then((res) => {
					console.log(res.data.agent_price);
					if (res.data.agent_price && res.data.status != 0) {

						that.agent_price = res.data.agent_price;

					} else {
						that.agent_price = "";

					}
				})
			},


			getcart() {
				let that = this;
				let q = {
					productId: that.id,
					cartNum: 1,
					new: 1,
					uniqueId: '',
					virtual_type: that.detail.virtual_type
				};
				const news = 1;
				postCartAdd(q)
					.then((res) => {

						if (news) {
							let url = '/pages/goods/order_confirm/index?new=1&cartId=' + res.data.cartId;
							uni.navigateTo({
								url
							});
						} else {
							that.$util.Tips({
								title: that.$t(`添加成功`),
								success: () => {
									that.getCartCount(true);
								}
							});
						}
						this.isGiftOrder = 0;
					})
					.catch((err) => {
						that.isOpen = false;
						return that.$util.Tips({
							title: err
						});
					});
			},

			cancelAgent() {
				const that = this;
				//this.popupShow2 = false;
				cancelAgentPrices(that.id).then((res) => {
					uni.showToast({
						title: '代理出价已取消',
						icon: 'none',
						duration: 2000
					});
					that.agent_price = '';
				})
			},
		}
	}
</script>

<style>
	page {
		font-family: '仿宋';
	}


	.w-full {
		width: 100%;
	}

	.p-20-10-10 {
		padding: 20px 10px 10px 10px;
	}

	.productTitle {
		font-size: 20px;
	}

	.p-10 {
		padding: 10px;
	}

	.bold {
		font-weight: bold;
	}

	.flex {
		display: flex;
	}

	.h-full {
		height: 100%;
	}

	.justify-between {
		justify-content: space-between;
	}

	.warp {
		flex-wrap: wrap;
	}

	.bg {
		background-color: #ffffff;
	}

	.p-0-10 {
		padding: 0 10px;
	}

	.text-a71f24 {
		color: #a71f24;
	}

	.introduction {
		padding: 20px 10px;
		margin-top: 10px;
		color: #000000;
	}

	.mt-5 {
		margin-top: 5px;
	}

	.half {
		width: 50%;
	}

	.p-10-0 {
		padding: 10px 0;
	}

	.w-15 {
		width: 15%;
	}

	.mt-20 {
		margin-top: 20px;
	}

	.pb-20 {
		padding-bottom: 20px;
	}

	.biddingBox {
		background-color: #fff;
		margin-top: 15px;
		padding: 20px 10px;
	}

	.lead {
		background-color: #a71f24;
		color: #fff;
		padding: 10px 15px;
	}

	.eliminate {
		background-color: #e9e9e9;
		color: #999;
		padding: 10px 15px;
	}

	.bottom {
		position: fixed;
		bottom: 0;
		border-top: 1px solid #e9e9e9;
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

	.lineH-40 {
		line-height: 40px;
	}

	.center {
		text-align: center;
	}

	.productIntroduction {
		padding: 20px 10px 130px;
		margin-top: 10px;
		color: #000000;
	}

	.btn {
		width: 48%;
		background-color: #783c17;
	}

	.w-48 {
		width: 48%;
	}

	.bg-e {
		background-color: #eeeeee;
	}

	.setAmountb {
		font-size: 25px;
		font-weight: bold;
		text-align: center;
		width: 100%;
	}

	.amountb {
		text-align: center;
		width: 170px;
		background-color: #a71f24;
		margin: 0 10px;
		color: #fff;
		border-radius: 5px;
	}

	.btns {
		width: 45px;
		text-align: center;
		line-height: 50px;
		border-radius: 5px;
		border: 1px solid #e0e0e0;
		font-size: 20px;
		font-weight: bold;
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
</style>
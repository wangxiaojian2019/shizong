<template>
	<view v-if="load">
		<u-navbar title=" ">
			<view class="slot-wrap bold"> {{cate.cate_name}} </view>
		</u-navbar>
		<view class="w-full flex" style="background-color:#c17e48">
			<view style="height: 250px;" :style="'width:' + width + 'px'">
				<image :src="detail.slider_image[1]" style="height: 100%;" mode="heightFix"></image>
			</view>
			<view style="padding: 20px;color: #fff;">
				<view style="font-size: 16px;text-align: center;font-weight: bold;">{{detail.store_name}}</view>
				<view style="margin-top: 10px;font-size: 10px;line-height: 14px;">
					{{detail.store_info}}
				</view>
			</view>
		</view>
		<view class="flex p-20-8">
			<view style="letter-spacing: 1px;">
				<text class="bold">第{{cate.number}}件</text><text style="color: #333;"
					class="bold">/本场共{{cate.count}}件</text>
			</view>
			<view style="border: 1px solid #333;border-radius: 10px;padding: 0 6px;margin-left: 8px;" class="bold"
				@click="productListShow(1)">
				查看全部拍品</view>
		</view>

		<view class="flex justify-between" style="padding: 0 10px;">
			<view class="w-38">
				<image :src="detail.slider_image[0]" class="w-full" mode="widthFix"></image>
			</view>
			<view class="w-58 relative">
				<view class="bold">
					Lot{{index}} {{detail.store_name}}
				</view>
				<view class="bottom">
					<view v-if="detail.price==0" class="bold">起拍价 RMB<text
							class="text-a71f24">{{detail.ot_price}}</text></view>
					<view v-else class="bold">当前价 RMB <text class="text-a71f24"
							style="margin-left: 5px;">{{detail.price}}</text></view>


					<view class="mt-8 bold" v-if="agentPrices">我的代理金额: RMB <text class="text-a71f24"
							style="margin-left: 5px;">{{agentPrices}}</text></view>
					<view class="flex justify-between w-full mt-8" v-if="detail.uid != 0 && bidding[0].plate">
						<view class="flex justify-between">
							<view
								style="background-color: #a71f24;color: #ffffff;font-size: 12px;border-radius: 5px;line-height: 20px;padding: 0 8px;">
								领先</view>
							<view v-if="detail.uid == cate.sceneId" class="bold"
								style="margin-left: 8px;line-height: 20px;">现场</view>
							<view v-else class="bold" style="margin-left: 8px;line-height: 20px;">网络
								{{bidding[0].plate}}
							</view>
						</view>
					</view>
				</view>
			</view>
		</view>
		
		<view style="width: 100%;height: 150px;">
			
		</view>

		<!--<u-popup v-model="show" mode="right" z-index="99" width="300px">
			<view :style="'height:' + height + 'px'"></view>
			<scroll-view :style="'height:' + productListH + 'px'" scroll-y :scroll-into-view="scrollIntoId">
				<view class="p-10">
					<view v-for="item in productList" class="flex justify-between p-10 mt-8"
						:class="cate.status == 1 && cate.number == item.index ? 'active':''" :id="'item-'+item.id">
						<view class="w-38">
							<image :src="item.image" class="w-full" mode="widthFix"></image>
						</view>
						<view class="w-58 relative">
							<view class="bold">Lot{{item.index}} {{item.store_name}}</view>
							<view class="bottom">
								<view v-if="item.price == 0 && cate.status == 0">起拍价: {{item.ot_price}}</view>
								<view v-else-if="item.price != 0 && cate.status == 0">当前价: {{item.price}}</view>
								<view v-else-if=" cate.status == 1 && cate.number == item.index" class="auctioned">正在竞拍
								</view>
								<view v-else-if="item.price == 0 && cate.status == 1 && cate.number < item.index">起拍价:
									{{item.ot_price}}
								</view>
								<view v-else-if="item.price != 0 && cate.status == 1  && cate.number > item.index">落槌价:
									{{item.price}}
								</view>
								<view v-else>落槌价: {{item.price}}</view>


							</view>
						</view>
					</view>
				</view>

			</scroll-view>

		</u-popup>-->

		<view style="position: fixed;right: 0;bottom: 0;width: 100%;z-index: 99;background-color: rgb(0, 0, 0,0.3)"
			:style="'height:' + productListH + 'px'" v-if="show">
			<view class="flex">
				<view class="height: 100%" :style="'width:' + bgW + 'px;height:' + productListH + 'px' " @click="productListShow(0)">

				</view>
				<view style="height: 100%;background-color: #fff;width: 300px;z-index: 100;position: absolute;" :class="show==true ? 'mymove1 right-0':'mymove2 right-300'" >
					<scroll-view :style="'height:' + productListH + 'px'" scroll-y :scroll-into-view="scrollIntoId">
						<view class="p-10">
							<view v-for="item in productList" class="flex justify-between p-10 mt-8"
								:class="cate.status == 1 && cate.number == item.index ? 'active':''"
								:id="'item-'+item.id" @click="todetails(item.id)">
								<view class="w-38">
									<image :src="item.image" class="w-full" mode="widthFix"></image>
								</view>
								<view class="w-58 relative">
									<view class="bold">Lot{{item.index}} {{item.store_name}}</view>
									<view class="bottom">
										<view v-if="item.price == 0 && cate.status == 0">起拍价: {{item.ot_price}}</view>
										<view v-else-if="item.price != 0 && cate.status == 0">当前价: {{item.price}}</view>
										<view v-else-if=" cate.status == 1 && cate.number == item.index"
											class="auctioned">正在竞拍
										</view>
										<view
											v-else-if="item.price == 0 && cate.status == 1 && cate.number < item.index">
											起拍价:
											{{item.ot_price}}
										</view>
										<view
											v-else-if="item.price != 0 && cate.status == 1  && cate.number > item.index">
											当前价:
											{{item.price}}
										</view>
										<view v-else>落槌价: {{item.price}}</view>


									</view>
								</view>
							</view>
						</view>

					</scroll-view>
				</view>
			</view>


		</view>

		<view class="bottomBox">
			<view class="p-20-8">
				<view class="flex justify-between" v-if="plate && state==1">
					<view>号牌: {{plate}}</view>
					<view v-if="remaining<1000000">剩余额度: {{remaining}}</view>
					<view v-else>剩余额度: 99999999</view>
				</view>
				<view class="w-full mt-8 " v-if="!plate">
					<view class="plateBtn">
						办理号牌
					</view>
				</view>
				<view class="w-full mt-8 " v-else-if="!state">
					<view class="plateBtn">
						缴纳保证金
					</view>
				</view>
				<view class="mt-8 " v-else-if="cate.status == 0">
					<view class="plateBtn bg-e" style="color: #333;">
						预展中
					</view>
				</view>

				<view v-else-if="cate.status == 1" class="w-full mt-8 ">
					<view class="flex justify-between btnBox">
						<view class="flex justify-between biddingBoX">
							<view class="w-20" @click="getPrice(0)">
								<view class="radius">
									<view class="middle">
										<image src="@/static/images/jian.png" class="w-full block" mode="widthFix">
										</image>
									</view>
								</view>
							</view>
							<view class="value">
								{{value}}
							</view>
							<view class="w-20" @click="getPrice(1)">
								<view class="radius">
									<view class="middle">
										<image src="@/static/images/jia.png" class="w-full block" mode="widthFix">
										</image>
									</view>
								</view>
							</view>
						</view>
						<view class="bg-a71f24 bidding" v-if="model == 1" @click="addPrices(1)">
							直接竞价
						</view>
						<view class="bg-a71f24 bidding" v-if="model == 0" @click="pay(1)">
							提升额度
						</view>
						<view class="bg-a71f24 bidding" v-if="model == 2" @click="postagent()">
							代理出价
						</view>
					</view>
				</view>
				<view class="mt-8 " v-else>
					<view class="plateBtn bg-e" style="color: #333;">
						已结拍
					</view>
				</view>

				<view class="flex justify-between mt-8" v-if="plate && state==1 && cate.status == 1">
					<view @click="changeModel(0)">增加额度</view>
					<view @click="changeModel(1)">直接出价</view>
					<view @click="changeModel(2)" v-if="!agentPrices">代理出价</view>
					<view v-else @click="cancelAgent()">取消代理出价</view>
				</view>
			</view>

		</view>

		<!--<u-popup v-model="show1" mode="center" :border-radius="10">
			<view style="font-size: 26px;text-align: center;padding-top: 15px;" class="bold">代理出价</view>
			<view class="flex justify-between" style="width: 300px;padding-bottom: 10px;margin-top: 15px;">
				<view class="w-20" @click="getPrice(0)">
					<view class="radius">
						<view class="middle">
							<image src="@/static/images/jian.png" class="w-full block" mode="widthFix">
							</image>
						</view>
					</view>
				</view>
				<view class="value">
					{{value}}
				</view>
				<view class="w-20" @click="getPrice(1)">
					<view class="radius">
						<view class="middle">
							<image src="@/static/images/jia.png" class="w-full block" mode="widthFix">
							</image>
						</view>
					</view>
				</view>
			</view>
			<view class="w-full flex" style="border-top: 1px solid #333;margin-top: 15px;">
				<view class="w-full" style="text-align: center;border-right: 1px solid #333;padding: 10px 0;">取消</view>
				<view class="w-full" style="text-align: center;;padding: 10px 0">确认</view>
			</view>
		</u-popup>-->
	</view>
	<view v-else style="width: 100%;height: 100vh;">
		<view style="position: absolute;top: 50%;left: 50%;transform: translate(-50%,-50%);">
			<u-loading mode="circle"></u-loading>
		</view>
	</view>
</template>

<script>
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
	import {
		handleSyncManualBidResult,
		normalizeBidMessage
	} from '@/utils/bidResult.js';
import { vShow } from "vue";
	export default {
		data() {
			return {
				titleWidth: 350,
				titleHeight: 20,
				//cate_name: "剑未配妥，出门已是江湖",
				cate: "",
				cate_id: 0,
				id: 0,
				productList: "",
				detail: "",
				show: false,
				height: 0,
				productListH: 0,
				index: 0,
				value: 0,
				plate: "",
				state: 0,
				remaining: 0,
				freeze: 0,
				userInfo: "",
				uid: 0,
				show1: true,
				agent: 0,
				model: 1,
				agentPrices: "",
				bidding_ladder: 0,
				load: 0,
				bidding: "",
				openId: "",
				timer: null,
				scrollIntoId: "",
				bgW: 0,
				width: 0
			}
		},
		onLoad(options) {
			this.cate_id = options.cid;
			const menuButtonInfo = uni.getMenuButtonBoundingClientRect();
			const system = uni.getSystemInfoSync();
			this.height = menuButtonInfo.bottom + 10;
			this.productListH = system.windowHeight - this.height;
			this.getUserInfo();
			this.bgW = system.windowWidth - 300;
			//console.log(this.height);
			this.downTime();
			this.width = 3526*250/5290;
			//console.log(this.width);
		},

		onShow() {
			this.getDetails();
			this.getPlateNumberdetails(0);
			
			
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
		methods: {
			getview(id) {
				addView(id).then((res) => {}).catch((err) => {
					console.error("addView 调用失败:", err);
				});
			},
			
			handleMessage(data) {
				const that = this;
				console.log(data);
				// if (data['type'] == "product1") {
				// 	that.cate = data['data'][0];
				// 	that.id = that.productList[that.cate.number - 1].id;
				// 	this.sendMessage({
				// 		type: 'product1',
				// 		data: {
				// 			id: that.id
				// 		}
				// 	});
				// 	that.productDetails();
				// 	that.getagentPrice();
				// 	for (let i = 0; i < that.productList.length; i++) {
				// 		that.productList[i].index = i + 1;
				// 		if (that.productList[i].id == that.id) {
				// 			that.index = i + 1;


				// 		}
				// 	}
				// } else if (data['type'] == "product2" && data['data'][0].id == that.id) {
				// 	that.detail = data['data'][0];
				// } else if (data['type'] == "product3") {

				// }
				if (data['type'] == "product11") {

					that.cate = data['data'][0].category;
					//that.productList = data['data'][0].product;
					if (that.id != that.productList[that.cate.number - 1].id) {
						that.productList = data['data'][0].product;
						that.id = that.productList[that.cate.number - 1].id;
						that.bidding_ladder = 0;
						uni.showModal({
							title: "提示",
							content: "新的拍品已上架",
						})
						this.getagentPrice();
						this.productDetails();
						this.scrollIntoId = "item-" + that.id;
						that.model = 0;
						this.sendData({
							type: 'product2',
							data: {
								id: that.id
							}
						});
						
						for (let i = 0; i < that.productList.length; i++) {
							that.productList[i].index = i + 1;
							if (that.productList[i].id == that.id) {
								that.index = i + 1;
						
						
							}
						}
					}



					if (that.bidding_ladder != that.cate.bidding_ladder) {
						that.bidding_ladder = that.cate.bidding_ladder;
						uni.showModal({
							title: "提示",
							content: "当前拍品加价幅度已更新",
						})
					}



					
				} else if (data['type'] == "product21" && data['data'][0]['id'] == that.id) {
					that.detail = data['data'][0];
					if(that.detail.price >= that.value && that.model != 1){
						var price = that.detail.price;
						var number;
						if (price == 0 && that.detail.ot_price != 0) {
							that.value = that.detail.ot_price;
						} else if (price == 0 && that.detail.ot_price == 0) {
							that.value = 2000;
						} else {
							if (price < 100000) {
								price = (price / 1000) % 10;
								//console.log(price);
								if (price == 0 || price == 8) {
									that.value = that.detail.price + 2000;
								} else {
									that.value = that.detail.price + 3000;
								}
							} else {
								if (that.cate.bidding_ladder == 0) {
									price = (price / 10000) % 10;
									if (price == 0 || price == 8) {
										that.value = that.detail.price + 20000;
									} else {
										that.value = that.detail.price + 30000;
									}
								} else {
									price = (price / 1000) % 10;
									if (price == 0 || price == 8) {
										that.value = that.detail.price + 2000;
									} else {
										that.value = that.detail.price + 3000;
									}
								}
						
							}
						}
					}
					
				} else if (data['type'] == "product31" && data['data'][0][0]) {
					//console.log( data['data'][0]);
					if(data['data'][0][0]['product_id']== that.id){
						that.bidding = data['data'][0];
					}
					
				}

			},

			downTime() {
				const that = this;
				this.timer = setInterval(() => {
					this.getPlateNumberdetails(0);
				}, 30000)

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
							console.log("发送成功,hahaha");
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

				});
			},

			getDetails() {
				const that = this;

				synchronizationlist(that.cate_id).then((res) => {
					that.cate = res.data.category;
					that.productList = res.data.product;
					that.id = that.productList[that.cate.number - 1].id;
					that.scrollIntoId = "item-" + that.id;
					that.getagentPrice();
					getApp().globalData.type = {
						type: 'product2',
						data: {
							id: that.id
						}
					};

					that.sendData({
						type: 'product2',
						data: {
							id: that.id
						}
					});

					that.sendMessage({
						type: 'product2',
						data: {
							id: that.id
						}
					});
					that.productDetails();

					for (let i = 0; i < that.productList.length; i++) {
						that.productList[i].index = i + 1;
						if (that.productList[i].id == that.id) {
							that.index = i + 1;


						}
					}
				})
			},

			todetails(idx) {
				//console.log(idx);
				//	console.log(this.id);
				uni.navigateTo({
					url: "/pages/goods_cate/synchronous_product_details?id=" + idx + "&cate_id=" + this.cate_id
				})
			},

			productDetails() {
				const that = this;

				getproductDetails(that.id).then((res) => {
					that.detail = res.data;
					var price = res.data.price;
					that.biddingList(that.id);
					that.load = 1;
					var number;
					if (price == 0 && res.data.ot_price != 0) {
						that.value = res.data.ot_price;
					} else if (price == 0 && res.data.ot_price == 0) {
						that.value = 2000;
					} else {
						if (price < 100000) {
							price = (price / 1000) % 10;
							//console.log(price);
							if (price == 0 || price == 8) {
								that.value = res.data.price + 2000;
							} else {
								that.value = res.data.price + 3000;
							}
						} else {
							if (that.cate.bidding_ladder == 0) {
								price = (price / 10000) % 10;
								if (price == 0 || price == 8) {
									that.value = res.data.price + 20000;
								} else {
									that.value = res.data.price + 30000;
								}
							} else {
								price = (price / 1000) % 10;
								if (price == 0 || price == 8) {
									that.value = res.data.price + 2000;
								} else {
									that.value = res.data.price + 3000;
								}
							}

						}
					}
				})
			},

			previewImage(url) {
				uni.previewImage({
					current: url, // 当前显示图片的链接
					urls: [url] // 需要预览的图片列表（支持多张）
				});
			},

			productListShow(index) {
				//console.log(Show);
				if (index == 1) {
					this.show = true;
				} else {
					setTimeout(() => {
						// 这里放置需要延时执行的代码
						this.show = false;
					}, 1000); 
					
				}

			},

			biddingList() {
				//console.log("asss");
				const that = this;
				getbiddingList(that.id).then((res) => {
					//console.log(res.data);
					that.bidding = res.data;
				})
			},

			getPrice(index) {
				var price;
				if (this.model != 0) {
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
									if (this.value - 20000 > this.detail.price && this.value - 20000 > this.detail
										.ot_price) {
										this.value = this.value - 20000;
									} else {
										uni.showToast({
											title: '出价不能低于起拍价或当前价',
											icon: 'none',
											duration: 2000
										});
									}

								} else {

									if (this.value - 30000 > this.detail.price && this.value - 30000 > this.detail
										.ot_price) {
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
									if (this.value - 2000 > this.detail.price && this.value - 2000 > this.detail
										.ot_price) {
										this.value = this.value - 2000;
									} else {
										uni.showToast({
											title: '出价不能低于起拍价或当前价',
											icon: 'none',
											duration: 2000
										});
									}

								} else {

									if (this.value - 3000 > this.detail.price && this.value - 3000 > this.detail
										.ot_price) {
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
				} else {
					if (index == 0 && this.value > 10000) {
						this.value = this.value - 10000;
					} else if (index == 1) {
						this.value = this.value + 10000;
					}
				}


			},

			addQuota(index) {
				if (index == 0 && this.value > 10000) {
					this.value = this.value - 10000;
				} else if (index == 1) {
					this.value = this.value + 10000;
				}
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


			addPrices(index) {
				const that = this;
				if (index == 1) {
					//this.paystatus = 1;
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
						content: "你的直接出价金额 RMB" + that.value,
						success(res) {
							if (res.confirm) {
								addSynchronousPrice(that.id, that.plate, that.value)
									.then((res) => {
										handleSyncManualBidResult(that, res.msg);
									})
									.catch((err) => {
										handleSyncManualBidResult(that, normalizeBidMessage(err));
									});
							}
						}
					})

				}

			},

			postagent() {

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
								that.model = 1;
								that.getagentPrice();
								that.getPlateNumberdetails(0);
								that.productDetails();
							})
						}
					}
				})


			},


			getagentPrice() {
				const that = this;
				agentPriceDetail(that.id).then((res) => {
					//console.log(res.data);
					if (res.data.agent_price && res.data.status != 0) {

						that.agentPrices = res.data.agent_price;

					} else {
						that.agentPrices = "";

					}
				})
			},

			changeModel(index) {
				//this.model = index;
				const that = this;
				if (index != 0) {

					var price = that.detail.price;
					var number;
					if (price == 0 && that.detail.ot_price != 0) {
						that.value = that.detail.ot_price;
					} else if (price == 0 && that.detail.ot_price == 0) {
						that.value = 2000;
					} else {
						if (price < 100000) {
							price = (that.detail.price / 1000) % 10;
							if (price == 0 || price == 8) {
								that.value = that.detail.price + 2000;
							} else {
								that.value = that.detail.price + 3000;
							}
						} else {
							if (that.cate.bidding_ladder == 0) {
								price = (that.detail.price / 10000) % 10;
								if (price == 0 || price == 8) {
									that.value = that.detail.price + 20000;
								} else {
									that.value = that.detail.price + 30000;
								}
							} else {
								price = (that.detail.price / 1000) % 10;
								if (price == 0 || price == 8) {
									that.value = that.detail.price + 2000;
								} else {
									that.value = that.detail.price + 3000;
								}
							}
						}
					}
					this.model = index;
				} else {

					if (this.remaining > 1000000) {
						uni.showToast({
							title: "您已缴纳专场保证金10w,无需在提升额度",
							icon: 'none',
							duration: 2000
						});
						return;
					} else {
						this.model = index;
						that.value = 10000;
					}
				}
			},

			cancelAgent() {
				const that = this;

				cancelAgentPrices(that.id).then((res) => {
					uni.showToast({
						title: '代理出价已取消',
						icon: 'none',
						duration: 2000
					});
					that.agentPrices = '';
					that.getPlateNumberdetails(0);
				})
			},

			

			pay(total_fee) {

				this.show = false;
				if (total_fee == 0) {
					this.popupShow = false;
				} else {
					const that = this;
					if (that.value != 0) {
						uni.showModal({
							title: "提示",
							content: "是否支付" + that.value + "元保证金，获得" + that.value * 5 + "的竞拍额度",
							success(res) {
								if (res.confirm) {
									getwxpay(that.openId, that.cate_id, that.value).then((res) => {
										that.wxpay(res.data);
									})
								}
							}
						})
					}
					//console.log(that.openId);

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

						uni.showToast({
							title: '支付成功'
						});

						setTimeout(() => {
							// 这里放置需要延时执行的代码
							that.getPlateNumberdetails(0);
						}, 2000); // 2000毫秒 = 2秒
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

		}

	}
</script>

<style>
	page {
		font-family: '仿宋';
	}

	.slot-wrap {

		margin: 0 auto;
		width: 200px;
		text-align: center;
		overflow: hidden;
		text-overflow: ellipsis;
		/* 当文本溢出时，显示省略号 */
		white-space: nowrap;
		/* 禁止文本换行 */
	}

	.w-full {
		width: 100%;
	}

	.bold {
		font-weight: bold;
	}

	.flex {
		display: flex;
	}

	.justify-between {
		justify-content: space-between;
	}

	.p-20-8 {
		padding: 20px 10px;
	}

	.p-10 {
		padding: 10px 8px;
	}

	.w-38 {
		width: 38%;
	}

	.w-58 {
		width: 58%;
	}

	.active {
		border: 2px solid #a71f24;
		border-radius: 10px;
	}

	.mt-8 {
		margin-top: 8px;
	}

	.relative {
		position: relative;
	}

	.bottom {
		position: absolute;
		bottom: 5px;
	}

	.auctioned {
		padding: 2px 15px;
		background-color: #a71f24;
		color: #fff;
		border-radius: 5px;
	}

	.text-a71f24 {
		color: #a71f24;
	}

	.font-20 {
		font-size: 24px;
	}

	.bg-e {
		background-color: #eeeeee !important;
	}

	.bg-a71f24 {
		background-color: #a71f24;
	}

	.radius {
		width: 40px;
		height: 40px;
		border-radius: 100%;
		background-color: #414141;
		margin: 0 auto;
		position: relative;
	}

	.middle {
		position: absolute;
		top: 50%;
		left: 50%;
		transform: translate(-50%, -50%);
		width: 20px;
	}

	.block {
		display: block;
	}

	.btnBox {
		border-radius: 8px;
		overflow: hidden;
	}

	.biddingBoX {
		background-color: #000;
		color: #fff;
		width: 75%;
		padding: 10px 0;
	}

	.w-20 {
		width: 20%
	}

	.value {
		font-size: 28px;
		line-height: 40px;
	}

	.bidding {
		width: 25%;
		text-align: center;
		color: #fff;
		line-height: 40px;
		padding: 10px 0;
	}

	.plateBtn {
		background-color: #a71f24;
		line-height: 40px;
		text-align: center;
		color: #ffffff;
		border-radius: 10rpx;
	}

	.bottomBox {
		position: fixed;
		bottom: 0;
		background-color: #ffffff;
		left: 0;
		right: 0;
	}
	
	.mymove1{
		animation:mymove 1s linear;
	}
	
	.mymove2{
		animation:mymoves 1s linear;
	}
	
	.right-0{
		right: 0;
	}

	.right-300{
		right: -300px;
	}

	@keyframes mymove {
		from {
			right: -300px;
		}

		to {
			right: 0px;
		}
	}
	
	@keyframes mymoves {
		from {
			right: 0px;
		}
	
		to {
			right: -300px;
		}
	}
</style>
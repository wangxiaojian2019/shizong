<template>
	<view class="w-full h-fullscreen">
		<view v-if="!load">
			<view class="content">
				<view class="w-full flex justify-between top" style="font-size: 14px;">
					<view class="flex">
						<view class="preview" v-if="details.category.status== 0">同步拍·预展中</view>
						<view class="preview" v-else-if="details.category.status== 1">同步拍·拍卖中</view>
						<view class="preview" v-else>同步拍·已结束</view>
						<view class="ml-15" v-if="details.category.status== 0 && details.category.startTime > nows">
							距开拍{{downTime1}}</view>
					</view>
					<view>

					</view>
				</view>

				<view class="cate_name w-full">
					{{details.category.cate_name}}
				</view>

				<view class="w-full flex justify-between mt-15">
					<view style="width: 38%;">
						<image :src="details.category.big_pic" class="w-full" mode="widthFix"
							@click="previewImage(details.category.big_pic)"></image>
					</view>
					<view style="width: 58%;" class="bold">
						<view class="lineH">拍卖时间:{{details.category.startTime1}}</view>
						<view class="lineH">专场保证金:{{margin}}</view>
						<view class="lineH">佣金:{{commission}}</view>
						<view class="lineH">参拍须知</view>
						<view class="lineH flex">
							<view>围观:{{details.category.view}}次</view>
							<view class="ml-15 ">出价:{{bid}}次</view>
						</view>
					</view>
				</view>
			</view>
			<view class="mt-15 bg">
				<view class="flex justify-between w-full"
					style="border-bottom: 1px solid #e9e9e9;padding: 20px 10px 0 ;">
					<view class="flex" style="line-height: 60px;font-size: 18px;">
						<view :class="active==0 ? 'active':'' " @click="changeActive(0)">拍品({{details.product.length}})
						</view>
						<view class="ml-20" :class="active==1 ? 'active':'' " @click="getAuctionDetails()">拍卖详情</view>
					</view>
				</view>

				<view style="background-color: #fff;padding: 20px 10px 0 10px;" v-if="active==0">
					<view class="w-full">
						<scroll-view scroll-y :style="'height:' + scroolH + 'px'">
							<view v-for="item in details.product" class="w-full flex justify-between mt-25"
								@click="todetails(item.id)" :id="'item-'+item.id">
								<view style="width: 30%;" :style="'height:' + imageH + 'px'">
									<image :src="item.image" class="w-full"
										style="height: 100%;object-fit: cover;border-radius: 10px;" mode="aspectFill">
									</image>
								</view>
								<view style="width: 66%;">
									<view class="bold">Lot {{item.index}} {{item.store_name}}</view>
									<view style="width: 250px;position: relative;" class="mt-8">
										<image :src="bg2" class="w-full" mode="widthFix"></image>
										<view style="position: absolute;top: 0;line-height: 24px;font-size: 12px;"
											class="w-full">
											<view class="w-full flex justify-between">
												<view style="width: 28%;text-align: center;color: #ffffff;"
													v-if="details.category.status== 0">预展中</view>
												<view style="width: 28%;text-align: center;color: #ffffff;"
													v-else-if="details.category.status== 1 && details.category.number == item.index">
													拍卖中</view>
												<view style="width: 28%;text-align: center;color: #ffffff;"
													v-else-if="details.category.status== 1 && details.category.number< item.index">
													即将开始</view>
												<view style="width: 28%;text-align: center;color: #ffffff;" v-else>已结拍
												</view>
												<view style="width: 68%;" v-if="details.category.status== 0">距开拍:
													{{downTime1}}
												</view>

											</view>
										</view>
									</view>
									<view class="w-full bold mt-8"
										v-if="item.price==0 && details.category.number <= item.index">起拍价
										{{item.ot_price}}
									</view>
									<view class="w-full bold mt-8"
										v-else-if="item.price!=0 && details.category.number <=item.index && details.category.status!=2">
										当前价
										{{item.price}}
									</view>
									<view class="w-full bold mt-8" v-else>落槌价 {{item.price}}</view>
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
					</view>
				</view>
				<view v-else style="padding: 20px 10px 150px 10px;">
					<rich-text :nodes="article.content"></rich-text>
				</view>
			</view>

			<view class="w-full bottomBox">

				<view class="w-full plate" v-if="plate">号牌{{plate}},<text v-if="state==0">请缴纳保证金</text><text
						v-else>保证金已缴纳</text></view>
				<view class="plateBox">
					<!--<view class="w-full plateBtn" v-if="!userInfo.uid" @click="getUserInfo">去登录</view>-->
					<view class="w-full plateBtn" style="background-color: #e5e5e5;" v-if="details.category.status== 2">
						已结拍
					</view>
					<view class="w-full plateBtn" v-else-if="plate && state==1" @click="toSpecial()">进入专场</view>
					<view class="w-full plateBtn" v-else-if="plate && state==0" @click="payShow()">缴纳保证金</view>
					<view class="w-full plateBtn" v-else @click="addNumberPlate">办理号牌</view>
				</view>
			</view>

		</view>
		<view v-else class="middle">
			<u-loading mode="circle"></u-loading>
		</view>
	</view>
</template>

<script>
	import {
		synchronizationlist,
		auctionDetails
	} from '@/api/store.js';
	export default {
		data() {
			return {
				nows: 0,
				id: 9,
				load: true,
				details: "",
				margin: "RMB 200,000或1:5",
				commission: "13.5%-15%",
				bid: 0,
				timer: null,
				downTime1: "",
				scroolH: 0,
				imageH: 0,
				active: 0,
				bg2: "https://mtpm.billionwz.com/statics/images/bg2.png",
				article: "",
				userInfo: "",
				popupShow1: false
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
		},
		onShow() {
			this.getsynchronizationlist();
		},

		methods: {
			/**
			 * 获取个人用户信息
			 */
			getUserInfo: function() {
				let that = this;
				getUserInfo().then((res) => {
					that.userInfo = res.data;
					that.$store.commit('SETUID', res.data.uid);
					//console.log(res.data);
					uni.stopPullDownRefresh();
					if (!this.userInfo.phone) {
						this.popupShow1 = true
					}

					//this.getUserInfo();
				});
			},

			/**
			 * 倒计时
			 */
			downTime() {
				const that = this;
				this.timer = setInterval(() => {
					var time = Date.now();
					var downtime = that.details.category.startTime * 1000 - time;
					that.nows = time;
					if (downtime == 0) {
						that.getDetails();
						clearInterval(this.timer)
						that.timer = null; // 重要：清除后重置引用
						that.downTime1 = `0天0时0分0秒` // 替换整个数组	
					} else if (downtime > 0) {
						const d = Math.floor(downtime / 86400000);
						const h = Math.floor((downtime % 86400000) / 3600000);
						const m = Math.floor((downtime % 3600000) / 60000);
						const s = Math.floor((downtime % 60000) / 1000);
						that.downTime1 = `${d}天${h}时${m}分${s}秒` // 替换整个数组		
					}



				}, 1000)
			},

			/**
			 * 获取专场信息
			 */
			getsynchronizationlist() {
				const that = this;
				synchronizationlist(that.id).then((res) => {
					//console.log(res);
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
					this.downTime();
				})
			},
			/**
			 * 切换内容
			 */
			changeActive(index) {
				this.active = index;
			},

			/**
			 * 拍场详情
			 */
			getAuctionDetails() {
				const that = this;
				console.log(that.details.category.article_id);
				if (that.article) {
					that.active = 1;
					return;
				}
				auctionDetails(that.details.category.article_id).then((res) => {
					that.article = res.data;
					that.active = 1;
					//console.log(res.data);
				})
			},

		}
	}
</script>

<style>
	page {
		/*background-color: #fff;*/
		font-family: '仿宋';
		color: #000000;
	}

	.content {
		padding: 20px 10px;
		background-color: #ffffff;
	}

	.w-full {
		width: 100%;
	}

	.flex {
		display: flex;
	}

	.justify-between {
		justify-content: space-between;
	}

	.top {
		font-size: 14px;
		letter-spacing: 1px;
		line-height: 30px;
		font-weight: bold;
	}

	.ml-15 {
		margin-left: 15rpx;
	}

	.ml-20 {
		margin-left: 20px;
	}

	.mt-15 {
		margin-top: 15px;
	}

	.mt-25 {
		margin-top: 25px;
	}

	.preview {
		background-color: #a71f24;
		color: #fff;
		padding: 0px 15px;
		border-radius: 30px;
		text-align: center;
	}

	.cate_name {
		margin-top: 20px;
		font-size: 30px;
		letter-spacing: 3px;
		font-weight: bold;
		line-height: 40px;
	}

	.lineH {
		line-height: 40px;
	}

	.active {
		border-bottom: 1px solid #000000;
		font-weight: bold;
	}

	.bg {
		background-color: #fff;
	}

	.bold {
		font-weight: bold;
	}

	.bottomBox {
		position: fixed;
		bottom: 0;
		font-size: 14px;
	}

	.plate {
		background-color: #e5e5e5;
		text-align: center;
		padding: 8px 0;
		border-top: 1px solid #e9e9e9;
	}

	.plateBox {
		border-top: 1px solid #e9e9e9;
		padding: 15px 10px;
		background-color: #ffffff;
	}

	.plateBtn {
		background-color: #a71f24;
		line-height: 40px;
		text-align: center;
		color: #ffffff;
		border-radius: 10rpx;
	}

	.mt-8 {
		margin-top: 8px;
	}

	.btn1 {
		background-color: #e5e5e5 !important;
		color: #000000 !important;
	}

	.text-indent {
		text-indent: 2em;
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

	.h-fullscreen {
		height: 100vh;
	}

	.middle {
		position: absolute;
		top: 50%;
		left: 50%;
		transform: translate(-50%, -50%);
	}
</style>
<template>
	<view class="w-full" style="height: 100vh;">
		<view v-if="!load">
			<view class="content">
				<view class="w-full flex justify-between top" style="font-size: 14px;">
					<view class="flex">
						<view class="preview" v-if="details.category.status== 0">限时拍·预展中</view>
						<view class="preview" v-else-if="details.category.status== 1">限时拍·拍卖中</view>
						<view class="preview" v-else>限时拍·已结束</view>
						<view class="ml-15" v-if="details.category.status== 0">距开拍{{downTime1}}</view>
					</view>
					<view @click="sendSubscriptionInformation()" :class=" scribeMessage==1 ? 'activeColor':''">
						提醒
					</view>
				</view>

				<view class="cate_name w-full">
					{{details.category.cate_name}}
				</view>
				<view v-if="showTimedSessionTip" class="session-tabs flex mt-10">
					<view v-for="(c, i) in parallelCates" :key="c.id"
						:class="['session-tab', id == c.id ? 'session-tab-active' : '']"
						@click="switchTimedCate(c)">
						第{{ i + 1 }}场
					</view>
				</view>
				<view v-if="showTimedSessionTip && timedSessionTip" class="timed-tip mt-10">{{ timedSessionTip }}</view>

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
					<!--<view class="flex justify-between" style="color: #000000;width: 90%;margin: 0 auto;">
						<view>图录号</view>
						<view>估价</view>
						<view>我的参拍</view>
					</view>-->
					<view class="w-full">
						<scroll-view scroll-y :style="'height:' + scroolH + 'px'" :scroll-into-view="scrollIntoId">
							<view v-for="(item,index) in details.product" :key="item.id" class="w-full flex justify-between mt-25"
								@click="todetails(item.id)" :id="'item-'+item.id" style="padding: 10px;border-radius: 10px;" :class="index == details.category.number-1 ? 'activeborder':''">
								<view style="width: 30%;" :style="'height:' + imageH + 'px'">
									<image :src="item.image" class="w-full"
										style="height: 100%;object-fit: cover;border-radius: 10px;" mode="aspectFill">
									</image>
								</view>
								<view style="width: 66%;">
									<view class="bold" :class="index == details.category.number-1 ? 'activeColor':''">Lot {{item.index}} {{item.store_name}}</view>
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
						v-else-if="state==2">退款审核中</text><text v-else>保证金已缴纳</text></view>
				<view class="plateBox">
					
					<view class="w-full plateBtn" style="background-color: #e5e5e5;" v-if="details.category.status== 2">
						已结拍
					</view>
					<view class="w-full plateBtn" style="background-color: #e5e5e5;" v-else-if="plate && state==2">退款审核中</view>
					<view class="w-full plateBtn" v-else-if="plate && state==1" @click="toSpecial()">进入专场</view>
					<view class="w-full plateBtn" v-else-if="plate && state==0" @click="payShow()">缴纳保证金</view>
					<view class="w-full plateBtn" v-else @click="addNumberPlate">办理号牌</view>
				</view>
			</view>

			<u-modal v-model="show" :content="content" :show-cancel-button="true" :confirm-text="confirmText"
				@confirm="confirm"></u-modal>

		</view>
		<view v-else style="position: absolute;top: 50%;left: 50%;transform: translate(-50%,-50%);">
			<u-loading mode="circle"></u-loading>
		</view>

		<u-popup v-model="popupShow" mode="center" width="90%" border-radius="10">
			<view style="height: 20px;"></view>
			<scroll-view scroll-y="true" style="height: 400px;width: 90%;margin: 0 auto;" class="bold">
				<view>北京明廷国际拍卖有限公司</view>
				<view class="mt-8">网络拍卖会 保证金规则</view>
				<view class="text-indent mt-8 ">为保证竞拍的公平性，参与竞拍需交纳一定金额的保证金作为参拍凭证，交纳保证金后可参与专场拍品的竞买，交纳保证金前请您仔细阅读《竞拍协议》。
				</view>
				<view class="mt-8">一、保证金说明：</view>
				<view class="text-indent mt-8 ">网络拍卖会专场保证金为{{deposit_amount}}元人民币（网络大拍为1万元人民币）。</view>
				<view class="mt-8 ">二、如何交纳保证金：</view>
				<view class="text-indent mt-8 ">1、进入专场或拍品详情页，点击交纳保证金（两种支付方式：微信支付+线下支付，方可参与竞拍）。</view>
				<view class="text-indent mt-8 ">•微信支付时（限制金额内可使用），点击立即支付</view>
				<view class="text-indent mt-8">页面跳转微信支付页面，支付完成后返回拍品详情，您即可参加竞投。</view>
				<view class="text-indent mt-8">
					•线下汇款时（遇到限额情况推荐使用），需打开手机银行或网银，按照公示的【北京明廷国际拍卖有限公司】/【北京明廷国际拍卖有限公司吴兴分公司】账号信息进行转账。</view>
				<view class="text-indent mt-8">
					转账汇款请备注：您的姓名、注册手机号，以及“交纳同步拍保证金”字样。当您转账完成后，返回小程序填写汇款告知单，提交后需等待明廷拍卖财务人员审核，会在审核成功后显示保证金交纳成功，您即可参加竞投。为了避免微信支付限额以及线下汇款转账时效性等不确定因素，请您提前至少一个工作日支付足额保证金，以保证您可以顺利竞投。
				</view>
				<view class="mt-8">三、注意事项：</view>
				<view class="text-indent mt-8">1、在交纳保证金前，请提前完成实名认证（在“我的”页面，找到“实名认证”上传证件完成认证）。</view>
				<view class="text-indent mt-8">2、请参与竞拍前提前交纳保证金，以免耽误竞拍。</view>
				<view class="mt-8">四、保证金退还的情形：</view>
				<view class="text-indent mt-8">1、未竞得拍品，在所有专场拍卖结束后三天内，保证金原路退还。</view>
				<view class="text-indent mt-8">、参拍并竞得拍品后，在规定时间内支付全额订单金额（包括竞拍成功的单笔或多笔订单）后，保证金原路退还；
					（注：保证金不参与抵扣）。</view>
				<view class="mt-8">五、保证金扣除的情形：</view>
				<view class="text-indent mt-8">竞拍成功，中拍者未在7日内支付订单金额，属于违约，平台将自动关闭交易并扣除专场保证金，请准时付款。</view>
				<view class="text-indent mt-8">1、在交纳保证金前，请提前完成实名认证（在“我的”页面，找到“实名认证”上传证件完成认证）。</view>
			</scroll-view>
			<view class="flex">
				<view class="bold p-10-0 center agentbtn1" @click="pay(0)">取消</view>
				<view class="bold p-10-0 center text-white agentbtn2" @click="pay(1)">确认</view>
			</view>
		</u-popup>

		<u-popup v-model="popupShow1" mode="center" width="80%" border-radius="10">
			<view style="padding: 20px;line-height: 20px;" class="bold center">
				你还未绑定手机号，绑定手机号能接收短信通知。
			</view>
			<view class="flex">
				<view class="bold p-10-0 center agentbtn1" @click="bangding(0)">取消</view>
				<view class="bold p-10-0 center text-white agentbtn2" @click="bangding(1)">去绑定</view>
			</view>
		</u-popup>
		
		<u-popup v-model="popupShow2" mode="bottom" width="80%" border-radius="10">
			<view style="padding: 20px 8px;">
				<view style="text-align: right;">
					<image src="@/static/images/close.png" style="width: 20px;" mode="widthFix" @click="noshoW()">
					</image>
				</view>
				<view style="width: 300px;margin: 0 auto;">
					<image src="https://mtpm.billionwz.com/statics/images/fwh.jpg" class="w-full" mode="widthFix" show-menu-by-longpress>
					</image>
				</view>
				<view style="text-align: center;color:  #a71f24;">
					长按扫码关注服务号
				</view>
			</view>
		</u-popup>
	</view>

</template>

<script>
	import {
		getUserInfo,
		SendSubscribeMessage,
		getScribeMessage
	} from '../../api/user';
	import {
		getCategorydDetails,
		getNumberPlate,
		getNumberdetails,
		auctionDetails,
		getwxpay,
	} from '@/api/store.js';
	import {
		applyTimedDepositFromCategoryRes,
		loadTimedDepositAmount,
		mergePlateDepositDetails
	} from '@/utils/timedDeposit.js';
	import {
		DEFAULT_TIMED_META,
		resolveTimedTipForDisplay,
		shouldShowTimedSessionTip,
	} from '@/utils/timedAuctionMeta.js';
	export default {
		computed: {
			showTimedSessionTip() {
				return shouldShowTimedSessionTip(this.parallelCates.length, this.timedMeta);
			},
			timedSessionTip() {
				if (!this.showTimedSessionTip) {
					return '';
				}
				return resolveTimedTipForDisplay(this.parallelCates.length, this.timedMeta.tip);
			},
		},
		data() {
			return {
				id: 2,
				details: [],
				downTime1: "",
				intervalId: null,
				margin: 1,
				deposit_amount: 1,
				need_pay: 1,
				commission: "10%",
				active: 0,
				bg2: "https://mtpm.billionwz.com/statics/images/bg2.png",
				plate: "",
				imageH: 0,
				load: true,
				type: 0,
				userInfo: "", //用户信息
				show: false,
				content: "请先完成实名认证",
				confirmText: "去实名",
				bid: 0,
				article: "",
				state: 0, //判断号牌是否启用
				cycleTime: 1000,
				socketTask: null,
				timer: null,
				time1: null,
				scrollIntoId: "",
				scroolH: 0,
				orderId: "",
				openId: "",
				jiazai: false,
				popupShow: false,
				popupShow1: false,
				popupShow2: false, // 默认不弹，由 checkQrCodeShow() 决定是否弹出
				scribeMessage: 0,
				parallelCates: [],
				timedMeta: Object.assign({}, DEFAULT_TIMED_META),
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
			this.getScribeMessages();
			this.checkQrCodeShow(); // 每日首次进入弹出二维码
		},
		onShow() {

			this.loadTimedDepositDisplay();
			this.getDetails();
			this.getPlateNumberdetails(0);
			getApp().globalData.type = {
				type: 'category',
				data: {
					id: this.id
				}
			};
			
			this.sendMessage({
				type: 'category',
				data: {
					id: this.id
				}
			});
			
			this.sendData({
				type: 'category',
				data: {
					id: this.id,
			
				}
			})
		},

		onHide() {
			clearInterval(this.time1);
			clearInterval(this.timer);
			this.timer = null;
			getApp().globalData.type = "";
			uni.$off('socketMessage', this.handleMessage);
			this.jiazai = false;
		},
		onUnload() {
			clearInterval(this.time1);
			clearInterval(this.timer);
			this.timer = null;
			getApp().globalData.type = "";
			uni.$off('socketMessage', this.handleMessage);
			this.jiazai = false;
		},
		
		onShareAppMessage() {
			const that = this;
			return {
				title: this.details.category.cate_name, // 分享标题
				path: '/pages/goods_cate/goods_cate1?id=' + this.id, // 分享的页面路径，通常携带一些参数
				imageUrl: this.details.category.big_pic// 可选，自定义分享图片
			};
		},
		
		
		onShareTimeline() {
			const that = this;
			return {
				title: this.details.category.cate_name, // 分享标题
				path: '/pages/goods_cate/goods_cate1?id=' + this.id, // 分享的页面路径，通常携带一些参数
				imageUrl: this.details.category.big_pic // 可选，自定义分享图片
			};
		},

		methods: {
			handleMessage(data) {
				//data = JSON.parse(data.data);

				console.log(data);
				//console.log(this.load);
				if (data['type'] == 'category') {
					const data1 = JSON.parse(data.data);
					//console.log(data1);
					//const data1 = data.data;
					const that = this;
					that.details = data1;
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



					//that.scrollIntoId = that.details.product[that.details.category.number-1].id;

					const date = new Date(that.details.category.startTime * 1000);
					//let year = date.getFullYear();
					let month = ('0' + (date.getMonth() + 1)).slice(-2); // 月份从0开始，需要+1
					let day = ('0' + date.getDate()).slice(-2);
					let hours = ('0' + date.getHours()).slice(-2);
					let minutes = ('0' + date.getMinutes()).slice(-2);
					//let seconds = ('0' + date.getSeconds()).slice(-2);

					that.details.category.startTime1 = `${month}月${day}日 ${hours}:${minutes}`;

					if (that.load == true) {
						that.load = false;
					}

					if (that.details.category.status == 2) {
						clearInterval(that.time1);
					}

					if (that.jiazai == false) {
						that.scrollIntoId = 'item-' + that.details.product[that.details.category.number - 1].id;
						that.downTime();
						that.jiazai = true;
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
							success: () => {console.log("发送成功")},
							fail: (err) => {
								console.error('消息发送失败', err);
							}
						});
					} else {
						console.warn('WebSocket 连接未建立，无法发送消息');
					}
				}, 30000)
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

			/**
			 * 获取个人用户信息
			 */
			getUserInfo: function() {
				let that = this;
				getUserInfo().then((res) => {
					that.userInfo = res.data;
					//that.$store.commit('SETUID', res.data.uid);
					//console.log(res.data);
					//uni.stopPullDownRefresh();
					if (!this.userInfo.phone) {
						this.popupShow1 = true
					}
					
					//this.getUserInfo();
				});
			},


			confirm() {
				uni.navigateTo({
					url: "/pages/realName/index"
				})
			},

			downTime() {
				const that = this;
				this.timer = setInterval(() => {
					var time = Date.now();
					var downtime = that.details.category.startTime * 1000 - time;
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

			getAuctionDetails() {
				const that = this;
				if (!that.details || !that.details.category) {
					uni.showToast({ title: '专场信息加载中', icon: 'none' });
					return;
				}
				if (that.article && that.article.content) {
					that.active = 1;
					return;
				}
				if (that.details.article && that.details.article.content) {
					that.article = that.details.article;
					that.active = 1;
					return;
				}
				const articleId = Number(that.details.category.article_id || 0);
				if (!articleId) {
					uni.showToast({ title: '暂未配置拍卖详情', icon: 'none' });
					return;
				}
				auctionDetails(articleId).then((res) => {
					if (res.data) {
						that.article = res.data;
						that.active = 1;
					}
				}).catch((err) => {
					uni.showToast({
						title: (err && err.msg) ? err.msg : '加载拍卖详情失败',
						icon: 'none',
					});
				});
			},

			loadTimedDepositDisplay() {
				return loadTimedDepositAmount(this, this.id);
			},

			switchTimedCate(c) {
				if (!c || c.id == this.id) {
					return;
				}
				uni.redirectTo({
					url: '/pages/goods_cate/goods_cate1?id=' + c.id
				});
			},

			getDetails() {
				const that = this;
				that.parallelCates = [];
				that.timedMeta = Object.assign({}, DEFAULT_TIMED_META, { tip: '' });

				var bid1 = 0;
				getCategorydDetails(that.id).then((res) => {
					//console.log(res.data.category);
					//console.log(res.data.product);
					
					that.details = res.data;
					that.parallelCates = Array.isArray(res.data.parallel_cates) ? res.data.parallel_cates : [];
					that.timedMeta = Object.assign({}, DEFAULT_TIMED_META, res.data.timed_meta || {});
					if (res.data && res.data.article && res.data.article.content) {
						that.article = res.data.article;
					}
					applyTimedDepositFromCategoryRes(that, res.data);
					
					//console.log(that.details);
					//console.log(that.details.product);
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

					const date = new Date(that.details.category.startTime * 1000);
					//let year = date.getFullYear();
					let month = ('0' + (date.getMonth() + 1)).slice(-2); // 月份从0开始，需要+1
					let day = ('0' + date.getDate()).slice(-2);
					let hours = ('0' + date.getHours()).slice(-2);
					let minutes = ('0' + date.getMinutes()).slice(-2);
					//let seconds = ('0' + date.getSeconds()).slice(-2);

					that.details.category.startTime1 = `${month}月${day}日 ${hours}:${minutes}`;

					if (that.load == true) {
						that.load = false;
					}

					if (that.details.category.status == 2) {
						clearInterval(that.time1);
					}

					if (that.jiazai == false) {
						that.scrollIntoId = 'item-' + that.details.product[that.details.category.number - 1].id;
						that.downTime();
						that.jiazai = true;
					}
					
				//	console.log(that.details.category);
				})
			},

			addNumberPlate() {
				const that = this;
				if (!this.userInfo) {
					this.getUserInfo();
					return;
				}
				//console.log(this.userInfo);
				if (!this.userInfo.card_id) {
					this.show = true;
					return;
				}
				getNumberPlate(that.id).then((res) => {
					//console.log(res);
					that.getPlateNumberdetails(1);
				})
			},

			getPlateNumberdetails(index) {
				const that = this;
				//console.log(that.details.category);
				getNumberdetails(that.id).then((res) => {
					that.plate = res.data.number_plate || '';
					if ((res.data.application || 0) === 1) {
						that.state = 2;
					} else {
						// 与后端 buildParticipationDetails 一致（含后台分配号牌未缴保证金 state=1）
						that.state = Number(res.data.state ?? 0);
					}
					mergePlateDepositDetails(that, res.data);
					//console.log(res.data);
					if (that.plate && index == 1) {
						uni.showToast({
							title: '您的号牌是' + that.plate,
							icon: 'none',
							duration: 2000
						});

					}
				}).catch(() => {
					that.parallelCates = [];
					that.timedMeta = Object.assign({}, DEFAULT_TIMED_META, { tip: '' });
					that.loadTimedDepositDisplay();
				})
			},

			todetails(id) {
				uni.navigateTo({
					url: "/pages/goods_cate/goods_cate2?id=" + id + "&cate_id=" + this.id
				})
			},

			changeActive(index) {
				this.active = index;
			},

			toSpecial() {
				var id = this.details.product[this.details.category.number - 1].id;

				uni.navigateTo({
					url: "/pages/goods_cate/goods_cate2?id=" + id + "&cate_id=" + this.id
				})
			},

			kefu() {
				uni.navigateTo({
					url: "/pages/columnGoods/HotNewGoods/feedback"
				})
			},

			SubscribeMessage(tmplId) {
				SendSubscribeMessage(this.userInfo.uid, this.userInfo.phone, this.id, 0, 0).then((res) => {
					//console.log(res);
				})
			},

			pay(index) {
				const that = this;
				if (index == 1) {
					const payAmount = that.need_pay > 0 ? that.need_pay : that.deposit_amount;
					if (!payAmount || payAmount <= 0) {
						uni.showToast({
							title: '保证金已缴足',
							icon: 'none'
						});
						this.popupShow = false;
						return;
					}
					let code = '';
					wx.login({
						success: res => {
							code = res.code;
							uni.request({
								url: 'https://mtpm.billionwz.com//api/wxlogin', //仅为示例，并非真实接口地址。
								data: {
									code: code
								},
								success: (ress) => {
									getwxpay(ress.data.data.openid, that.id, payAmount).then((resss) => {
										that.wxpay(resss.data);
									})
								}
							})
						}
					})
					
				}
				this.popupShow = false;
			},

			payShow() {
				this.popupShow = true;
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
						console.log('支付成功', res);
						that.getPlateNumberdetails();
						uni.showToast({
							title: '支付成功'
						});
						// 支付成功后的业务逻辑，例如跳转到成功页面
					},
					fail: (err) => {
						console.log('支付失败', err);
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

			bangding(index) {
				if (index == 1) {
					uni.navigateTo({
						url: "/pages/users/user_info/index"
					});

				}
				this.popupShow1 = false;
			},

			previewImage(imageUrl) {
				uni.previewImage({
					urls: [imageUrl], // 要预览的图片地址数组
					current: 0, // 当前显示图片的索引
				});
			},
			
			sendSubscriptionInformation() {
				if(this.scribeMessage == 1){
					return;
				}
				const tmplId = 'lzI5nOgDKOhAQoUexbLXDWrK2cSU05BgLA1dEbsYuEM';
				const that = this;
				uni.requestSubscribeMessage({
					tmplIds: [tmplId], // 数组格式，一次最多3条
					success: (res) => {
						// res 格式示例：{ errMsg: "requestSubscribeMessage:ok", '模板ID': 'accept' }
						if (res[tmplId] === 'accept') {
							// 取"下一个即将开拍"的产品 product_id
							// details.category.number 是当前正在拍的第几件（1-based）
							// auction() 发通知时查的是 productList[number] = 下一件产品
							// 所以订阅时应存 product[number].id（0-based index = number）
							const number = that.details && that.details.category ? that.details.category.number : 0;
							const productList = that.details && that.details.product ? that.details.product : [];
							// 如果 number 在范围内，取下一件；否则取当前正在拍的
							const nextProduct = productList[number] ? productList[number] : productList[number - 1];
							const productId = nextProduct ? nextProduct.id : that.id;
							
							SendSubscribeMessage(productId).then((res)=>{
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
			
			getScribeMessages(){
				getScribeMessage(this.id).then((res)=>{
					//console.log(res);
					//console.log(this.scribeMessage);
					if(res.msg == "true"){
						this.scribeMessage = 1;
					}
				})
			},
			
			
			noshoW() {
				this.popupShow2 = false;
			},

			/**
			 * 每日首次进入弹出服务号二维码
			 * 使用本地存储记录今日日期，当日已弹过则不再弹出
			 * 次日重新进入时日期不同，自动重置恢复弹出
			 */
			checkQrCodeShow() {
				const today = new Date().toISOString().slice(0, 10); // 如 "2026-04-13"
				const lastShownDate = uni.getStorageSync('qrcode_shown_date');
				if (lastShownDate !== today) {
					// 今天还未弹过：显示弹窗，并记录今日日期
					this.popupShow2 = true;
					uni.setStorageSync('qrcode_shown_date', today);
				}
				// 若 lastShownDate === today，popupShow2 保持 false，不弹
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
	
	.activeColor{
		color: #a71f24;
	}
	
	.activeborder{
		border: 1px solid #a71f24;
	}

	.session-tabs {
		flex-wrap: wrap;
		gap: 10px;
	}

	.session-tab {
		padding: 6px 14px;
		font-size: 13px;
		color: #666;
		background: #f5f5f5;
		border-radius: 6px;
	}

	.session-tab-active {
		color: #fff;
		background: #a71f24;
	}

	.timed-tip {
		font-size: 12px;
		color: #999;
		line-height: 1.5;
	}
</style>
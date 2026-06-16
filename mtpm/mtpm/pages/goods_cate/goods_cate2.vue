<template>
	<view v-if="!load">
		<!--<u-navbar back-text=" " :title="detail.store_name"></u-navbar>-->
		<view class="w-full">
			<swiper class="w-full" :style="'height:' + swiperH + 'px'">
				<swiper-item v-for="(item, index) in detail.slider_image" :key="index">
					<image :src="item" class="w-full" mode="widthFix" @click="previewImage(item)"></image>
				</swiper-item>
			</swiper>
		</view>
		<view class="w-full bg-white">
			<view style="display: flex;justify-content: space-between;padding: 20px 10px 10px 10px;">
				<view v-if="cate.status == 0">
					预展中 {{cate.startTime1}} 开拍
				</view>
				<view v-else-if="cate.status == 1 && cate.number < index">
					即将开始
				</view>
				<view v-else-if="cate.status == 1 && cate.number == index">
					距离结束还有<span style="color: #a71f24;margin-left: 8px;">{{countDown}}</span>
				</view>
				<view v-else>
					已结拍
				</view>
				<view @click="sendSubscriptionInformation()" :class=" scribeMessage==1 ? 'activeColor':''">
					提醒
				</view>
			</view>


			<view class="flex justify-between p-10" v-if="navShow==0">
				<view class="bold">Lot {{index}}</view>
				<view :style="'width:' + lotW + 'px'">
					<scroll-view scroll-x="true" style="white-space: nowrap;" :scroll-left="scrollLeft">
						<view v-for="item in categoryProduct" :key="item.id" style="width: auto;padding: 0 5px;color: #999;"
							class="inline-block" @click="toProduct(cate_id,item.id)">
							Lot {{item.index}}
						</view>

					</scroll-view>
				</view>
			</view>
			<view style="font-size: 20px;" class="bold p-10">
				{{detail.store_name}}
			</view>

			<view class="flex bold p-10" v-if="detail.price==0">
				<view>起拍价 RMB</view>
				<view style="margin-left: 8px;color: #a71f24;">
					{{startingBid}}
				</view>
			</view>
			<view class="flex bold p-10" v-else>
				<view>当前价 RMB</view>
				<view style="margin-left: 8px;color: #a71f24;">
					{{detail.price}}
				</view>
			</view>
			<view style="padding: 0 10px;" class="bold" v-if="agent_price">
				我的委托 RMB {{agent_price}}<text style="color: #a71f24;"
					v-if="detail.price >= agentPrices">(您的代理金额已被超越)</text>
			</view>
			<view class="p-10"></view>
		</view>

		<view v-if="bidding && (cate.status == 0 || (cate.status == 1 &&  cate.number <= index))"
			style="background-color: #fff;margin-top: 15px;padding: 20px 10px;">
			<view class="flex justify-between bold">
				<view>竞拍记录</view>
				<view>{{bidding.length}}次</view>
			</view>
			<scroll-view scroll-x="true" style="white-space: nowrap; width: 100%;margin-top: 20px;">
				<view v-for="(item,index) in bidding" :key="item.bidKey" style="width: auto;" class="center inline-block"
					:class="index!=0 ? 'ml-10':''">
					<view v-if="index == 0" style="background-color: #a71f24;color: #fff;padding: 10px 15px;">
						{{item.plate}}号 领先
					</view>
					<view v-else style="background-color: #e9e9e9;color: #999;padding: 10px 15px;">
						{{item.plate}}号 出局
					</view>
					<view style="padding: 15px 0;letter-spacing: 1px;padding: 10px 15px;">
						<view class="bold">RMB</view>
						<view class="bold" style="font-size: 18px;">{{item.price}}</view>
					</view>
				</view>
			</scroll-view>
		</view>

		<view class="bg-white flex warp" style="padding: 20px 10px;margin-top: 10px;color: #000000;">
			<view class="flex p-10-0 half">
				<view class="w-15">
					<image src="@/static/images/logo1.png" class="w-full" mode="widthFix"></image>
				</view>
				<view class="ml-10">
					<view>保证金</view>
					<view class="bold" style="margin-top: 5px;">RMB {{depositDisplay}}</view>
				</view>

			</view>
			<view class="flex p-10-0 half">
				<view class="w-15">
					<image src="@/static/images/logo2.png" class="w-full" mode="widthFix"></image>
				</view>
				<view class="ml-10">
					<view>佣金</view>
					<view class="bold" style="margin-top: 5px;">10%</view>
				</view>

			</view>
			<view class="flex p-10-0 half" @click="biddingIncrement(1)">
				<view class="w-15">
					<image src="@/static/images/logo3.png" class="w-full" mode="widthFix"></image>
				</view>
				<view class="ml-10">
					<view>竞价阶梯</view>
					<view class="bold" style="margin-top: 5px;">查看详情</view>
				</view>
			</view>
			<view class="flex p-10-0 half">
				<view class="w-15">
					<image src="@/static/images/logo4.png" class="w-full" mode="widthFix"></image>
				</view>
				<view class="ml-10">
					<view>运费</view>
					<view class="bold" style="margin-top: 5px;">到付</view>
				</view>
			</view>
		</view>

		<view>
			<scroll-view scroll-x="true"></scroll-view>
		</view>

		<view class="bg-white flex warp" style="padding: 20px 10px 130px;margin-top: 10px;color: #000000;">
			<view class="w-full bold">拍品介绍</view>
			<view class="w-full" style="margin-top: 20px;">
				<!--<rich-text :nodes="detail.description"></rich-text>-->
				<view v-if="detail.video_link" style="padding-bottom: 15px;">
					<video :src="detail.video_link" class="w-full" controls="true"></video>
				</view>
				<mp-html :content="detail.description" />
			</view>
		</view>

		<view class="w-full bottomBox">
			<view class="w-full plate" v-if="plate">号牌{{plate}},<text v-if="state==2">退款审核中</text><text v-else-if="state==0">请联系客服激活号牌</text><text
					v-else>保证金已缴纳</text></view>
			<view class="plateBox">

				<view class="w-full plateBtn" v-if="!plate && cate.status!=2" @click="addNumberPlate">办理号牌</view>
				<!--<view class="w-full plateBtn" v-if="!plate && cate.status!=2" @click="reset(1)">编辑代理出价</view>-->
				<view class="w-full plateBtn" style="background-color: #e5e5e5;" v-else-if="plate && state==2">退款审核中</view>
				<view class="w-full plateBtn" v-else-if="plate && state==0 &&cate.status!=2" @click="pay()">缴纳保证金</view>
				<view v-else-if="plate && state==1 && (cate.status==0 || (cate.status==1 && cate.number<index))">
					<view class="w-full plateBtn" v-if="!agent_price" @click="isShow">代理出价</view>
					<view class="w-full plateBtn flex" v-else>
						<view class="half btn1" @click="cancelAgent()">取消代理出价</view>
						<view class="half" @click="reset(1)">编辑代理出价</view>
					</view>
				</view>
				<view v-else-if="plate && state==1 && cate.status==1 && cate.number==index">
					<view class="w-full plateBtn flex" v-if="!agent_price">
						<view class="half btn1" @click="isShow()">代理出价</view>

						<view class="half" @click="requestPrice()">直接竞价</view>
					</view>
					<view class="w-full plateBtn flex" v-else>
						<view class="half btn1" @click="ispopupShow2()">编辑代理出价</view>
						<view class="half" @click="requestPrice()">直接竞价</view>
					</view>
				</view>
				<view v-else>
					<view class="w-full plateBtn btn1"
						v-if="detail.uid != uid || detail.is_order != 0 || detail.is_show==0 ">已结拍</view>
					<view class="w-full plateBtn" @click="getcart()" v-else>去支付
					</view>
				</view>


			</view>
		</view>
		<!-- 选择代理出价 -->
		<u-popup v-model="show" mode="center">
			<view style="padding: 20px 10px;border-radius: 5px;">
				<view class="setAmountb">
					代理金额
				</view>
				<view class="flex" style="margin-top: 20px;">
					<view class="btn" @click="addInputV(0)">
						-</view>
					<view class="amountb">
						<input type="number" :value="inputValue" style="height: 50px;" @input="onInput" />
					</view>
					<view class="btn" @click="addInputV(1)">
						+</view>
				</view>
				<view class="flex justify-between w-full">
					<view class="flex justify-between w-full" style="margin-top: 20px;">
						<view class="bold p-10-0 center agentbtn1" style="width: 49%;border-radius: 5px;"
							@click="getagent(0)">取消出价</view>
						<view class="bold p-10-0 center ml-10 text-white agentbtn2"
							style="width: 49%;border-radius: 5px;" @click="getagent(1)">确认出价</view>
					</view>
				</view>
			</view>

		</u-popup>

		<!-- 选择修改代理出价 -->
		<u-popup v-model="show1" mode="center">
			<view style="padding: 20px 10px;border-radius: 5px;">
				<view class="setAmountb">
					编辑代理金额
				</view>
				<view class="flex" style="margin-top: 20px;">
					<view class="btn" @click="addInputV(0)">
						-</view>
					<view class="amountb">
						<input type="number" :value="inputValue" style="height: 50px;" @input="onInput" />
					</view>
					<view class="btn" @click="addInputV(1)">
						+</view>
				</view>
				<view class="flex justify-between">
					<view class="flex justify-between w-full" style="margin-top: 20px;">
						<view class="bold p-10-0 center agentbtn1" style="width: 48%;border-radius: 5px;"
							@click="resetgetAagent(0)">取消</view>
						<view class="bold p-10-0 center ml-10 text-white agentbtn2"
							style="width: 48%;border-radius: 5px;" @click="resetgetAagent(1)">确认</view>
					</view>
				</view>
			</view>

		</u-popup>

		<!-- 确认代理出价 -->
		<u-popup v-model="popupShow" mode="center">
			<view style="padding: 20px 10px;border-radius: 5px;">
				<view class="w-full bold center">
					你的最高出价金额是
				</view>
				<view style="font-size: 24px;" class="center mt-15">
					RMB {{inputValue}}
				</view>
				<view class="flex mt-15">
					<view class="bold p-10-0 center agentbtn1" @click="postagent(0)">取消</view>
					<view class="bold p-10-0 center ml-10 text-white agentbtn2" @click="postagent(1)">确认</view>
				</view>
			</view>

		</u-popup>

		<u-popup v-model="popupShow6" mode="center">
			<view style="padding: 20px 10px;border-radius: 5px;">
				<view class="w-full bold center">
					按照本拍卖进价阶梯为您设置代理出价为
				</view>
				<view style="font-size: 24px;" class="center mt-15">
					RMB {{inputValue}}
				</view>
				<view class="flex mt-15">
					<view class="bold p-10-0 center agentbtn1" style="width: 48%;" @click="getagent(0)">取消</view>
					<view class="bold p-10-0 center ml-10 text-white agentbtn2" style="width: 48%;"
						@click="getagent(1)">确认</view>
				</view>
			</view>

		</u-popup>


		<u-popup v-model="popupShow7" mode="center">
			<view style="padding: 20px 10px;border-radius: 5px;">
				<view class="w-full bold center">
					按照本拍卖进价阶梯为您修改的代理出价为
				</view>
				<view style="font-size: 24px;" class="center mt-15">
					RMB {{inputValue}}
				</view>
				<view class="flex mt-15">
					<view class="bold p-10-0 center agentbtn1" style="width: 48%;" @click="resetgetAagent(0)">取消</view>
					<view class="bold p-10-0 center ml-10 text-white agentbtn2" style="width: 48%;"
						@click="resetgetAagent(1)">确认</view>
				</view>
			</view>

		</u-popup>

		<u-popup v-model="popupShow8" mode="center">
			<view style="padding: 20px 10px;border-radius: 5px;">
				<view class="w-full bold center">
					按照本拍卖进价阶梯为您出价为
				</view>
				<view style="font-size: 24px;" class="center mt-15">
					RMB {{inputValue}}
				</view>
				<view class="flex mt-15">
					<view class="bold p-10-0 center agentbtn1" style="width: 48%;" @click="getprice(0)">取消</view>
					<view class="bold p-10-0 center ml-10 text-white agentbtn2" style="width: 48%;"
						@click="getprice(1)">确认</view>
				</view>
			</view>

		</u-popup>

		<!--确认修改代理出价 -->
		<u-popup v-model="popupShow1" mode="center">
			<view style="padding: 20px 10px;border-radius: 5px;">
				<view class="w-full bold center">
					你修改的最高出价是
				</view>
				<view style="font-size: 24px;" class="center mt-15">
					RMB {{inputValue}}
				</view>
				<view class="flex mt-15">
					<view class="bold p-10-0 center agentbtn1" @click="resetagent(0)">取消</view>
					<view class="bold p-10-0 center ml-10 text-white agentbtn2" @click="resetagent(1)">确认</view>
				</view>
			</view>

		</u-popup>
		<u-popup v-model="popupShow2" mode="center" :border-radius="10">
			<view class="flex bold"
				style="padding: 40px 15px;line-height: 35px;text-align: center;border-radius: 10rpx;">
				<view class="btn1" style="width: 130px;" @click="cancelAgent(1)">取消代理出价</view>
				<view class="agentbtn2 ml-10" style="color: #ffffff;" @click="reset(1)">修改代理出价</view>
			</view>
		</u-popup>

		<!--设置出价金额 -->
		<u-popup v-model="popupShow3" mode="center">
			<view style="padding: 20px 10px;border-radius: 5px;">
				<view class="setAmountb">
					竞价金额
				</view>
				<view class="flex" style="margin-top: 20px;">
					<view class="btn" @click="addInputV(0)">
						-</view>
					<view class="amountb">
						<input type="number" :value="inputValue" style="height: 50px;" @input="onInput" />
					</view>
					<view class="btn" @click="addInputV(1)">
						+</view>
				</view>
				<view class="flex justify-between">
					<view class="flex justify-between w-full" style="margin-top: 20px;">
						<view class="bold p-10-0 center agentbtn1" style="width: 48%;border-radius: 5px;"
							@click="getprice(0)">取消出价</view>
						<view class="bold p-10-0 center ml-10 text-white agentbtn2"
							style="width: 48%;border-radius: 5px;" @click="getprice(1)">确认出价</view>
					</view>
				</view>
			</view>

		</u-popup>

		<u-popup v-model="popupShow4" mode="center">
			<view style="padding: 20px 10px;border-radius: 5px;">
				<view class="w-full bold center">
					你的出价金额是
				</view>
				<view style="font-size: 24px;" class="center mt-15">
					RMB {{inputValue}}
				</view>
				<view class="flex mt-15">
					<view class="bold p-10-0 center agentbtn1" @click="addPrices(0)">取消</view>
					<view class="bold p-10-0 center ml-10 text-white agentbtn2" @click="addPrices(1)">确认</view>
				</view>
			</view>

		</u-popup>

		<u-popup v-model="popupShow5" mode="center">
			<view style="padding: 20px 10px;border-radius: 5px;width: 320px;">
				<view class="w-full bold center relative" style="font-size: 26px;">
					<view class="w-full center">默认竞价阶梯</view>
					<view style="position: absolute;right: 0;top: 0;" @click="biddingIncrement(0)">
						<image src="@/static/images/close.png" style="width: 20px;" mode="widthFix"></image>
					</view>
				</view>
				<view class="w-full mt-15" style="padding-bottom: 15px;">
					<view class="flex justify-between lineH-40 bg-f7">
						<view class="w-40 text-right">
							竞价区间
						</view>

						<view class="w-40">
							加价幅度
						</view>
					</view>
					<view class="flex justify-between lineH-40">
						<view class="w-40 text-right">
							0-50000
						</view>
						<view style="margin-top: 5px;">
							<image src="@/static/images/right.png" style="width: 20px;" mode="widthFix"></image>
						</view>
						<view class="w-40">
							500
						</view>
					</view>
					<view class="flex justify-between lineH-40 bg-f7">
						<view class="w-40 text-right">
							50000以上
						</view>
						<view style="margin-top: 5px;">
							<image src="@/static/images/right.png" style="width: 20px;" mode="widthFix"></image>
						</view>
						<view class="w-40">
							1000
						</view>
					</view>
				</view>
			</view>

		</u-popup>
		<u-modal v-model="shows" :content="content" :show-cancel-button="true" :confirm-text="confirmText"
			@confirm="confirm"></u-modal>
		<view
			style="width: 100%;height: 100vh;background-color: rgb(0, 0, 0,0.5);z-index: 99;position: fixed;top: 0;left: 0;"
			v-if="paystatus">
			<view style="position: absolute;top: 50%;left: 50%;transform: translate(-50%,-50%);">
				<u-loading mode="circle"></u-loading>
			</view>
		</view>
	</view>
	<view v-else style="width: 100%;height: 100vh;">
		<view style="position: absolute;top: 50%;left: 50%;transform: translate(-50%,-50%);">
			<u-loading mode="circle"></u-loading>
		</view>
	</view>

</template>

<script>
	import mpHtml from '@/node_modules/mp-html/dist/uni-app/components/mp-html/mp-html'
	import {
		data
	} from '../../uview-ui/libs/mixin/mixin';
	import {
		getProductDetail,
		getCategorydDetails,
		getNumberPlate,
		getNumberdetails,
		getAgentPrices,
		cancelAgentPrices,
		updateAgentPrices,
		addView,
		addPrice,
		postCartAdd,
		getproductDetails,
		agentPriceDetail,
		getbiddingList,
		getwxpay,
	} from '@/api/store.js';
	import {
		applyTimedDepositFromCategoryRes,
		loadTimedDepositAmount,
		mergePlateDepositDetails
	} from '@/utils/timedDeposit.js';
	import {
		handleTimedManualBidResult,
		normalizeBidMessage
	} from '@/utils/bidResult.js';
	import {
		getUserInfo,
		userShare,
		SendSubscribeMessage,
		getScribeMessage
	} from '@/api/user.js';
	export default {
		components: {
			mpHtml
		},
		data() {
			return {
				detail: "", //商品详情
				cate: "", //分类详情
				id: 0, //商品id
				cate_id: 0, //分类id
				index: 0, //商品序号
				lotW: 0,
				scrollLeft: 0,
				plate: "", //号牌
				agentW: 0,
				show: false, //代理出价框
				selector: [], //代理金额选择
				popupShow: false, //确认代理出价
				agent: 0, //零时代理金额
				agent_price: "", //代理金额
				agentPrices: 0,
				swiperH: 650, //swiper高度
				startingBid: 0, //起拍价
				addprice: 500,
				bidding: [],
				scrollX: true,
				show1: false,
				popupShow1: false,
				popupShow2: false,
				popupShow3: false,
				popupShow4: false,
				uid: 0,
				countDown: "",
				categoryProduct: [],
				inputValue: 0,
				popupShow5: false,
				popupShow6: false,
				popupShow7: false,
				popupShow8: false,
				userInfo: "",
				shows: false,
				content: "请先完成实名认证",
				confirmText: "去实名",
				load: true,
				status: 0,
				state: 0,
				deposit_amount: 1,
				need_pay: 1,
				showTrue: 0,
				agentStatus: 0,
				cycleTime: 5000,
				socketTask: null,
				time1: null,
				timer: null,
				orderId: "",
				openId: "",
				timer1: null,
				navShow: 0,
				paystatus: 0,
				downTrue: 0,
				scribeMessage: 0,
				nextLotPromptShown: false
			}
		},
		computed: {
			depositDisplay() {
				const amount = Number(this.deposit_amount || 0);
				return amount ? amount.toLocaleString() : '0';
			}
		},
		onLoad(options) {
			this.id = options.id;
			this.cate_id = options.cate_id;
			this.nextLotPromptShown = false;
			const system = uni.getSystemInfoSync();
			this.lotW = system.windowWidth - 70;
			this.agentW = system.windowWidth - 115;
			this.getUserInfo();

		},
		onShow() {
			this.loadTimedDepositDisplay();
			this.productDetails();
			this.getScribeMessages();
			this.getPlateNumberdetails(0);
			this.getagentPrice();
			this.getDetails();
			this.biddingList();

			// 切回来时，立即重启倒计时（downTime内部有防重复逻辑：先clear再创建）
			// 不加 timer==null 限制：微信切后台时 setInterval 被暂停但引用不为null，
			// 不重启会导致切回来后最多等1秒才刷新，用户看到"冻住"效果
			if (this.detail && this.detail.presale_start_time > 0 && this.detail.presale_end_time == 0) {
				this.downTrue = 1;
				this.downTime();
			}

			const that = this;
			setTimeout(() => {
				that.getview(that.cate_id);
			}, 3000);

			getApp().globalData.type = {
				type: 'product',
				data: {
					id: this.id
				}
			};

			this.sendMessage({
				type: 'product',
				data: {
					id: this.id
				}
			});

			this.sendData({
				type: 'product',
				data: {
					id: this.id
				}
			})
		},

		onHide() {
			// 不清除倒计时 timer，切出小程序后倒计时应继续（基于服务器时间差计算，回来后自动正确）
			// 只清 WebSocket 心跳和延时弹窗
			clearTimeout(this.timer1);
			clearInterval(this.time1);
			getApp().globalData.type = "";
			uni.$off('socketMessage', this.handleMessage);
		},
		onUnload() {
			// 离开页面时才真正清除所有计时器
			clearInterval(this.timer);
			this.timer = null;
			this.downTrue = 0;
			clearTimeout(this.timer1);
			clearInterval(this.time1);
			getApp().globalData.type = "";
			uni.$off('socketMessage', this.handleMessage);
		},
		onShareAppMessage() {
			const that = this;
			return {
				title: that.detail.store_name, // 分享标题
				path: '/pages/goods_cate/goods_cate2?id=' + this.id + "&cate_id=" + that.cate.id, // 分享的页面路径，通常携带一些参数
				imageUrl: that.detail.image // 可选，自定义分享图片
			};
		},


		onShareTimeline() {
			const that = this;
			return {
				title: that.detail.store_name, // 分享标题
				path: '/pages/goods_cate/goods_cate2?id=' + this.id + "&cate_id=" + that.cate.id, // 分享的页面路径，通常携带一些参数
				imageUrl: that.detail.image // 可选，自定义分享图片
			};
		},

		methods: {



			handleMessage(data) {
				const that = this;
				//console.log(data);
				if (data['type'] == "product1") {
					const incoming = Array.isArray(data['data']) ? (data['data'][0] || {}) : (data['data'] || {});
					if (Number(incoming.id) !== Number(that.cate_id)) {
						return;
					}
					that.cate = incoming;
					if (that.cate.status == 0) {
						const date = new Date(that.cate.startTime * 1000);

						let month = ('0' + (date.getMonth() + 1)).slice(-2); // 月份从0开始，需要+1
						let day = ('0' + date.getDate()).slice(-2);
						let hours = ('0' + date.getHours()).slice(-2);
						let minutes = ('0' + date.getMinutes()).slice(-2);
						let seconds = ('0' + date.getSeconds()).slice(-2);

						that.cate.startTime1 = `${month}月${day}日 ${hours}:${minutes}`;
					}
					// 收到新cate数据，若满足条件则重启计时器
					if (that.detail && that.detail.presale_start_time > 0 && that.detail.presale_end_time == 0) {
						that.downTrue = 1;
						that.downTime();
					}

					//that.cate.status = 0;
				} else if (data['type'] == "product2") {
					const pinfo = Array.isArray(data['data']) ? (data['data'][0] || {}) : (data['data'] || {});
					if (Number(pinfo.id) !== Number(that.id)) {
						return;
					}
					if (pinfo && pinfo.id) {
						that.detail = Object.assign({}, that.detail || {}, pinfo);
					}
					if (that.detail.presale_end_time) {
						let data = new Date(that.detail.presale_end_time);
						data = data.getTime();
						data = data + 7 * 24 * 3600 * 1000;
						const date = new Date(data);
						let month = ('0' + (date.getMonth() + 1)).slice(-2);
						let day = ('0' + date.getDate()).slice(-2);
						let hours = ('0' + date.getHours()).slice(-2);
						let minutes = ('0' + date.getMinutes()).slice(-2);
						that.detail.presale_end_time1 = `${month}月${day}日 ${hours}:${minutes}`;
					}
					if (that.detail.presale_start_time > 0 && that.detail.presale_end_time == 0) {
						// 每次收到数据都重启计时器（确保cate.delayCycle最新）
						that.downTrue = 1;
						that.downTime();
					}
					if (that.detail.ot_price != null) {
						that.startingBid = that.detail.ot_price.toLocaleString('en-US');
					}
				} else if (data['type'] == "product3") {
					that.bidding = that.parseBiddingSocketData(data['data']);
				}
			},

			normalizeBiddingList(list) {
				if (!Array.isArray(list)) {
					return [];
				}
				return list.map((row, i) => {
					return Object.assign({}, row, {
						bidKey: row.id ? ('id-' + row.id) : ('i-' + i + '-' + (row.bidding_time || 0) + '-' + (row.price || 0))
					});
				});
			},

			parseBiddingSocketData(raw) {
				let payload = raw;
				let list = [];
				if (Array.isArray(payload)) {
					if (payload.length === 1 && payload[0] && typeof payload[0] === 'object' && payload[0].product_id != null) {
						payload = payload[0];
					} else if (payload.length && payload[0] && (payload[0].price != null || payload[0].plate != null)) {
						return this.normalizeBiddingList(payload);
					}
				}
				if (!payload || typeof payload !== 'object') {
					return [];
				}
				if (payload.product_id != null) {
					if (Number(payload.product_id) !== Number(this.id)) {
						return this.bidding || [];
					}
					list = Array.isArray(payload.list) ? payload.list : [];
				} else if (Array.isArray(payload)) {
					list = payload;
				}
				return this.normalizeBiddingList(list);
			},

			refreshAuctionState() {
				const that = this;
				that.productDetails();
				setTimeout(() => {
					that.biddingList(true);
					that.getagentPrice();
				}, 300);
			},

			hasNextLot() {
				const total = (this.categoryProduct && this.categoryProduct.length) || 0;
				return total > 0 && Number(this.index) > 0 && Number(this.index) < total;
			},

			getNextLotProduct() {
				if (!this.hasNextLot()) {
					return null;
				}
				return this.categoryProduct[this.index] || null;
			},

			shouldPromptNextLot() {
				return this.hasNextLot();
			},

			promptGoNextLotIfNeeded() {
				const that = this;
				if (that.nextLotPromptShown || !that.shouldPromptNextLot()) {
					return;
				}
				const next = that.getNextLotProduct();
				if (!next || !next.id) {
					return;
				}
				that.nextLotPromptShown = true;
				uni.showModal({
					title: '提示',
					content: '本拍品已结拍，是否前往下一件',
					success(res) {
						if (res.confirm) {
							uni.redirectTo({
								url: '/pages/goods_cate/goods_cate2?id=' + next.id + '&cate_id=' + that.cate_id
							});
						}
					}
				});
			},

			downTime() {
				const that = this;
				// 防止重复创建：先清掉旧的
				if (that.timer) {
					clearInterval(that.timer);
					that.timer = null;
				}
				this.timer = setInterval(() => {
					const now = new Date();
					const timestampInSeconds = Math.floor(now / 1000);
					var time = that.cate.startTime + that.cate.delayCycle - timestampInSeconds;

					if (time > 0) {
						var m = Math.floor(time / 60);
						var s = time % 60;
						if (s < 10) {
							s = "0" + s;
						}
						that.countDown = `${m}:${s}`;
					} else if (time <= 0) {
						that.countDown = "0:00";
						clearInterval(that.timer);
						that.timer = null;
						that.downTrue = 0;
						if (that.nextLotPromptShown) {
							return;
						}
						that.getDetails();
						that.productDetails();
						if (that.timer1) {
							clearTimeout(that.timer1);
						}
						that.timer1 = setTimeout(() => {
							that.promptGoNextLotIfNeeded();
							that.timer1 = null;
						}, 2000);
					} else {
						// time < 0：cate数据可能还未刷新，保持显示0:00等待
						that.countDown = "0:00";
					}
				}, 1000)
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
						success: () => {},
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


			biddingList(forceRefresh = true) {
				const that = this;
				getbiddingList(that.id, forceRefresh).then((res) => {
					let list = res.data;
					if (list && !Array.isArray(list) && Array.isArray(list.data)) {
						list = list.data;
					}
					that.bidding = that.normalizeBiddingList(list);
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


			getDetails() {
				const that = this;

				getCategorydDetails(that.cate_id).then((res) => {
					applyTimedDepositFromCategoryRes(that, res.data);
					that.cate = res.data.category;
					that.categoryProduct = res.data.product;

					if (Number(that.cate.id) !== Number(that.cate_id)) {
						return;
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
							that.scrollLeft = i * 50;

						}

					}


				})
			},




			productDetails() {
				const that = this;
				that.selector = [];
				getproductDetails(that.id).then((res) => {
					const info = res.data || {};
					that.detail = Object.assign({}, that.detail || {}, info);
					if (that.detail.slider_image && that.detail.slider_image[0]) {
						that.getImageH(that.detail.slider_image[0]);
					}
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

					if (that.detail.presale_start_time > 0 && that.detail.presale_end_time == 0 && that.timer ==
						null) {
						that.downTrue = 1;
						that.downTime()
					}
					//that.startingBid = that.detail.ot_price.toLocaleString('en-US');
					that.startingBid = that.detail.ot_price
				})
			},


			getagentPrice() {
				const that = this;
				agentPriceDetail(that.id).then((res) => {
					that.agentStatus = res.data.status;
					if (res.data.agent_price && res.data.status != 0) {

						that.agentPrices = res.data.agent_price;
						that.agent_price = res.data.agent_price.toLocaleString('en-US');
					} else {
						that.agentPrices = "";
						that.agent_price = "";
					}
				})
			},


			//添加号码拍
			addNumberPlate() {
				const that = this;
				if (!this.userInfo) {
					this.getUserInfo();
					return;
				}
				if (!this.userInfo.card_id) {
					this.shows = true;
					return;
				}
				getNumberPlate(that.cate_id).then((res) => {
					that.getPlateNumberdetails();

				})
			},

			confirm() {
				uni.navigateTo({
					url: "/pages/realName/index"
				})
			},

			loadTimedDepositDisplay() {
				return loadTimedDepositAmount(this, this.cate_id || this.id);
			},

			//获取号码拍
			getPlateNumberdetails() {
				const that = this;
				getNumberdetails(that.cate_id).then((res) => {
					that.plate = res.data.number_plate || '';
					if ((res.data.application || 0) === 1) {
						that.state = 2;
					} else {
						that.state = Number(res.data.state ?? 0);
					}
					mergePlateDepositDetails(that, res.data);
				}).catch(() => {
					that.loadTimedDepositDisplay();
				})
			},

			onInput(e) {
				this.inputValue = e.detail.value;
			},
			//设置代理出价
			getagent(index) {
				this.popupShow6 = false;
				if (index == 1) {

					var number = Number(this.inputValue);

					if (this.detail.price > number || this.detail.ot_price > number) {
						uni.showToast({
							title: '代理出价不能低于当前价',
							icon: 'none',
							duration: 2000
						});
						return;
					}


					var bid = this.detail.ot_price;
					var num;

					//if (this.inputValue <= 50000) {
					//	if (num % 500 != 0) {
					//		number = Math.floor(num / 500) * 500;
					//		this.inputValue = number + bid;
					//		this.popupShow6 = true;
					//	} else {
					//		this.agent = this.inputValue;
					//		this.popupShow = true;

					//	}
					//} else {
					//num = num - 50000;

					//	if (num % 1000 != 0) {
					//		number = Math.floor(num / 1000) * 1000;
					//		this.inputValue = number + 50000 + bid

					//		this.popupShow6 = true;
					//	} else {
					//		this.agent = this.inputValue;
					//		this.popupShow = true;
					//	}
					//}



					if (this.inputValue <= 50000) {
						num = this.inputValue - bid;
						if (this.inputValue < this.detail.price + 500) {
							this.agent = this.detail.price + 500;
							this.popupShow = true;
						} else if (num % 500 != 0) {
							number = Math.floor(num / 500) * 500;
							this.inputValue = number + bid;
							this.popupShow6 = true;
						} else {
							this.agent = this.inputValue;
							this.popupShow = true;
						}
					} else {
						var num1;
						//console.log(bid);
						if (bid >= 50000) {
							num = this.inputValue - bid;
							//console.log(num);
							if (this.inputValue < bid + 1000) {
								this.inputValue = bid + 1000;
								this.popupShow = true;
								//console.log("yes");
							} else {
								if (num % 1000 != 0) {
									number = Math.floor(num / 1000) * 1000;
									this.inputValue = number + bid;
									this.popupShow6 = true;
								} else {
									this.agent = this.inputValue;
									this.popupShow = true;
								}
								//console.log("no");
							}

						} else {
							num1 = 50000 - bid;
							//num = Math.floor(num / 500)
							if (num1 % 500 != 0) {
								num1 = Math.floor(num1 / 500) + 1;
							} else {
								num1 = Math.floor(num1 / 500);
							}
							num1 = num1 * 500 + bid;
							//console.log(num1);
							if (this.inputValue < num1) {
								this.inputValue = num1;
								this.popupShow = true;
							} else {
								num = this.inputValue - num1;
								if (num % 1000 != 0) {
									number = Math.floor(num / 1000) * 1000;
									this.inputValue = number + num1;
									this.popupShow6 = true;
								} else {
									this.agent = this.inputValue;
									this.popupShow = true;
								}
							}
						}
					}

				}

				this.show = false;
			},

			postagent(index) {
				if (index == 1) {
					const that = this;
					//console.log(that.agent);
					getAgentPrices(that.id, that.agent, that.plate, 0).then((res) => {
						uni.showToast({
							title: '代理出价成功',
							icon: 'none',
							duration: 2000
						});
						that.refreshAuctionState();
					}).catch((err) => {
						uni.showToast({
							title: normalizeBidMessage(err),
							icon: 'none',
							duration: 2500
						});
						that.refreshAuctionState();
					})
				}
				this.popupShow = false;
			},

			isShow() {
				this.show = true;
				//var number = Number(this.inputValue);
				if (this.detail.price == 0 && this.detail.ot_price != 0) {
					this.inputValue = this.detail.ot_price;
				} else if (this.detail.price < 50000) {
					this.inputValue = this.detail.price + 500;
				} else {
					this.inputValue = this.detail.price + 1000;
				}
			},

			cancelAgent() {
				const that = this;
				this.popupShow2 = false;
				cancelAgentPrices(that.id).then((res) => {
					uni.showToast({
						title: '代理出价已取消',
						icon: 'none',
						duration: 2000
					});
					that.agent_price = '';
				}).catch((err) => {
					uni.showToast({
						title: normalizeBidMessage(err),
						icon: 'none',
						duration: 2500
					});
				})
			},

			reset() {
				this.show1 = true;
				this.popupShow2 = false;
				if (this.detail.price == 0 & this.detail.ot_price != 0) {
					this.inputValue = this.detail.ot_price;
				} else if (this.detail.price < 50000) {
					this.inputValue = this.detail.price + 500;
				} else {
					this.inputValue = this.detail.price + 1000;
				}
			},

			resetgetAagent(index) {
				this.popupShow7 = false;
				if (index == 1) {
					//this.popupShow6 = false;
					var number = Number(this.inputValue);

					if (this.detail.price > number || this.detail.ot_price > number) {
						uni.showToast({
							title: '代理出价不能低于当前价',
							icon: 'none',
							duration: 2000
						});
						return;
					}
					var bid = this.detail.ot_price;

					var num;
					//if (this.inputValue <= 50000) {
					//	num = this.inputValue - bid;
					//	if (num % 500 != 0) {
					//		number = Math.floor(num / 500) * 500;
					//		this.inputValue = number + bid;
					//		this.popupShow7 = true;
					//	} else {
					//		this.agent = this.inputValue;
					//		this.popupShow1 = true;

					//	}
					//} else {

					//	if (bid >= 50000) {
					//		num = this.inputValue - bid;
					//	} else {
					//		num = this.inputValue - 50000;
					//	}
					//num = num - 50000;
					//	if (num % 1000 != 0) {
					//		number = Math.floor(num / 1000) * 1000;
					//		this.inputValue = number + 50000 + bid;
					//		this.popupShow7 = true;
					//	} else {
					//		this.agent = this.inputValue;
					//		this.popupShow1 = true;
					//	}
					//}


					if (this.inputValue <= 50000) {
						num = this.inputValue - bid;
						if (this.inputValue < this.detail.price + 500) {
							this.agent = this.detail.price + 500;
							this.popupShow1 = true;
						} else if (num % 500 != 0) {
							number = Math.floor(num / 500) * 500;
							this.inputValue = number + bid;
							this.popupShow7 = true;
						} else {
							this.agent = this.inputValue;
							this.popupShow1 = true;

						}
					} else {
						var num1;
						//console.log(bid);
						if (bid >= 50000) {
							num = this.inputValue - bid;
							//console.log(num);
							if (this.inputValue < bid + 1000) {
								this.inputValue = bid + 1000;
								this.popupShow1 = true;
								//console.log("yes");
							} else {
								if (num % 1000 != 0) {
									number = Math.floor(num / 1000) * 1000;
									this.inputValue = number + bid;
									this.popupShow7 = true;
								} else {
									this.agent = this.inputValue;
									this.popupShow1 = true;
								}
								//console.log("no");
							}

						} else {
							num1 = 50000 - bid;
							//num = Math.floor(num / 500)
							if (num1 % 500 != 0) {
								num1 = Math.floor(num1 / 500) + 1;
							} else {
								num1 = Math.floor(num1 / 500);
							}
							num1 = num1 * 500 + bid;
							//console.log(num1);
							if (this.inputValue < num1) {
								this.inputValue = num1;
								this.popupShow1 = true;
							} else {
								num = this.inputValue - num1;
								if (num % 1000 != 0) {
									number = Math.floor(num / 1000) * 1000;
									this.inputValue = number + num1;
									this.popupShow7 = true;
								} else {
									this.agent = this.inputValue;
									this.popupShow1 = true;
								}
							}
						}
					}
				}
				this.show1 = false;
			},

			resetagent(index) {
				if (index == 1) {
					this.popupShow1 = false;
					const that = this;

					updateAgentPrices(that.id, that.agent, that.plate).then((res) => {
						uni.showToast({
							title: '更新代理出价成功',
							icon: 'none',
							duration: 2000
						});
						that.refreshAuctionState();
					}).catch((err) => {
						uni.showToast({
							title: normalizeBidMessage(err),
							icon: 'none',
							duration: 2500
						});
						that.refreshAuctionState();
					})
				}
				this.popupShow1 = false;
			},

			getview(id) {
				addView(id).then((res) => {}).catch((err) => {
					console.error("addView 调用失败:", err);
				});
			},

			ispopupShow2() {
				this.popupShow2 = true
			},

			requestPrice() {
				if (this.detail.price == 0 & this.detail.ot_price != 0) {
					this.inputValue = this.detail.ot_price;
				} else if (this.detail.price < 50000) {
					this.inputValue = this.detail.price + 500;
				} else {
					this.inputValue = this.detail.price + 1000;
				}
				this.popupShow3 = true;
			},

			getprice(index) {
				this.popupShow3 = false;
				this.popupShow8 = false;
				if (index == 1) {
					var number = Number(this.inputValue);

					if (this.detail.price > number || this.detail.ot_price > number) {
						uni.showToast({
							title: '出价不能低于当前价',
							icon: 'none',
							duration: 2000
						});
						return;
					}
					var bid = this.detail.ot_price;
					var num;
					//console.log(this.inputValue);

					if (this.inputValue <= 50000) {
						num = this.inputValue - bid;
						if (this.inputValue < this.detail.price + 500) {
							this.agent = this.detail.price + 500;
							this.popupShow4 = true;
						} else if (num % 500 != 0) {
							number = Math.floor(num / 500) * 500;
							this.inputValue = number + bid;
							this.popupShow8 = true;
						} else {
							this.agent = this.inputValue;
							this.popupShow4 = true;

						}
					} else {
						var num1;
						//console.log(bid);
						if (bid >= 50000) {
							num = this.inputValue - bid;
							//console.log(num);
							if (this.inputValue < bid + 1000) {
								this.inputValue = bid + 1000;
								this.popupShow4 = true;
								//console.log("yes");
							} else {
								if (num % 1000 != 0) {
									number = Math.floor(num / 1000) * 1000;
									this.inputValue = number + bid;
									this.popupShow8 = true;
								} else {
									this.agent = this.inputValue;
									this.popupShow4 = true;
								}
								//console.log("no");
							}

						} else {
							num1 = 50000 - bid;
							//num = Math.floor(num / 500)
							if (num1 % 500 != 0) {
								num1 = Math.floor(num1 / 500) + 1;
							} else {
								num1 = Math.floor(num1 / 500);
							}
							num1 = num1 * 500 + bid;
							//console.log(num1);
							if (this.inputValue < num1) {
								this.inputValue = num1;
								this.popupShow4 = true;
							} else {
								num = this.inputValue - num1;
								if (num % 1000 != 0) {
									number = Math.floor(num / 1000) * 1000;
									this.inputValue = number + num1;
									this.popupShow8 = true;
								} else {
									this.agent = this.inputValue;
									this.popupShow4 = true;
								}
							}
						}
					}

					//this.popupShow4 = true;
				}
			},

			addPrices(index) {
				const that = this;
				if (index == 1) {
					this.paystatus = 1;
					addPrice(that.id, that.plate, that.inputValue)
						.then((res) => {
							that.paystatus = 0;
							handleTimedManualBidResult(that, res.msg);
						})
						.catch((err) => {
							that.paystatus = 0;
							handleTimedManualBidResult(that, normalizeBidMessage(err));
						});
				}
				this.popupShow4 = false
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

			addInputV(idx) {
				var number = Number(this.inputValue);
				if (idx == 0) {

					if (number - 500 >= this.detail.price + 500) {
						if (number - 1000 >= 50000) {
							this.inputValue = number - 1000;
						} else {
							this.inputValue = number - 500;
						}
					}
				} else {
					if (this.inputValue < 50000) {
						this.inputValue = number + 500;
					} else {
						this.inputValue = number + 1000;
					}
				}
			},


			biddingIncrement(index) {
				if (index == 1) {
					this.popupShow5 = true;
				} else {
					this.popupShow5 = false;
				}
			},

			toProduct(cid, id) {
				if (this.swiperH > 0) {
					uni.navigateTo({
						url: "/pages/goods_cate/goods_cate2?id=" + id + "&cate_id=" + cid
					})
				}

			},

			kefu() {
				uni.navigateTo({
					url: "/pages/columnGoods/HotNewGoods/feedback"
				})
			},

			pay() {
				const that = this;
				const payAmount = that.need_pay > 0 ? that.need_pay : that.deposit_amount;
				if (!payAmount || payAmount <= 0) {
					uni.showToast({
						title: '保证金已缴足',
						icon: 'none'
					});
					return;
				}
				wx.login({
					success: res => {
						const code = res.code;
						uni.request({
							url: 'https://mtpm.billionwz.com//api/wxlogin',
							data: {
								code: code
							},
							success: (ress) => {
								getwxpay(ress.data.data.openid, that.cate_id, payAmount).then((
									resss) => {
									that.wxpay(resss.data);
								})
							}
						})
					}
				})
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
						that.getPlateNumberdetails();
						uni.showToast({
							title: '支付成功'
						});
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

			previewImage(url) {
				uni.previewImage({
					current: url, // 当前显示图片的链接
					urls: [url] // 需要预览的图片列表（支持多张）
				});
			},

			sendSubscriptionInformation() {
				if (this.scribeMessage == 1) {
					return;
				}
				const that = this;
				// SendSubscribeMessage(this.id).then((res) => {
				// 	//console.log('用户同意订阅');
				// 	uni.showToast({
				// 		title: '用户同意订阅'
				// 	});

				// 	that.getScribeMessages();
				// })

				const tmplId = 'TOK4bhRLg3mya7fnZP8fUj5WoGm8wZeu4QKeVSuebg0';
				//				const that = this;
				uni.requestSubscribeMessage({
					tmplIds: [tmplId], // 数组格式，一次最多3条
					success: (res) => {
						// res 格式示例：{ errMsg: "requestSubscribeMessage:ok", '模板ID': 'accept' }
						if (res[tmplId] === 'accept') {
							that.scribeMessage == 1

							SendSubscribeMessage(this.id, that.openId).then((resss) => {
								//console.log('用户同意订阅');
								uni.showToast({
									title: '用户同意订阅'
								});

								that.getScribeMessages();
							})
						}
					}



				})
			},

			getScribeMessages() {
				getScribeMessage(this.id).then((res) => {
					console.log(res);
					//console.log(this.scribeMessage);
					if (res.msg == "true") {
						this.scribeMessage = 1;
					}
				})
			},

		}
	}
</script>

<style>
	page {
		font-family: '仿宋';
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

	.inline-block {
		display: inline-block;
	}

	.bg-white {
		background-color: #ffffff;
	}

	.warp {
		flex-wrap: wrap;
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

	.p-10 {
		padding: 10px;
	}

	.p-10-0 {
		padding: 10px 0;
	}

	.half {
		width: 50%;
	}

	.center {
		text-align: center;
	}

	.ml-10 {
		margin-left: 10px;
	}

	.text-white {
		color: #fff;
	}

	.w-15 {
		width: 15%;
	}

	.btn1 {
		background-color: #e5e5e5;
		color: #000000;
	}

	.mt-15 {
		margin-top: 15px;
	}

	.agentbtn1 {
		width: 130px;
		border: 1px solid #a71f24;
	}

	.agentbtn2 {
		width: 130px;
		background-color: #a71f24;
	}

	.fontSize-24 {
		font-size: 24px;
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

	.w-40 {
		width: 40%;
	}

	.text-right {
		text-align: right;
	}

	.lineH-40 {
		line-height: 40px;
	}

	.bg-f7 {
		background-color: #f7f7f7;

	}

	.activeColor {
		color: #a71f24;
	}
</style>
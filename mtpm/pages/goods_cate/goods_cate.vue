<template>
	<view>
		<view style="padding: 10px" v-if="SpecialEvent.length">
			<view class="timed-section-hd">
				<text class="timed-section-title">限时拍</text>
				<text v-if="showTimedSessionTip" class="timed-section-meta">本周期最多{{ timedMeta.max_display }}场 · 当前{{ SpecialEvent.length }}场</text>
			</view>
			<view class="timed-section-tip" v-if="showTimedSessionTip && timedSessionTip">{{ timedSessionTip }}</view>
		</view>
		<view style="padding: 10px" >
			<view v-for="(item,index) in SpecialEvent" :key="item.id" style="margin-top: 20px;" @click="todetails(item.id)">
				<view v-if="showTimedSessionTip" class="session-index-tag">第{{ index + 1 }}场</view>
				<view class="relative">
					<image :src="bg1" class="w-full block" mode="widthFix"></image>
					<view style="position: absolute;top: 50%;left: 50%;transform: translate(-50%,-50%);width: 100%;">
						<view style="height: 100%;line-height: 45px;color:#ffffff;font-size: 12px;font-weight: 600;"
							class="flex w-full">
							<view style="width: 42%;height:100%;text-align: center;" v-if="item.status== 0">
								限时拍·预展中·可出价
							</view>
							<view style="width: 42%;height:100%;text-align: center;" v-else-if="item.status== 1">
								限时拍·拍卖中
							</view>
							<view style="width: 42%;height:100%;text-align: center;" v-else>
								限时拍·已结束
							</view>
							<view style="width: 50%;height:100%" v-if="item.status== 0">
								距开始：{{downTime1[index]}}
							</view>
							<view style="width: 50%;height:100%" v-else-if="item.status== 1">
								当前第{{item.number}}/{{item.count}}件
							</view>
						</view>

					</view>
				</view>
				<view class="flex w-full">
					<view style="width: 40%;">
						<image :src="item.big_pic" class="w-full" mode="widthFix"></image>
					</view>
					<view style="width: 58%;padding: 15px;" class="relative">
						<view class="cate_name">{{item.cate_name}}</view>
						<view class="mt-10">
							{{item.count}}件拍品（Lot 1-{{item.count}})
						</view>
						<view class="mt-10">
							{{item.startTime1}}
						</view>
						<view style="position: absolute;bottom: 15px;width: 100%;display: flex;">
							<view
								style="background-color: #eeeeee;color: #999999;padding: 8px;width: auto;font-size: 14px;text-align: center;border-radius: 5px;">
								拍品{{item.count}}</view>
							<view
								style="background-color: #eeeeee;color: #999999;padding: 8px;width: auto;font-size: 14px;text-align: center;border-radius: 5px;margin-left: 10px;">
								围观{{item.view}}</view>
						</view>
					</view>

				</view>
			</view>
		</view>
		
		<view style="padding: 10px">
			<view v-for="(item,index) in synchronous" :key="item.id" style="margin-top: 20px;" @click="toSynchronous(item.id)">
				<view class="relative">
					<image :src="bg1" class="w-full block" mode="widthFix"></image>
					<view style="position: absolute;top: 50%;left: 50%;transform: translate(-50%,-50%);width: 100%;">
						<view style="height: 100%;line-height: 45px;color:#ffffff;font-size: 12px;font-weight: 600;"
							class="flex w-full">
							<view style="width: 42%;height:100%;text-align: center;" v-if="item.status== 0">
								同步拍·预展中·可出价
							</view>
							<view style="width: 42%;height:100%;text-align: center;" v-else-if="item.status== 1">
								同步拍·拍卖中
							</view>
							<view style="width: 42%;height:100%;text-align: center;" v-else>
								同步拍·已结束
							</view>
							<view style="width: 50%;height:100%" v-if="item.status== 0">
								距开始：{{downTime2[index]}}
							</view>
							<view style="width: 50%;height:100%" v-else-if="item.status== 1">
								当前第{{item.number}}/{{item.count}}件
							</view>
						</view>
		
					</view>
				</view>
				<view class="flex w-full">
					<view style="width: 40%;">
						<image :src="item.big_pic" class="w-full" mode="widthFix"></image>
					</view>
					<view style="width: 58%;padding: 15px;" class="relative">
						<view class="cate_name">{{item.cate_name}}</view>
						<view class="mt-10">
							{{item.count}}件拍品（Lot 1-{{item.count}})
						</view>
						<view class="mt-10">
							{{item.startTime1}}
						</view>
						<view style="position: absolute;bottom: 15px;width: 100%;display: flex;">
							<view
								style="background-color: #eeeeee;color: #999999;padding: 8px;width: auto;font-size: 14px;text-align: center;border-radius: 5px;">
								拍品{{item.count}}</view>
							<view
								style="background-color: #eeeeee;color: #999999;padding: 8px;width: auto;font-size: 14px;text-align: center;border-radius: 5px;margin-left: 10px;">
								围观{{item.view}}</view>
						</view>
					</view>
		
				</view>
			</view>
		</view>
		
		<pageFooter @newDataStatus="newDataStatus"></pageFooter>
	</view>
</template>

<script>
	import pageFooter from '@/components/pageFooter/index.vue';
	import {
		getcatList,
		getProductslist,
		synchronization,
		parseTimedCatResponse
	} from '@/api/store.js';
	import {
		DEFAULT_TIMED_META,
		resolveTimedTipForDisplay,
		shouldShowTimedSessionTip,
	} from '@/utils/timedAuctionMeta.js';
	export default {
		computed: {
			showTimedSessionTip() {
				return shouldShowTimedSessionTip(this.SpecialEvent.length, this.timedMeta);
			},
			timedSessionTip() {
				if (!this.showTimedSessionTip) {
					return '';
				}
				return resolveTimedTipForDisplay(this.SpecialEvent.length, this.timedMeta.tip);
			},
		},
		data() {
			return {
				SpecialEvent: [],
				timedMeta: Object.assign({}, DEFAULT_TIMED_META),
				intervalId: null,
				bg1: "https://mtpm.billionwz.com/statics/images/bg1.png",
				downTime1: [''],
				timer: null,
				isActive: false,
				synchronous: [], 
				intervalIds: null,
				downTime2: [''],
				shows: 0
			}
		},
		onShow() {
			this.isActive = true;
			this.getSpecialEvent();
			this.downTime();
			this.startTimer();
			this.getsynchronization();
			this.downTimes();
			//this.getPlateNumberdetails(0);
			if (!this.$store.getters.isLogin) {
				console.log(1);
			}
		},
		onHide() {
			this.isActive = false;

			//console.log(this.isActive)
		},
		methods: {
			newDataStatus(val, num) {
				this.isFooter = val ? true : false;
				this.pdHeight = num;
			},

			downTime() {
				const that = this;

				this.intervalId = setInterval(() => {
					const newDownTime1 = [];
					for (let i = 0; i < that.SpecialEvent.length; i++) {
						var time = Date.now();
						that.downTime1[i] = ``;
						var downtime = that.SpecialEvent[i].startTime * 1000 - time;
						if (downtime <= 0) {
							downtime = 0;
							that.getSpecialEvent();
							clearInterval(that.intervalId)
							that.intervalId = null; // 重要：清除后重置引用
						}
						const d = Math.floor(downtime / 86400000);
						const h = Math.floor((downtime % 86400000) / 3600000);
						const m = Math.floor((downtime % 3600000) / 60000);
						const s = Math.floor((downtime % 60000) / 1000);

						newDownTime1.push(`${d}天${h}时${m}分${s}秒`);
					}
					that.downTime1 = newDownTime1; // 替换整个数组

				}, 1000);
			},
			
			downTimes() {
				const that = this;
			
				this.intervalIds = setInterval(() => {
					const newDownTime1 = [];
					for (let i = 0; i < that.synchronous.length; i++) {
						var time = Date.now();
						that.downTime2[i] = ``;
						var downtime = that.synchronous[i].startTime * 1000 - time;
						if (downtime <= 0) {
							downtime = 0;
							that.getsynchronization();
							clearInterval(that.intervalIds)
							that.intervalIds = null; // 重要：清除后重置引用
						}
						const d = Math.floor(downtime / 86400000);
						const h = Math.floor((downtime % 86400000) / 3600000);
						const m = Math.floor((downtime % 3600000) / 60000);
						const s = Math.floor((downtime % 60000) / 1000);
			
						newDownTime1.push(`${d}天${h}时${m}分${s}秒`);
					}
					that.downTime2 = newDownTime1; // 替换整个数组
			
				}, 1000);
			},

			// 启动定时器
			startTimer() {
				this.timer = setInterval(() => {
					this.getSpecialEvent(); // 每30秒调用一次fetchData方法
				}, 1000); // 30秒
			},

			getSpecialEvent() {
				if (this.isActive == false) {
					return;
				}
				let that = this;
				getcatList().then((res) => {
					const parsed = parseTimedCatResponse(res);
					that.SpecialEvent = parsed.list;
					that.timedMeta = parsed.meta;
					//console.log(that.SpecialEvent);
					for (let i = 0; i < that.SpecialEvent.length; i++) {



						const date = new Date(that.SpecialEvent[i].startTime * 1000);
						//let year = date.getFullYear();
						let month = ('0' + (date.getMonth() + 1)).slice(-2); // 月份从0开始，需要+1
						let day = ('0' + date.getDate()).slice(-2);
						let hours = ('0' + date.getHours()).slice(-2);
						let minutes = ('0' + date.getMinutes()).slice(-2);
						//let seconds = ('0' + date.getSeconds()).slice(-2);

						that.SpecialEvent[i].startTime1 = `${month}月${day}日 ${hours}:${minutes}`;
						//console.log(that.SpecialEvent[i])
					}
				})
			},

			getsynchronization() {
				const that = this;
				synchronization().then((res) => {
					that.synchronous = res.data;
					console.log(res.data);
					for (let i = 0; i < that.synchronous.length; i++) {



						const date = new Date(that.synchronous[i].startTime * 1000);
						//let year = date.getFullYear();
						let month = ('0' + (date.getMonth() + 1)).slice(-2); // 月份从0开始，需要+1
						let day = ('0' + date.getDate()).slice(-2);
						let hours = ('0' + date.getHours()).slice(-2);
						let minutes = ('0' + date.getMinutes()).slice(-2);
						//let seconds = ('0' + date.getSeconds()).slice(-2);

						that.synchronous[i].startTime1 = `${month}月${day}日 ${hours}:${minutes}`;
						//console.log(that.SpecialEvent[i])
					}
					//console.log(that.SpecialEvent[i])
				})
			},


			todetails(index) {
				uni.navigateTo({
					url: "/pages/goods_cate/goods_cate1?id=" + index,
				})
			},
			
			toSynchronous(index){
				uni.navigateTo({
					url: "/pages/goods_cate/synchronouslist?id=" + index,
				})
			}
		}
	}
</script>
<style scoped lang="scss">
	.flex {
		display: flex;
		justify-content: space-between;
	}

	page {
		font-family: '仿宋';
	}

	.w-full {
		width: 100%;
	}

	.relative {
		position: relative;
	}

	.mt-10 {
		margin-top: 10px;
	}


	.block {
		display: block;
	}

	.cate_name {
		font-weight: 600;
		color: #000000;
	}

	.timed-section-hd {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 8px;
	}

	.timed-section-title {
		font-size: 16px;
		font-weight: 600;
		color: #000;
	}

	.timed-section-meta {
		font-size: 12px;
		color: #666;
	}

	.timed-section-tip {
		font-size: 12px;
		color: #999;
		line-height: 1.5;
		margin-bottom: 4px;
	}

	.session-index-tag {
		display: inline-block;
		margin-bottom: 6px;
		padding: 2px 8px;
		font-size: 12px;
		color: #fff;
		background: #c8161d;
		border-radius: 4px;
	}
</style>
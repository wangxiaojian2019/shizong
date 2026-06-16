<template>
	<!-- 带背景轮播图 -->
	<view class="swiperBg" :style="[swiperBgStyle]">
		<template v-if="dataConfig.swiperConfig.list.length">
			<view class="colorBg" :style="[colorBgStyle]"></view>
			<view class="swiper">
				<swiper :style="'height:'+ imageH +'rpx;'" :autoplay="true" :previous-margin="swiperMargin"
					:next-margin="swiperMargin" :circular="circular" :interval="interval" :duration="duration"
					@change="bannerfun" v-if="imageH">
					<swiper-item v-for="(item,index) in dataConfig.swiperConfig.list" :key="index">
						<view @click="goDetail(item)" class="swiper-item"
							:style="[itemStyle,active == index?activeStyle:'']">
							<image :src="item.img" mode="aspectFill" class="image" :style="[imageStyle]"
								@click="previewImage(item.img)" @longpress="handleLongPress" show-menu-by-longpress>
							</image>
						</view>
					</swiper-item>
				</swiper>
				<view class="noPic" v-else>{{ $t(`图片加载中`) }}...</view>
				<!--<view class="dot acea-row" :style="[dotStyle]">
					<view class="progress" v-if="dataConfig.docConfig.tabVal == 2" :style="[progressWidth,dotItemStyle]">
						<view class="inner" :style="[progressValue,dotItemActiveStyle]"></view>
					</view>
					<view class="acea-row" :class="{ small: dataConfig.docConfig.tabVal == 1, line: dataConfig.docConfig.tabVal == 3 }" v-else>
						<view class="dot-item" v-for="(item, index) in dataConfig.swiperConfig.list" :key="index" :class="{ active: active == index }"
							:style="[active == index ? dotItemActiveStyle : dotItemStyle]"></view>
					</view>
				</view>-->
				<view style="padding: 10px">
					<view v-if="SpecialEvent.length" class="timed-section-hd">
						<text class="timed-section-title">限时拍</text>
						<text v-if="showTimedSessionTip" class="timed-section-meta">本周期最多{{ timedMeta.max_display }}场 · 当前{{ SpecialEvent.length }}场</text>
					</view>
					<view v-if="showTimedSessionTip && timedSessionTip" class="timed-section-tip">{{ timedSessionTip }}</view>
					<view v-for="(item,index) in SpecialEvent" :key="item.id" style="margin-top: 20px;" @click="todetails(item.id)">
						<view v-if="showTimedSessionTip" class="session-index-tag">第{{ index + 1 }}场</view>
						<view style="position: relative;">
							<image :src="bg1" style="width: 100%;display: block;" mode="widthFix"></image>
							<view
								style="position: absolute;top: 50%;left: 50%;transform: translate(-50%,-50%);width: 100%;">
								<view style="width: 100%;height: 100%;color:#ffffff;font-size: 12px;font-weight: 600;"
									class="flex">
									<view style="width: 42%;height:100%;text-align: center;" v-if="item.status== 0">
										限时拍·预展中·可出价
									</view>
									<view style="width: 42%;height:100%;text-align: center;"
										v-else-if="item.status== 1">
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
						<view style="width:100%;" class="flex">
							<view style="width: 40%;">
								<image :src="item.big_pic" style="width: 100%;" mode="widthFix"></image>
							</view>
							<view style="width: 58%;padding: 15px;position: relative;">
								<view style="font-weight: 600;color: #000000;">{{item.cate_name}}</view>
								<view style="margin-top: 10px;">
									{{item.count}}件拍品（Lot 1-{{item.count}})
								</view>
								<view style="margin-top: 10px;">
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
				
			</view>

		</template>
	</view>
</template>

<script>
	import {
		DEFAULT_TIMED_META,
		resolveTimedTipForDisplay,
		shouldShowTimedSessionTip,
	} from '@/utils/timedAuctionMeta.js';
	export default {
		name: 'swiperBg',
		props: {
			dataConfig: {
				type: Object,
				default: () => {}
			},
			isSortType: {
				type: String | Number,
				default: 0
			},
			SpecialEvent: {
				type: Array,
				default: () => []
			},
			timedMeta: {
				type: Object,
				default: () => Object.assign({}, DEFAULT_TIMED_META),
			},
			synchronous: {
				type: Array,
				default: () => []
			},
		},
		data() {
			return {
				circular: true,
				autoplay: true,
				interval: 3000,
				duration: 500,
				imgUrls: [], //图片轮播数据
				bgColor: '', //轮播背景颜色
				marginTop: 0, //组件上边距
				paddinglr: 0, //轮播左右边距
				docConfig: 0, //指示点样式
				imgConfig: 0, //是否为圆角
				imageH: 0,
				isColor: 0,
				txtStyle: 0,
				dotColor: '',
				current: 1, //数字指示器当前
				active: 0, //一般指示器当前
				swiperMargin: '',
				bg1: "https://mtpm.billionwz.com/statics/images/bg1.png",
				downTime1: [
					'', ''
				],
				intervalId: null,
				isActive: false,
				intervalIds: null,
				downTime2: [
					'', ''
				],
				shows: 0
			};
		},

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
			itemStyle() {
				let val = this.dataConfig.imgConfig.val;
				let num = 1;
				if (this.dataConfig.styleConfig.tabVal == 2) {
					num = !val ? 1 : 0.9;
				}
				return {
					transform: `scale(${num})`
				}
			},
			activeStyle() {
				let val = this.dataConfig.imgConfig.val;
				let num = 1;
				if (this.dataConfig.styleConfig.tabVal == 2) {
					num = !val ? 1 : (1 - val / 400);
				}
				return {
					transform: `scale(${num})`
				}
			},
			swiperBgStyle() {
				return {
					padding: `${this.dataConfig.topConfig.val * 2}rpx ${this.dataConfig.prConfig.val * 2}rpx ${this.dataConfig.bottomConfig.val * 2}rpx`,
					marginTop: `${this.dataConfig.mbConfig.val * 2}rpx`,
				};
			},
			colorBgStyle() {
				let styleObject = {
					'background': this.dataConfig.bgColor.color[0].item,
				};
				if (this.dataConfig.styleConfig.tabVal == 1) {
					styleObject['height'] = '50%';
				}
				return styleObject;
			},
			imageStyle() {
				let borderRadius = `${this.dataConfig.filletImg.val * 2}rpx`;
				if (this.dataConfig.filletImg.type) {
					borderRadius =
						`${this.dataConfig.filletImg.valList[0].val * 2}rpx ${this.dataConfig.filletImg.valList[1].val * 2}rpx ${this.dataConfig.filletImg.valList[3].val * 2}rpx ${this.dataConfig.filletImg.valList[2].val * 2}rpx`
				}
				return {
					borderRadius: borderRadius
				};
			},
			dotStyle() {
				let styleObject = {};
				if (this.dataConfig.docPosition.tabVal) {
					styleObject['justify-content'] = this.dataConfig.docPosition.tabVal == 1 ? 'center' : 'flex-end';
				}
				if (this.dataConfig.styleConfig.tabVal == 2) {
					styleObject['padding'] = '0 100rpx'
					styleObject['bottom'] = '32rpx'
				}
				return styleObject;
			},
			dotItemStyle() {
				let styleObject = {};
				if (this.dataConfig.toneConfig.tabVal) {
					styleObject['background'] = this.dataConfig.dotBgColor.color[0].item;
				}
				return styleObject;
			},
			dotItemActiveStyle() {
				let styleObject = {};
				if (this.dataConfig.toneConfig.tabVal) {
					styleObject['background'] = this.dataConfig.dotColor.color[0].item;
				}
				return styleObject;
			},
			progressWidth() {
				return {
					width: `${this.dataConfig.swiperConfig.list.length * 20}rpx`,
				};
			},
			progressValue() {
				return {
					width: `${this.current / this.dataConfig.swiperConfig.list.length * 100}%`,
				};
			},
		},
		watch: {
			imageH(nVal, oVal) {
				let self = this
				this.imageH = nVal
			},

		},
		created() {
			this.downTime();
			this.downTimes();
			this.imgUrls = this.dataConfig.swiperConfig.list
			if (this.dataConfig.styleConfig.tabVal == 2) {
				this.swiperMargin = '55rpx';
			}

		},
		mounted() {

			if (this.imgUrls.length) {
				let that = this;
				this.$nextTick((e) => {
					uni.getImageInfo({
						src: that.setDomain(that.imgUrls[0].img),
						success: (res) => {
							if (res && res.height > 0) {
								// that.$set(that, 'imageH',
								// 	res.height / res
								// 	.width * 750)
								let height = res.height * ((750 - this.dataConfig.prConfig.val * 4) /
									res.width)
								that.$set(that, 'imageH', height);
							} else {
								that.$set(that, 'imageH', 375);
							}
						},
						fail: function(error) {
							that.$set(that, 'imageH', 375);
						}
					})
				})
			}

		},
		methods: {
			bannerfun(e) {
				this.active = e.detail.current;
				this.current = e.detail.current + 1;
			},
			//替换安全域名
			setDomain: function(url) {
				url = url ? url.toString() : '';
				//本地调试打开,生产请注销
				if (url.indexOf("https://") > -1) return url;
				else return url.replace('http://', 'https://');
			},
			goDetail(url) {
				let urls = url.info[0].value;
				this.$util.JumpPath(urls);
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
							that.$emit('onButtonClick');

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
							that.$emit('onButtonClick1');
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

			todetails(index) {

				uni.navigateTo({
					url: "/pages/goods_cate/goods_cate1?id=" + index,
				})

			},
			previewImage(imageUrl) {
				uni.previewImage({
					urls: [imageUrl], // 要预览的图片地址数组
					current: 0, // 当前显示图片的索引
				});
			},
			toSynchronous(index){
				uni.navigateTo({
					url: "/pages/goods_cate/synchronouslist?id=" + index,
				})
			}
		}
	}
</script>

<style lang="scss">
	.noPic {
		border-radius: 10rpx;
		width: 100%;
		height: 300rpx;
		background-color: #F0F0F0;
		color: #ccc;
		text-align: center;
		line-height: 300rpx;
		font-size: 30rpx;
	}

	.swiperBg {
		position: relative;

		.colorBg {
			position: absolute;
			left: 0;
			top: 0;
			height: 100%;
			width: 100%;
		}

		.swiper {
			z-index: 20;
			position: relative;
			overflow: hidden;

			.dot {
				position: absolute;
				bottom: 20rpx;
				left: 0;
				width: 100%;
				padding: 0 20rpx;

				.dot-item {
					width: 12rpx;
					height: 12rpx;
					border-radius: 6rpx;
					margin-right: 16rpx;
					background: #DDDDDD;

					&:last-child {
						margin-right: 0;
					}

					&.active {
						background: var(--view-theme);
					}
				}

				.small {
					.dot-item {
						width: 10rpx;
						height: 10rpx;
						border-radius: 5rpx;
						margin-right: 8rpx;

						&.active {
							width: 18rpx;
						}
					}
				}

				.line {
					.dot-item {
						width: 20rpx;
						height: 6rpx;
						border-radius: 3rpx;
						margin-right: 10rpx;
					}
				}

				.progress {
					width: 60rpx;
					height: 6rpx;
					border-radius: 3rpx;
					background: #DDDDDD;

					.inner {
						width: 33%;
						height: 6rpx;
						border-radius: 3rpx;
						background: var(--view-theme);
						transition: 0.3s;
					}
				}
			}

			.swiper-item {
				width: 100%;
				height: 100%;
				transform: scale(0.9);
				transition: 0.3s;

				// &.active {
				// 	transform: scale(1);
				// }
			}

			.image {
				width: 100%;
				height: 100%;
			}

			// 圆形指示点
			&.circular {
				/deep/.uni-swiper-dot {
					width: 10rpx !important;
					height: 10rpx !important;
					background: rgba(0, 0, 0, .4) !important
				}

				/deep/.uni-swiper-dot-active {
					background: #fff !important
				}
			}

			// 方形指示点
			&.square {
				/deep/.uni-swiper-dot {
					width: 20rpx !important;
					height: 5rpx !important;
					border-radius: 3rpx;
					background: rgba(0, 0, 0, .4) !important
				}

				/deep/.uni-swiper-dot-active {
					background: #fff !important
				}
			}
		}

		.flex {
			display: flex;
			justify-content: space-between;
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
			margin-bottom: 8px;
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
	}
</style>
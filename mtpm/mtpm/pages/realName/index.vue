<template>
	<view style="padding: 10px;">
		<view class="mt-25">身份证照片</view>
		<view class="w-full flex mt-15 justify-between">
			<view class="cardBox relative" @click="chooseImage(0)" v-if="!humanFace" :style="'height:' + cardH + 'px'">
				<view class="middle" v-if="!isShow">
					<view style="width: 50px;">
						<image src="@/static/images/humanFace.png" class="w-full" mode="widthFix"></image>
					</view>
					<view>
						头像面
					</view>
				</view>
				<view class="middle" v-else>
					<u-loading :show="isShow"></u-loading>
				</view>
			</view>
			<view class="cardBox overflow-hidden" :style="'height:' + cardH + 'px'" v-else>
				<image :src="humanFace" class="w-full" mode="widthFix"></image>
			</view>
			<view class="cardBox relative" @click="chooseImage(1)" v-if="!coatOfArms" :style="'height:' + cardH + 'px'">
				<view class="middle" v-if="!isShow1">
					<view style="width: 50px;">
						<image src="@/static/images/coatOfArms.png" class="w-full" mode="widthFix"></image>
					</view>
					<view>
						国徽面
					</view>
				</view>
				<view class="middle" v-else>
					<u-loading :show="isShow1"></u-loading>
				</view>
			</view>
			<view class="cardBox overflow-hidden" :style="'height:' + cardH + 'px'" v-else>
				<image :src="coatOfArms" class="w-full" mode="widthFix"></image>
			</view>
		</view>

		<view class="w-full mt-35">
			<view class="w-full flex justify-between border-top">
				<view class="w-30">真实姓名</view>

				<view class="w-68">
					<input type="text" disabled class="w-full" placeholder="上传证件照片自动识别" :value="name" />
				</view>
			</view>
			<view class="w-full flex justify-between border-top border-bottom">
				<view class="w-30">证件号码</view>

				<view class="w-68">
					<input type="text" disabled class="w-full" placeholder="上传证件照片自动识别" :value="IdNumber" />
				</view>
			</view>
		</view>

		<view class="m-auto mt-25" style="width: 280px;font-size: 14px;background-color: red">
			<!--<view> 
				<radio></radio>
			</view>
			<view>已阅读并同意</view>
			<view @click="vip_clause()">《用户协议》</view>
			<view @click="privacy()">《隐私协议》</view>-->
			<!--<radio-group >
				<radio :checked="isAgreed" @change="handleAgreementChange" :activeBackgroundColor="bgColor"/> 已阅读并同意<text class="link"
					@click="vip_clause()">《用户协议》</text><text class="link" @click="privacy()">《隐私协议》</text>
			</radio-group>-->
			<u-radio-group v-model="value">
				<u-radio @change="radioChange" name="1" active-color="#a71f24" label-size="24">已阅读并同意<text class="link"
						@click="vip_clause()">《用户协议》</text><text class="link" @click="privacy()">《隐私协议》</text></u-radio>
			</u-radio-group>
		</view>

		<view class="submit">
			<view style="padding: 10px;">
				<view class="w-full realNameBtn" @click="submitRealname()">
					提交认证
				</view>
			</view>

		</view>
	</view>
</template>

<script>
	import {
		userRealname
	} from '@/api/user.js';
	export default {
		data() {
			return {

				name: "", //真实姓名
				IdNumber: "", //证件号码 
				name1: "", //存储真实姓名
				IdNumber1: "", //存储证件号码 
				humanFace: "",
				coatOfArms: "",
				cardH: 0,
				value: '',
				isShow: false,
				isShow1: false
			}
		},
		onLoad() {
			const system = uni.getSystemInfoSync();
			const systemW = (system.windowWidth - 20) * 0.48;

			const num = systemW * 100 / 134;
			const roundedStr = num.toFixed(2); // "3.14"（字符串）

			this.cardH = parseFloat(roundedStr);
			console.log(this.cardH);
		},
		methods: {
			radioChange(e) {
				console.log(e);
			},


			chooseImage(index) {
				if (!this.humanFace && index == 1) {
					uni.showToast({
						title: '请先上传头像面',
						icon: 'none',
						duration: 2000
					});
					return;
				}
				uni.chooseImage({
					count: 1,
					success: (res) => {
						if (index == 0) {
							this.isShow = true;
						} else {
							this.isShow1 = true;
						}
						this.uploadImage(res.tempFilePaths[0], index);
					},
				})
			},

			uploadImage(image, index) {
				var cardSide = "";
				if (index == 0) {
					cardSide = "FRONT";
				} else {
					cardSide = "BACK";
				}

				//console.log(cardSide);
				//console.log(image);
				const that = this;
				uni.uploadFile({
					url: 'https://mtpm.billionwz.com/api/realNameAuth', // 服务器接口地址
					filePath: image, // 要上传的文件路径
					name: 'file', // 后端接收的字段名
					formData: {
						'cardSide': cardSide, // 额外的表单数据
					},

					success: (res) => {
						let responseString = res.data; // 假设返回的是 JSON 字符串

						try {
							let responseObj = JSON.parse(responseString);
							//console.log(responseObj.code); // 200
							//console.log(responseObj.data); // 数组或对象
							if (responseObj.code == 200) {
								if (index == 0) {
									that.IdNumber1 = responseObj.data.IdNum;
									that.name1 = responseObj.data.Name;
									that.humanFace = image;
									that.isShow = false;
								} else {
									that.coatOfArms = image;
									that.IdNumber = that.IdNumber1;
									that.name = that.name1;
									that.isShow1 = false;
									uni.showToast({
										title: responseObj.msg,
										icon: 'none',
										duration: 2000
									});
								}
							} else {
								uni.showToast({
									title: responseObj.msg,
									icon: 'none',
									duration: 2000
								});
							}

						} catch (e) {
							console.error("JSON 解析失败:", e);
						}
					},
					fail: (err) => {
						console.error('上传失败', err);
					}
				});
			},

			privacy() {
				uni.navigateTo({
					url: "/pages/users/privacy/index"
				})
			},

			vip_clause() {
				uni.navigateTo({
					url: "/pages/annex/vip_clause/index"
				})
			},

			submitRealname() {
				const that = this;
				if (!that.name || !that.IdNumber) {
					uni.showToast({
						title: "请上传证件照",
						icon: 'none',
						duration: 2000
					});
					return;
				}

				if (this.value != "1") {
					uni.showToast({
						title: "请确认阅读并同意《用户协议》《隐私协议》",
						icon: 'none',
						duration: 2000
					});
					return;
				}
				//console.log(this.value);
				userRealname(that.name, that.IdNumber).then((res) => {
					uni.showToast({
						title: res.msg,
						icon: 'none',
						duration: 2000
					});
					setTimeout(() => {
						uni.navigateBack({
							delta: 1 // 返回上一页
						});
					}, 2000);
				})
			}
		}
	}
</script>

<style>
	page {
		background-color: #ffffff;
	}

	.w-full {
		width: 100%;
	}

	.m-auto {
		margin: 0 auto;
	}

	.maxh {
		max-height: 100%;
	}

	.overflow-hidden {
		overflow: hidden;
	}


	.relative {
		position: relative;
	}

	.flex {
		display: flex;
	}

	.justify-between {
		justify-content: space-between;
	}

	.mt-15 {
		margin-top: 15px;
	}

	.mt-25 {
		margin-top: 25px;
	}

	.mt-35 {
		margin-top: 35px;
	}

	.cardBox {
		background-color: #f7f7f7;
		width: 48%;
		border-radius: 10rpx;
	}

	.border-top {
		border-top: 2px solid #f7f7f7;
		padding: 20px 10px;
	}

	.border-bottom {
		border-bottom: 2px solid #f7f7f7;
	}

	.w-30 {
		width: 30%;
	}

	.w-68 {
		width: 68%;
	}

	.middle {
		position: absolute;
		top: 50%;
		left: 50%;
		transform: translate(-50%, -50%);
	}

	.submit {
		position: fixed;
		bottom: 0;
		left: 0px;
		right: 0px;
		background-color: #ffffff;
		border-top: 2px solid #f7f7f7;
	}

	.realNameBtn {
		background-color: #a71f24;
		line-height: 60px;
		border-radius: 10px;
		text-align: center;
		color: #ffffff;
	}
</style>
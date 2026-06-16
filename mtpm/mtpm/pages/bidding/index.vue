<template>
	<scroll-view @scrolltolower="loadMore" :lower-threshold="50" style="height: 100vh;" scroll-y="true">
		<view v-for="item in biddingList" class="mt-10" style="background-color: #fff;padding: 20px 10px;">
			<view class="flex justify-between">
				<view style="width: 80%;">
					<view>
						商品名称：<text style="color:  #a71f24;">{{item.name}}</text>
					</view>
					<view class="mt-20 ">
						出价金额：{{item.price}}
						<text class="ml-15" v-if="item.pattern">出价状态: 手动竞价</text>
						<text class="ml-15" v-else>出价状态: 代理竞价</text>
					</view>
				</view>
				<view class="relative">
					<view class="status">
						<text style="color:  #77777;" v-if="item.status == 0">即将开始</text>
						<text style="color:  #a71f24;" v-else-if="item.status == 1">进行中</text>
						<text style="color:  #e7e7e;" v-else>已结束</text>
					</view>

				</view>

			</view>
		</view>
		<view style="padding: 30px 0;">
			<u-loadmore :status="status" />
		</view>
	</scroll-view>
</template>

<script>
	import {
		getBidding
	} from '@/api/store.js'
	export default {
		data() {
			return {
				biddingList: [],
				page: 1,
				//loading: true, // 加载状态
				status: 'loadmore',
			}
		},
		onShow() {
			this.Bidding();
		},
		methods: {
			Bidding() {
				const that = this;
				that.status = 'loading';
				getBidding(that.page).then((res) => {
					
					if(res.data.length>0){
						that.status = 'loadmore';
					}else{
						that.status = 'nomore';
					}
					
					for (let i = 0; i < res.data.length; i++) {


						that.biddingList.push(res.data[i]);
					}
					
				})
			},

			loadMore() {
				this.page = this.page + 1;
				//console.log(this.page);
				this.Bidding();
			}
		}
	}
</script>

<style>
	.flex {
		display: flex;
	}

	.justify-between {
		justify-content: space-between
	}

	.mt-10 {
		margin-top: 10px;
	}

	.mt-20 {
		margin-top: 20px;
	}

	.ml-15 {
		margin-left: 15px;
	}

	.relative {
		position: relative;
		width: 19%;
	}

	.status {
		position: absolute;
		top: 50%;
		left: 50%;
		transform: translate(-50%, -50%);
		font-size: 14px;
		width: 100%;
		text-align: right;
	}
</style>
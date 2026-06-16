<template>
	<view class="p-10">
		<scroll-view style="height: 100vh;" @scrolltolower="loadMore" :lower-threshold="50" scroll-y="true">
			<view v-for="item in productlist" @click="toSpecial(item.id,item.cate_id)" style="margin-top: 10px;">
				<view class="flex">
					<view style="width: 30%;" :style="'height:' + imageH + 'px'">
						<image :src="item.image" class="w-full h-full cover radius-10" mode="aspectFill"></image>
					</view>
					<view style="width: 66%;padding: 8px 0;">
						<view class="bold">{{item.store_name}}</view>
						<view class="lineH-30 font-14 pt-5">结拍价：<text style="color: #a71f24;">{{item.price}}</text></view>
						<view class="lineH-30 font-14">结拍时间：{{item.presale_end_time}}</view>
					
					</view>

					
				</view>
			</view><!-- 加载状态提示 -->
			<view v-if="loading==1" class="loading-text">加载中...</view>
			<view v-if="loading==2" class="loading-text">没有更多数据了</view>
		</scroll-view>
	</view>
</template>

<script>
	import {
		getProductList
	} from '@/api/store.js';

	export default {
		data() {
			return {
				page: 1,
				productlist: [],
				imageH: 0,
				loading: 0,
				fetching: false  // ✅ 新增：请求锁，防止并发调用
			}
		},
		onLoad() {
			const system = uni.getSystemInfoSync();
			this.imageH = system.windowWidth * 0.3 + 15;
		},
		onShow() {
			// ✅ 新增：每次进入页面都重置，防止返回后数据叠加重复
			this.page = 1;
			this.loading = 0;
			this.productlist = [];
			this.fetching = false;
			this.getProductLists();
		},
		methods: {
			getProductLists() {
				// ✅ 新增：防并发锁，上一次请求未完成时不允许重复发起
				if (this.fetching) return;
				this.fetching = true;
				const that = this;

				getProductList(that.page).then((res) => {
					
					// ✅ 新增：用 id 做去重兜底
					const existingIds = new Set(that.productlist.map(item => item.id));

					for (let i = 0; i < res.data.length; i++) {
						// ✅ 新增：跳过已存在的 id
						if (existingIds.has(res.data[i].id)) continue;
						existingIds.add(res.data[i].id);

						const date = new Date(res.data[i].presale_end_time * 1000);
						let year = date.getFullYear();
						let month = ('0' + (date.getMonth() + 1)).slice(-2);
						let day = ('0' + date.getDate()).slice(-2);
						let hours = ('0' + date.getHours()).slice(-2);
						let minutes = ('0' + date.getMinutes()).slice(-2);

						res.data[i].presale_end_time = `${year}年${month}月${day}日 ${hours}:${minutes}`;
						that.productlist.push(res.data[i]);
					}
					
					if(res.data.length == 0){
						that.loading = 2;
					} else {
						that.loading = 0;
					}
				}).finally(() => {
					// ✅ 新增：无论成功或失败，请求结束后释放锁
					that.fetching = false;
				});
			},

			toSpecial(id, cateId) {
				uni.navigateTo({
					url: "/pages/goods_cate/goods_cate2?id=" + id + "&cate_id=" + cateId
				})
			},

			loadMore() {
				// ✅ 修改：fetching=true 时也不触发，防止初始加载时 scroll 抢跑
				if(this.loading == 2 || this.fetching) return;
				this.page = this.page + 1;
				this.loading = 1;
				setTimeout(() => {
					this.getProductLists()
				}, 1000)	
			},
		}
	}
</script>
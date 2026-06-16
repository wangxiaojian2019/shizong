<template>
	<view style="padding: 0 10px;" class="flex">
		<view v-for="item in articleList" class="box" @click="todetaile(item.id)">
			<view>
				<image :src="item.image_input[0]" mode="widthFix" style="width: 100%;"></image>
			</view>
			<view class="one-line color title">
				{{item.title}}
			</view>
			<view class="adddate">
				{{item.add_time}}
			</view>
		</view>
	</view>
</template>

<script>
	import {
		getArticleList,
	} from '@/api/api.js';
	export default {
		data() {
			return {
				articleList: [],
				page: 1,
				limit:6,
				activeCou: 1
			}
		},
		onShow: function() {
			// this.getArticleHot();

			this.page = 1;
			this.articleList = [];
			this.getnewsList();
		},
		
		onReachBottom: function() {
			this.getnewsList();
		},

		methods: {
			getnewsList() {
				let that = this;
				let limit = that.limit;
				let page = that.page;
				let articleList = that.articleList;
				getArticleList(that.activeCou, {
					page: page,
					limit: limit
				}).then(res => {
					let articleListNew = [];
					let len = res.length;
					articleListNew = articleList.concat(res.data);
					that.page++;
					that.$set(that, 'articleList', articleListNew);
					that.status = limit > len;
					that.page = that.page;
					//console.log(res);
					//console.log(that.page);
				});
			},
			
			todetaile(id){
				uni.navigateTo({
					url: "/pages/extension/news_details/index?id="+id,
				})
			}
			
			
		}
	}
</script>

<style>
	page{
		font-family: '仿宋';
	}
	
	.flex{
		display: flex;justify-content: space-between;flex-wrap: wrap;
	}
	
	.box{
		width: 47%;margin-top: 20px;
	}
	
	.one-line {
	    white-space: nowrap; /* 强制文本不换行 */
	    overflow: hidden;    /* 隐藏超出部分 */
	    text-overflow: ellipsis; /* 超出部分显示为省略号 */
	}
	
	.color{
		color: #a71f24
	}
	
	.title{
		font-size: 18px;
		width: 85%;
		padding: 15px 5rpx 5px 5rpx;
		font-weight: bold;
		
	}
	
	.adddate{
		color: #999999;
		padding: 0 5rpx;
		
	}
</style>
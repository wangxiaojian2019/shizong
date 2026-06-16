<template>
	<el-dialog :visible.sync="modal" @closed="onCancel" title="拍卖专场" width="1000" v-loading="spinShow">
		<el-form ref="formDynamic" :model="formDynamic" :rules="rules" class="attrFrom" label-width="120px"
			label-position="right" @submit.native.prevent>
			<el-row :gutter="8">
				<el-col :span="16">
					<el-form-item label="分类名称：" prop="cate_name">
						<el-input placeholder="请输入分类名称" :maxlength="64" v-model="formDynamic.cate_name" />
					</el-form-item>
				</el-col>
				<el-col :span="16">
					<el-form-item label="专场模式：" prop="model">
						<el-radio-group v-model="formDynamic.model">
							<el-radio :label="0">限时拍</el-radio>
							<el-radio :label="1">同步拍</el-radio>
						</el-radio-group>
					</el-form-item>
				</el-col>
				<el-col :span="16">
					<el-form-item label="拍卖时间：" prop="startTime">
						<el-date-picker v-model="formDynamic.startTime" type="datetime" placeholder="选择日期时间">
						</el-date-picker>
					</el-form-item>
				</el-col>
				<el-col :span="16" v-if="formDynamic.model == 0">
					<el-form-item label="延时周期：" prop="delayCycle">
						<el-input placeholder="请输入延时周期" :maxlength="20" v-model="formDynamic.delayCycle"
							:disabled="true" />
					</el-form-item>
				</el-col>
				<el-col :span="16" v-if="formDynamic.model == 1">
					<el-form-item label="出价阶梯：" prop="bidding_ladder">
						<el-radio-group v-model="formDynamic.bidding_ladder" :disabled="true">
							<el-radio :label="0">默认2,5,8</el-radio>
							<el-radio :label="1">小2,5,8</el-radio>
						</el-radio-group>
					</el-form-item>
				</el-col>
				<el-col :span="16" v-if="formDynamic.model == 1">
					<el-form-item label="现场用户id：" prop="sceneId">
						<el-input placeholder="请输入现场用户id" :maxlength="20" v-model="formDynamic.sceneId" />
					</el-form-item>
				</el-col>
				<el-col :span="16">
					<el-form-item label="当前拍品：" prop="number">
						<el-input :maxlength="20" v-model="formDynamic.number" :disabled="true" />
					</el-form-item>
				</el-col>
				<el-col :span="16">
					<el-form-item label="图片：">
						<div class="pictrueBox" v-db-click @click="modalPicTap('dan', 'danTable', 0)">
							<div class="pictrue" v-if="formDynamic.big_pic">
								<img v-lazy="formDynamic.big_pic" />
								<el-input v-model="formDynamic.big_pic" style="display: none"></el-input>
							</div>
							<div class="upLoad acea-row row-center-wrapper" v-else>
								<el-input v-model="formDynamic.big_pic" style="display: none"></el-input>
								<i class="el-icon-picture-outline" style="font-size: 24px"></i>
							</div>
						</div>
					</el-form-item>
				</el-col>
				<el-col :span="16">
					<el-form-item label="专场介绍：" prop="article_id">	
						<el-input v-model="formDynamic.article_id" />
					</el-form-item>
				</el-col>
				<el-col :span="16">
					<el-form-item label="排序：" prop="sort">
						<el-input :maxlength="20" v-model="formDynamic.sort" />
					</el-form-item>
				</el-col>
				<el-col :span="16">
					<el-form-item label="状态：" prop="status">
						<el-radio-group v-model="formDynamic.status" :disabled="true">
							<el-radio :label="0">未开始</el-radio>
							<el-radio :label="1">拍卖中</el-radio>
							<el-radio :label="2">已结拍</el-radio>
						</el-radio-group>
					</el-form-item>
				</el-col>
				<el-col :span="16">
					<el-form-item label="是否展示：" prop="is_show">
						<el-radio-group v-model="formDynamic.is_show" >
							<el-radio :label="0">隐藏</el-radio>
							<el-radio :label="1">显示</el-radio>
						</el-radio-group>
					</el-form-item>
				</el-col>
			</el-row>

		</el-form>
		<span slot="footer" class="dialog-footer">

			<el-button @click="onClose">取消</el-button>
			<!--<el-button @click="" v-if="formDynamic.model==1 && formDynamic.status == 1 && formDynamic.number>1" @click="updateSubmit(0)">上一件</el-button>-->
			<el-button v-if="formDynamic.model==1 && formDynamic.status == 1 && formDynamic.number<formDynamic.count" @click="updateSubmit(1)">下一件</el-button>
			<el-button v-if="formDynamic.model==1 && formDynamic.status == 0" @click="updateSubmit(2)">开始</el-button>
			<el-button v-if="formDynamic.model==1 && formDynamic.status == 1 && formDynamic.number==formDynamic.count" @click="updateSubmit(3)">结拍</el-button>
			<el-button v-if="formDynamic.model==1 && formDynamic.status == 1 && formDynamic.bidding_ladder == 0" @click="updateSubmit(4)">小2,5,8</el-button>
			<el-button v-if="formDynamic.model==1 && formDynamic.status == 1 && formDynamic.bidding_ladder == 1" @click="updateSubmit(5)">默认2,5,8</el-button>
			<el-button type="primary" :loading="modal_loading" @click="handleSubmit()">确定</el-button>

		</span>
	</el-dialog>
</template>

<script>
	import {
		categorySave,categoryupdate,postproduct
	} from '@/api/product';
	export default {

		data() {
			return {
				modal: false,
				spinShow: false,
				formDynamic: {
					cate_name: '',
					startTime: '',
					delayCycle: 120,
					big_pic: '',
					number: 1,
					article_id: 5,
					status: 0,
					is_show: 0,
					sort: 0,
					model: 0,
					bidding_ladder: 0,
					sceneId: 0,
				},
				rules: {

				},
				model: 0,
				modal_loading: false,
			}
		},

		methods: {
			defaultForm() {
				return {
					cate_name: '',
					startTime: '',
					delayCycle: 120,
					big_pic: '',
					number: 1,
					article_id: 5,
					status: 0,
					is_show: 0,
					sort: 0,
					model: 0,
					bidding_ladder: 0,
					sceneId: 0,
				};
			},
			onCancel() {
				this.clear();
			},
			onClose() {
				this.modal = false;
				this.clear();
			},
			clear() {
				this.formDynamic = this.defaultForm();
				this.model = 0;
				this.modal_loading = false;
			},

			getIofo(row) {
				this.formDynamic = Object.assign(this.defaultForm(), row || {});
				if (this.formDynamic.id) {
					this.formDynamic.id = Number(this.formDynamic.id);
				}
				this.model = this.formDynamic.model != null ? Number(this.formDynamic.model) : 0;
			},

			handleSubmit() {
				const that = this;
				const isEdit = !!this.formDynamic.id;
				that.modal_loading = true;
				const req = isEdit
					? categoryupdate(this.formDynamic.id, this.formDynamic, 6)
					: categorySave(this.formDynamic);
				req.then((res) => {
					that.$message.success(res.msg || (isEdit ? '修改成功' : '添加成功'));
					that.modal = false;
					that.clear();
					that.$emit('getCateList');
				}).catch((res) => {
					that.$message.error(res.msg || '操作失败');
				}).finally(() => {
					that.modal_loading = false;
				});
			},
			
			modalPicTap(type, tableName, index) {
				this.$emit('modalPicTap', type, tableName, index);
			},
			setBigPic(url) {
				this.$set(this.formDynamic, 'big_pic', url || '');
			},
			
			updateSubmit(index){
				const that = this;
				var time = Math.floor(Date.now() / 1000);
				
				if(index == 0){
					//上一件
					//var prodcut = this.this.formDynamic.product[this.formDynamic.number]
					this.formDynamic.number = this.formDynamic.number - 1;
					
				}else if(index == 1){
					//下一件
					this.formDynamic.bidding_ladder = 0;
					this.formDynamic.number = this.formDynamic.number + 1;
				}else if(index == 2){
					//开始
					this.formDynamic.bidding_ladder = 0;
					this.formDynamic.status = 1;
				}else if(index == 3){
					//结拍
					this.formDynamic.status = 2;
				}else if(index == 4){
					//结拍
					this.formDynamic.bidding_ladder = 1;
				}else if(index == 5){
					//结拍
					this.formDynamic.bidding_ladder = 0;
				}
				categoryupdate(this.formDynamic.id,this.formDynamic,index).then((res)=>{
					console.log(res);
					if(index==6){
						that.modal = false;
						that.$emit('getCateList');
					}
					
				})
			},

		}
	}
</script>

<style lang="scss" scoped>
	@use '../productAdd/productAdd.scss' as *;
</style>
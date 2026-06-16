<template>
	<el-dialog
		:visible.sync="modal"
		@closed="onClose"
		title="专场配置"
		width="1000px"
		v-loading="spinShow"
		:close-on-click-modal="false"
	>
		<el-form
			ref="formRef"
			:model="formDynamic"
			:rules="rules"
			class="attrFrom"
			label-width="120px"
			label-position="right"
			@submit.native.prevent
		>
			<el-row :gutter="8">
				<el-col :span="16">
					<el-form-item label="专场名称：">
						<el-input v-model="formDynamic.cate_name" disabled />
					</el-form-item>
				</el-col>
				<el-col :span="16">
					<el-form-item label="围观人数：" prop="view">
						<el-input-number
							v-model="formDynamic.view"
							:min="0"
							:max="99999999"
							:precision="0"
							controls-position="right"
							style="width: 100%"
						/>
					</el-form-item>
				</el-col>
				<el-col :span="16">
					<el-form-item>
						<span class="form-tip">小程序专场页展示为「围观 N 次」；用户进入专场后仍会自动累加（每次 +3）。</span>
					</el-form-item>
				</el-col>
			</el-row>
		</el-form>
		<span slot="footer" class="dialog-footer">
			<el-button @click="onClose">取消</el-button>
			<el-button type="primary" :loading="modal_loading" @click="handleSubmit">确定</el-button>
		</span>
	</el-dialog>
</template>

<script>
	import { getCateViewApi, setCateViewApi } from '@/api/product';

	export default {
		name: 'cateViewConfig',
		data() {
			return {
				modal: false,
				spinShow: false,
				modal_loading: false,
				formDynamic: {
					id: 0,
					cate_name: '',
					view: 0,
				},
				rules: {
					view: [
						{ required: true, message: '请输入围观人数', trigger: 'blur' },
						{ type: 'number', min: 0, message: '围观人数不能小于0', trigger: 'blur' },
					],
				},
			};
		},
		methods: {
			defaultForm() {
				return {
					id: 0,
					cate_name: '',
					view: 0,
				};
			},
			open(row) {
				const id = Number(row.id) || 0;
				if (!id) {
					this.$message.error('专场无效');
					return;
				}
				this.modal = true;
				this.spinShow = true;
				this.formDynamic = {
					id,
					cate_name: row.cate_name || '',
					view: 0,
				};
				getCateViewApi(id)
					.then((res) => {
						const data = res.data || {};
						this.formDynamic = {
							id: Number(data.id) || id,
							cate_name: data.cate_name || row.cate_name || '',
							view: data.view != null && data.view !== '' ? Number(data.view) : 0,
						};
						this.$nextTick(() => {
							if (this.$refs.formRef) {
								this.$refs.formRef.clearValidate();
							}
						});
					})
					.catch((res) => {
						this.$message.error(res.msg || '获取围观人数失败');
						this.modal = false;
					})
					.finally(() => {
						this.spinShow = false;
					});
			},
			onClose() {
				this.modal = false;
				this.formDynamic = this.defaultForm();
				this.modal_loading = false;
				this.spinShow = false;
			},
			handleSubmit() {
				this.$refs.formRef.validate((valid) => {
					if (!valid) {
						return;
					}
					const id = this.formDynamic.id;
					if (!id) {
						this.$message.error('专场无效');
						return;
					}
					this.modal_loading = true;
					setCateViewApi(id, { view: this.formDynamic.view })
						.then((res) => {
							const savedView =
								res.data && res.data.view != null ? Number(res.data.view) : this.formDynamic.view;
							this.formDynamic.view = savedView;
							this.$message.success(res.msg || '保存成功');
							this.modal = false;
							this.$emit('saved');
							this.onClose();
						})
						.catch((res) => {
							this.$message.error(res.msg || '保存失败');
						})
						.finally(() => {
							this.modal_loading = false;
						});
				});
			},
		},
	};
</script>

<style lang="scss" scoped>
	@use '../productAdd/productAdd.scss' as *;

	.form-tip {
		color: #909399;
		font-size: 12px;
		line-height: 1.5;
	}

</style>

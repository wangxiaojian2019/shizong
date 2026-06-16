<template>
	<div class="article-manager">
		<el-card :bordered="false" shadow="never" class="ivu-mt" :body-style="{ padding: 0 }">
			<div class="padding-add">
				<el-form ref="artFrom" :model="artFrom" inline label-width="80px" label-position="right"
					@submit.native.prevent>
					<!--<el-form-item label="商品分类：" prop="pid" label-for="pid">
						<el-select v-model="artFrom.pid" placeholder="请选择商品分类" @change="userSearchs" clearable
							class="form_content_width">
							<el-option v-for="item in treeSelect" :value="item.id" :label="item.cate_name"
								:key="item.id">{{
                item.cate_name
              }}</el-option>
						</el-select>
					</el-form-item>-->
					<el-form-item label="是否展示：" label-for="is_show">
						<el-select v-model="artFrom.is_show" placeholder="请选择是否展示" clearable @change="userSearchs"
							class="form_content_width">
							<el-option value="1" label="显示"></el-option>
							<el-option value="0" label="隐藏"></el-option>
						</el-select>
					</el-form-item>
					<el-form-item label="专场名称：" label-for="status2">
						<el-input clearable placeholder="请输入专场名称" v-model="artFrom.cate_name"
							class="form_content_width" />
					</el-form-item>
					<el-form-item>
						<el-button type="primary" v-db-click @click="userSearchs">查询</el-button>
					</el-form-item>
				</el-form>
			</div>
		</el-card>
		<el-card :bordered="false" shadow="never" class="ivu-mt mt16">
			<el-button v-auth="['product-save-cate']" type="primary" class="bnt" v-db-click
				@click="addClass">添加专场</el-button>
			<vxe-table class="mt14" highlight-hover-row :loading="loading" header-row-class-name="false"
				:tree-config="{ children: 'children' }" :data="tableData">
				<vxe-table-column field="id" title="ID" tooltip width="80"></vxe-table-column>
				<vxe-table-column field="cate_name" tree-node title="专场名称" min-width="250"></vxe-table-column>
				<vxe-table-column field="model" title="专场模式" min-width="100">
					<template v-slot="{ row }">
						<el-tag size="small" :type="row.model == 1 ? 'warning' : 'success'">
							{{ row.model == 1 ? '同步拍' : '限时拍' }}
						</el-tag>
					</template>
				</vxe-table-column>
				<!--<vxe-table-column field="pic" title="专场图标" min-width="100">
					<template v-slot="{ row }">
						<div class="tabBox_img" v-viewer v-if="row.pic">
							<img v-lazy="row.pic" />
						</div>
					</template>
				</vxe-table-column>-->
				<vxe-table-column field="view" title="围观人数" min-width="100">
					<template v-slot="{ row }">
						<span>{{ row.view != null ? row.view : 0 }}</span>
					</template>
				</vxe-table-column>
				<vxe-table-column field="sort" title="排序" min-width="100" tooltip="true"></vxe-table-column>
				<vxe-table-column field="is_show" title="是否展示" min-width="120">
					<template v-slot="{ row }">
						<el-switch class="defineSwitch" :active-value="1" :inactive-value="0" v-model="row.is_show"
							:value="row.is_show" @change="onchangeIsShow(row)" size="large" active-text="展示"
							inactive-text="隐藏">
						</el-switch>
					</template>
				</vxe-table-column>
				<vxe-table-column field="date" title="操作" width="180" fixed="right">
					<template v-slot="{ row, index }">
						<a v-db-click @click="edit(row)">编辑</a>
						<el-divider direction="vertical"></el-divider>
						<a v-db-click @click="openConfig(row)">配置</a>
						<el-divider direction="vertical"></el-divider>
						<a v-db-click @click="del(row, '删除专场', index)">删除</a>
					</template>
				</vxe-table-column>
			</vxe-table>
		</el-card>
		<!-- 添加 编辑表单
		<edit-from ref="edits" :FromData="FromData" @submitFail="userSearchs"></edit-from>-->
		<addClass ref="addattr" @getCateList="getList" @modalPicTap="modalPicTap"></addClass>
		<cateViewConfig ref="cateViewConfig" @saved="getList"></cateViewConfig>
		<el-dialog :visible.sync="modalPic" width="950px" scrollable title="上传商品图" :close-on-click-modal="false"
			append-to-body>
			<uploadPictures :isChoice="isChoice" @getPic="getPic" :gridBtn="gridBtn" :gridPic="gridPic"
				v-if="modalPic"></uploadPictures>
		</el-dialog>		
	</div>
</template>

<script>
	import {
		mapState
	} from 'vuex';
	import {
		productListApi,
		productCreateApi,
		productEditApi,
		setShowApi,
		treeListApi
	} from '@/api/product';
	import editFrom from '../../../components/from/from';
	import addClass from './addClass';
	import cateViewConfig from './cateViewConfig';
	import uploadPictures from '@/components/uploadPictures';
	export default {
		name: 'product_productClassify',
		components: {
			editFrom,
			addClass,
			cateViewConfig,
			uploadPictures
		},
		data() {
			return {
				treeSelect: [],
				FromData: null,
				grid: {
					xl: 7,
					lg: 7,
					md: 12,
					sm: 24,
					xs: 24,
				},
				loading: false,
				artFrom: {
					pid: 0,
					is_show: '',
					page: 1,
					cate_name: '',
					limit: 15,
				},
				total: 0,
				tableData: [],
				formDynamic: {
					cate_name: '',
					startTime: '',
					delayCycle: 120,
					big_pic: '',
					number: 1,
					article_id: 0,
					status: 0,
					is_show: 0,
					sort: 0,
					model: 0,
					bidding_ladder: 0,
					sceneId: 0,
					
				},
				formDynamic1: {
					cate_name: '',
					startTime: '',
					delayCycle: 120,
					big_pic: '',
					number: 1,
					article_id: 0,
					status: 0,
					is_show: 0,
					sort: 0,
					model: 0,
					bidding_ladder: 0,
					sceneId: 0,
					
				},
				isChoice: '',
				gridBtn: {
					xl: 4,
					lg: 8,
					md: 8,
					sm: 8,
					xs: 8,
				},
				gridPic: {
					xl: 6,
					lg: 8,
					md: 12,
					sm: 12,
					xs: 12,
				},
				modalPic: false,
				picTit: '',
				tableIndex: 0,
			};
		},
		computed: {
			...mapState('admin/userLevel', ['categoryId']),
		},
		mounted() {
			this.goodsCategory();
			this.getList();
		},
		methods: {
			// 商品分类；
			goodsCategory() {
				treeListApi(0)
					.then((res) => {
						this.treeSelect = res.data;
					})
					.catch((res) => {
						this.$message.error(res.msg);
					});
			},
			// 列表
			getList() {
				console.log(1);
				this.loading = true;
				this.artFrom.is_show = this.artFrom.is_show || '';
				this.artFrom.pid = this.artFrom.pid || '';
				productListApi(this.artFrom)
					.then(async (res) => {
						let data = res.data;
						this.tableData = data.list;
						this.total = data.count;
						this.loading = false;
						//console.log(res.data);
					})
					.catch((res) => {
						this.loading = false;
						this.$message.error(res.msg);
					});
			},
			pageChange(index) {
				this.artFrom.page = index;
				this.getList();
			},
			// 添加
			addClass() {
				this.$refs.addattr.modal = true;
				this.$refs.addattr.getIofo({ ...this.formDynamic1 });
			},
			// 编辑
			edit(row) {
				this.$refs.addattr.modal = true;
				this.$refs.addattr.getIofo({ ...row });
			},
			openConfig(row) {
				this.$refs.cateViewConfig.open(row);
			},
			// 修改状态
			onchangeIsShow(row) {
				let data = {
					id: row.id,
					is_show: row.is_show,
				};
				setShowApi(data)
					.then(async (res) => {
						this.$message.success(res.msg);
					})
					.catch((res) => {
						this.$message.error(res.msg);
					});
			},
			// 下拉树
			handleCheckChange(data) {
				let value = '';
				let title = '';
				this.list = [];
				this.artFrom.pid = 0;
				data.forEach((item, index) => {
					value += `${item.id},`;
					title += `${item.title},`;
				});
				value = value.substring(0, value.length - 1);
				title = title.substring(0, title.length - 1);
				this.list.push({
					value,
					title,
				});
				this.artFrom.pid = value;
				this.getList();
			},
			// 删除
			del(row, tit, num) {
				let delfromData = {
					title: tit,
					num: num,
					url: `product/category/${row.id}`,
					method: 'DELETE',
					ids: '',
				};
				this.$modalSure(delfromData)
					.then((res) => {
						this.$message.success(res.msg);
						this.getList();
					})
					.catch((res) => {
						this.$message.error(res.msg);
					});
			},
			// 表格搜索
			userSearchs() {
				this.artFrom.page = 1;
				this.getList();
			},
			
			// 获取单张图片信息（写入弹窗子组件 formDynamic，而非本页 formDynamic）
			getPic(pc) {
				const picUrl = (pc && (pc.att_dir || pc.satt_dir)) || '';
				if (this.$refs.addattr) {
					this.$refs.addattr.setBigPic(picUrl);
				}
				this.modalPic = false;
			},
			
			modalPicTap(tit, picTit = '', index = 0) {
				this.modalPic = true;
				this.isChoice = tit === 'dan' ? '单选' : '多选';
				this.picTit = picTit;
				this.tableIndex = index;
			},
		},
	};
</script>
<style lang="scss" scoped>
	.treeSel ::v-deep .ivu-select-dropdown-list {
		padding: 0 10px !important;
		box-sizing: border-box;
	}

	.tabBox_img {
		width: 36px;
		height: 36px;
		border-radius: 4px;
		cursor: pointer;

		img {
			width: 100%;
			height: 100%;
		}
	}
</style>
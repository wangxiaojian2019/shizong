<template>
	<div>
		<el-card :bordered="false" shadow="never" class="ivu-mt" :body-style="{ padding: 0 }">
			<div class="padding-add">
				<el-form ref="artFrom" :model="artFrom" label-width="80px" label-position="right" class="tabform"
					@submit.native.prevent inline>
					<el-form-item label="规格搜索1：">
						<el-form-item label="商品分类：" label-for="pid">
							<el-cascader v-model="artFrom.cate_id" size="small" :options="treeSelect"
								:props="{ multiple: false, emitPath: false, checkStrictly: true }" clearable
								class="form_content_width"></el-cascader>
						</el-form-item>
					</el-form-item>
					<el-form-item label="支付状态：">
						<el-select v-model="artFrom.paystatus" clearable placeholder="全部" class="form_content_width">
							<el-option label="未支付" value="0" />
							<el-option label="已支付" value="1" />
							<el-option label="已退款" value="2" />
						</el-select>
					</el-form-item>
					<el-form-item>
						<el-button type="primary" v-db-click @click="userSearchs">查询</el-button>
					</el-form-item>
				</el-form>


			</div>
		</el-card>
		<el-card :bordered="false" shadow="never" class="ivu-mt mt16">
			<!--<el-button v-auth="['product-rule-save']" type="primary" v-db-click @click="addAttr">添加商品规格</el-button>
			<el-button v-auth="['product-product-rule-delete']" v-db-click @click="del(null, '批量删除规格')">批量删除</el-button>-->
			<el-table ref="table" :data="tableList" v-loading="loading" highlight-current-row :row-key="getRowKey"
				@selection-change="handleSelectRow" empty-text="暂无数据" class="mt14">
				<el-table-column type="selection" width="60" :reserve-selection="true"> </el-table-column>
				<el-table-column label="ID" width="80">
					<template slot-scope="scope">
						<span>{{ scope.row.id }}</span>
					</template>
				</el-table-column>
				<el-table-column label="拍卖专场" min-width="130">
					<template slot-scope="scope">
						<span>{{ scope.row.special }}</span>
					</template>
				</el-table-column>
				<el-table-column label="用户信息" min-width="130">
					<template slot-scope="scope">
						<span>{{ scope.row.user_id }} | {{scope.row.name}}</span>
					</template>
				</el-table-column>
				<el-table-column label="用户手机号" min-width="130">
					<template slot-scope="scope">
						<span>{{ scope.row.phone }}</span>
					</template>
				</el-table-column>
				<el-table-column label="号码牌" min-width="130">
					<template slot-scope="scope">
						<span>{{ scope.row.number_plate }}</span>
					</template>
				</el-table-column>
				<el-table-column label="号牌获得类型" min-width="160">
					<template slot-scope="scope">
						<span>{{ scope.row.plate_acquire_label || '暂无号牌' }}</span>
					</template>
				</el-table-column>
				<el-table-column label="剩余竞拍额度" min-width="130">
					<template slot-scope="scope">
						
						<el-input v-model="scope.row.remaining" placeholder="请输入内容" @input="editRemaining" @blur="editnumberPlate(scope.row.user_id,scope.row.cate_id,scope.row.state,scope.row.remaining)"></el-input>
					</template>
				</el-table-column>
				<el-table-column label="状态" min-width="130">
					<template slot-scope="scope">
						<el-switch v-model="scope.row.state" active-color="#13ce66" inactive-color="#ff4949"
							active-text="已分配"
							inactive-text="待分配"
							:disabled="scope.row.plate_status_locked"
							@change="editnumberPlate(scope.row.user_id,scope.row.cate_id,scope.row.state,scope.row.remaining)">
						</el-switch>
					</template>
				</el-table-column>
				<el-table-column label="已中拍" min-width="130">
					<template slot-scope="scope">
						<span>{{ scope.row.stock }}</span>
					</template>
				</el-table-column>
				<!--<el-table-column label="已支付" min-width="130">
					<template slot-scope="scope">
						<span>{{ scope.row.sales }}</span>
					</template>
				</el-table-column>-->
				<el-table-column label="支付状态" min-width="130">
					<template slot-scope="scope">
						<span v-if="scope.row.paystatus==0">未支付</span>
						<span v-else-if="scope.row.paystatus==1">已支付</span>
						<span v-else>已退款</span>
					</template>
				</el-table-column>
				<el-table-column label="操作" min-width="130">
					<template slot-scope="scope">
						<a
							v-if="scope.row.show_refund_btn"
							v-db-click
							@click="confirmRefundDeposit(scope.row)"
						>退款</a>
					</template>
				</el-table-column>

				<!--<el-table-column label="操作" fixed="right" width="120">
					<template slot-scope="scope">
						<a v-db-click @click="edit(scope.row)">编辑</a>
						<el-divider direction="vertical"></el-divider>
						<a v-db-click @click="del(scope.row, '删除规格', scope.$index)">删除</a>
					</template>
				</el-table-column>-->
			</el-table>
			<div class="acea-row row-right page">
				<pagination v-if="total" :total="total" :page.sync="artFrom.page" :limit.sync="artFrom.limit"
					@pagination="getDataList" />
			</div>
		</el-card>
		<add-attr ref="addattr" @getList="userSearchs"></add-attr>
	</div>
</template>

<script>
	import {
		mapState
	} from 'vuex';
	import addAttr from './addAttr';
	import {
		ruleListApi,
		getnumberPlate,
		editPlate,
		cascaderListApi,
		refundOrder
	} from '@/api/product';
	export default {
		name: 'productAttr',
		components: {
			addAttr
		},
		data() {
			return {
				loading: false,
				artFrom: {
					page: 1,
					limit: 20,
					cate_id: 0,
					paystatus: '',
				},
				tableList: [],
				total: 0,
				selectedIds: new Set(), //选中合并项的id
				ids: [],
				multipleSelection: [],
				treeSelect: []
			};
		},
		computed: {
			...mapState('admin/order', ['orderChartType']),
		},
		created() {
			this.getDataList();
			this.goodsCategory();
		},
		methods: {
			getRowKey(row) {
				return row.id;
			},

			goodsCategory() {
				cascaderListApi(1)
					.then((res) => {
						this.treeSelect = res.data;
					})
					.catch((res) => {
						this.$message.error(res.msg);
					});
			},
			//全选和取消全选时触发
			handleSelectAll(selection) {
				if (selection.length === 0) {
					//获取table的数据；
					let data = this.$refs.table.data;
					data.forEach((item) => {
						if (this.selectedIds.has(item.id)) {
							this.selectedIds.delete(item.id);
						}
					});
				} else {
					selection.forEach((item) => {
						this.selectedIds.add(item.id);
					});
				}
				this.$nextTick(() => {
					//确保dom加载完毕
					this.setChecked();
				});
			},
			//  选中某一行
			handleSelectRow(selection) {
				const uniqueArr = [];
				const ids = [];
				for (let i = 0; i < selection.length; i++) {
					const item = selection[i];
					if (!ids.includes(item.id)) {
						uniqueArr.push(item);
						ids.push(item.id);
					}
				}
				this.selectedIds = ids;
				this.multipleSelection = uniqueArr;
				this.$nextTick((e) => {
					this.setChecked();
				});
			},
			setChecked() {
				//将new Set()转化为数组
				this.ids = [...this.selectedIds].join(',');
			},
			// 删除
			del(row, tit) {
				let data = {};
				if (tit === '批量删除规格') {
					if (this.selectedIds.size === 0) return this.$message.warning('请选择要删除的规格！');
					data = {
						ids: this.ids,
					};
				} else {
					data = {
						ids: row.id,
					};
				}
				let delfromData = {
					title: tit,
					num: 0,
					url: `product/product/rule/delete`,
					method: 'DELETE',
					ids: data,
				};
				this.$modalSure(delfromData)
					.then((res) => {
						this.$message.success(res.msg);
						this.getDataList();
					})
					.catch((res) => {
						this.$message.error(res.msg);
					});
			},
			addAttr() {
				this.$refs.addattr.modal = true;
			},
			// 编辑
			edit(row) {
				this.$refs.addattr.modal = true;
				this.$refs.addattr.getIofo(row);
			},
			buildPlateQueryParams() {
				const paystatus = this.artFrom.paystatus;
				return {
					...this.artFrom,
					paystatus:
						paystatus === '' || paystatus === null || paystatus === undefined ? -1 : paystatus,
				};
			},
			// 列表；
			getDataList() {
				//this.loading = true;
				//ruleListApi(this.artFrom)
				//	.then((res) => {
				//		let data = res.data;
				//		this.tableList = data.list;
				//		this.$nextTick(() => {
				//确保dom加载完毕
				//			this.setChecked();
				//		});
				//		this.total = res.data.count;
				//		this.loading = false;
				//	})
				//	.catch((res) => {
				//		this.loading = false;
				//		this.$message.error(res.msg);
				//	});
				this.loading = true;
				//console.log(this.artFrom);
				getnumberPlate(this.buildPlateQueryParams()).then((res) => {
						//console.log(res.data);
						let data = res.data.product;
						this.tableList = data;
						for (let i = 0; i < data.length; i++) {
							const plateNo = String(data[i].number_plate || '');
							data[i].state = plateNo !== '' && plateNo !== '待分配';
						}

						this.$nextTick(() => {
							//确保dom加载完毕
							this.setChecked();
						});
						//this.total = 1;
						this.total = res.data.count;
						console.log(res.data);
						this.loading = false;
					})
					.catch((res) => {
						this.loading = false;
						this.$message.error(res.msg);
					});
			},

			editnumberPlate(user_id, cate_id, state, remaining) {
				const data = {
					user_id: user_id,
					cate_id: cate_id,
					state: state ? 1 : 0,
					remaining: remaining,
				};
				editPlate(data)
					.then((res) => {
						this.$message.success(res.msg || '保存成功');
						this.getDataList();
					})
					.catch((res) => {
						this.$message.error(res.msg || '保存失败');
						this.getDataList();
					});
			},
			// 表格搜索
			userSearchs() {
				this.artFrom.page = 1;
				this.getDataList();
			},

			postRefundOrder(uid, cate_id) {
				const data = { uid, cate_id };
				const that = this;
				refundOrder(data)
					.then((res) => {
						that.$message.success(res.msg || '退款成功');
						that.getDataList();
					})
					.catch((res) => {
						that.$message.error(res.msg || '退款失败');
					});
			},
			confirmRefundDeposit(row) {
				const uid = row.user_id;
				const cateId = row.refund_primary_cate_id || row.cate_id;
				const userLabel = row.name ? `${uid}（${row.name}）` : String(uid);
				const amount = row.plate_acquire_amount || '';
				const amountText = amount !== '' && amount !== null ? `${amount}元` : '';
				const sessionHint = row.refund_primary_cate_id && row.refund_primary_cate_id !== row.cate_id
					? '（同周期各专场号牌将一并收回）'
					: '';
				this.$confirm(
					`确认退还用户 ${userLabel} 的保证金${amountText}？退款后将同步收回同周期号牌并更新各专场状态${sessionHint}。`,
					'退款确认',
					{
						confirmButtonText: '确认退款',
						cancelButtonText: '取消',
						type: 'warning',
					}
				)
					.then(() => {
						this.postRefundOrder(uid, cateId);
					})
					.catch(() => {});
			},
			editRemaining(E){
				console.log(e);
			}
		},
	};
</script>

<style scoped></style>
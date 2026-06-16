<template>
  <div class="divBox">
    <el-card :bordered="false" shadow="never" class="ivu-mt">
      <div slot="header" class="clearfix">
        <span>限时拍拍卖周期</span>
      </div>
      <div class="tips mb16">
        一个拍卖周期须<strong>同时绑定两个不同的限时拍专场</strong>（均需已开启「展示」），两场<strong>共用号牌</strong>。
        <strong>未纳入周期的限时拍专场</strong>仍可按单场正常办牌。已绑定其它周期的专场不可再选；两场均结拍后系统自动解除绑定。
      </div>
      <el-button type="primary" class="mb16" v-db-click @click="openCreate">新建周期</el-button>
      <el-table v-loading="loading" :data="tableData" border stripe>
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column prop="name" label="周期名称" min-width="200" />
        <el-table-column label="专场A" min-width="160">
          <template slot-scope="{ row }">
            <span v-if="row.cate_a_name">{{ row.cate_a_name }}</span>
            <span v-else class="text-muted">未绑定</span>
          </template>
        </el-table-column>
        <el-table-column label="专场B" min-width="160">
          <template slot-scope="{ row }">
            <span v-if="row.cate_b_name">{{ row.cate_b_name }}</span>
            <span v-else class="text-muted">未绑定</span>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="200" fixed="right">
          <template slot-scope="{ row }">
            <a v-db-click @click="openEdit(row)">编辑</a>
            <el-divider direction="vertical"></el-divider>
            <a v-db-click @click="openBind(row)">绑定专场</a>
            <el-divider direction="vertical"></el-divider>
            <a v-db-click @click="handleDelete(row)" class="text-danger">删除</a>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-dialog
      :title="formMode === 'edit' ? '编辑拍卖周期' : '新建拍卖周期'"
      :visible.sync="formVisible"
      width="480px"
      :close-on-click-modal="false"
    >
      <el-form ref="formRef" :model="formData" :rules="formRules" label-width="100px">
        <el-form-item label="周期名称" prop="name">
          <el-input v-model="formData.name" placeholder="如：5月双场限时拍" maxlength="64" />
        </el-form-item>
      </el-form>
      <span slot="footer">
        <el-button @click="formVisible = false">取消</el-button>
        <el-button type="primary" :loading="formSaving" v-db-click @click="submitForm">确定</el-button>
      </span>
    </el-dialog>

    <el-dialog
      :title="'绑定限时拍专场 · 周期#' + (currentSession.id || '')"
      :visible.sync="bindVisible"
      width="640px"
      :close-on-click-modal="false"
    >
      <div class="tips mb12">
        须选择<strong>两个不同</strong>的限时拍专场，且均为「展示」状态；已绑定其它周期的专场不会出现在列表中。
      </div>
      <el-alert
        v-if="bindVisible && !bindOptionPoolLoading && bindSelectOptions.length === 0"
        type="warning"
        :closable="false"
        class="mb12"
        title="当前没有可选的限时拍专场"
        description="请先在「拍卖专场」中创建限时拍并开启展示，并确认未被其它周期占用。"
      />
      <el-form label-width="80px" v-loading="bindOptionPoolLoading">
        <el-form-item label="专场A" required>
          <el-select
            v-model="bindForm.cate_a_id"
            filterable
            clearable
            placeholder="选择专场A"
            style="width: 100%"
          >
            <el-option
              v-for="item in optionsForA"
              :key="'a-' + item.id"
              :label="optionLabel(item)"
              :value="item.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="专场B" required>
          <el-select
            v-model="bindForm.cate_b_id"
            filterable
            clearable
            placeholder="选择专场B"
            style="width: 100%"
          >
            <el-option
              v-for="item in optionsForB"
              :key="'b-' + item.id"
              :label="optionLabel(item)"
              :value="item.id"
            />
          </el-select>
        </el-form-item>
      </el-form>
      <span slot="footer">
        <el-button @click="bindVisible = false">取消</el-button>
        <el-button type="primary" :loading="bindSaving" v-db-click @click="submitBind">保存绑定</el-button>
      </span>
    </el-dialog>
  </div>
</template>

<script>
import {
  getAuctionSessionListApi,
  getTimedCategoryOptionsApi,
  createAuctionSessionApi,
  updateAuctionSessionApi,
  deleteAuctionSessionApi,
  bindAuctionSessionApi,
} from '@/api/auctionSession';

export default {
  name: 'AuctionSessionConfig',
  data() {
    return {
      loading: false,
      tableData: [],
      formVisible: false,
      formMode: 'create',
      formSaving: false,
      editingId: 0,
      formData: {
        name: '',
      },
      formRules: {
        name: [{ required: true, message: '请输入周期名称', trigger: 'blur' }],
      },
      bindVisible: false,
      bindSaving: false,
      currentSession: {},
      bindForm: {
        cate_a_id: null,
        cate_b_id: null,
      },
      bindOptionPool: [],
      bindOptionPoolLoading: false,
      bindSelectOptions: [],
    };
  },
  computed: {
    optionsForA() {
      const b = Number(this.bindForm.cate_b_id) || 0;
      return this.bindSelectOptions.filter((item) => Number(item.id) !== b);
    },
    optionsForB() {
      const a = Number(this.bindForm.cate_a_id) || 0;
      return this.bindSelectOptions.filter((item) => Number(item.id) !== a);
    },
  },
  mounted() {
    this.loadList();
    this.loadBindOptionPool();
  },
  methods: {
    loadList() {
      this.loading = true;
      getAuctionSessionListApi()
        .then((res) => {
          this.tableData = res.data || [];
        })
        .catch(() => {
          this.tableData = [];
        })
        .finally(() => {
          this.loading = false;
        });
    },
    /** 进入页面只请求一次：可绑定专场池（session_id=0，不含已被任意周期占用的） */
    loadBindOptionPool(force = false) {
      if (this.bindOptionPoolLoading) {
        return Promise.resolve();
      }
      if (!force && this.bindOptionPool.length) {
        return Promise.resolve();
      }
      this.bindOptionPoolLoading = true;
      return getTimedCategoryOptionsApi(0)
        .then((res) => {
          this.bindOptionPool = Array.isArray(res.data) ? res.data : [];
        })
        .catch((err) => {
          this.bindOptionPool = [];
          this.$message.error(err.msg || '加载限时拍专场列表失败，请检查权限或稍后重试');
        })
        .finally(() => {
          this.bindOptionPoolLoading = false;
        });
    },
    /** 打开绑定弹窗：本地合并当前周期已绑专场，不再请求接口 */
    buildBindSelectOptions(row) {
      const excludeIds = new Set();
      (this.tableData || []).forEach((session) => {
        if (Number(session.id) === Number(row.id)) {
          return;
        }
        [session.cate_a_id, session.cate_b_id].forEach((id) => {
          const n = Number(id) || 0;
          if (n > 0) {
            excludeIds.add(n);
          }
        });
      });
      const options = (this.bindOptionPool || []).filter((item) => {
        const id = Number(item.id) || 0;
        return id > 0 && !excludeIds.has(id);
      });
      const seen = new Set(options.map((item) => Number(item.id)));
      const appendCurrent = (id, name) => {
        const n = Number(id) || 0;
        if (n <= 0 || seen.has(n)) {
          return;
        }
        seen.add(n);
        options.unshift({
          id: n,
          cate_name: (name || '').replace(/\(#\d+\)$/, '').trim() || `专场#${n}`,
          status_text: '',
          is_show_text: '已绑定本周期',
        });
      };
      appendCurrent(row.cate_a_id, row.cate_a_name);
      appendCurrent(row.cate_b_id, row.cate_b_name);
      return options;
    },
    resetFormData() {
      this.formData = { name: '' };
      this.editingId = 0;
    },
    openCreate() {
      this.formMode = 'create';
      this.resetFormData();
      this.formVisible = true;
      this.$nextTick(() => {
        if (this.$refs.formRef) this.$refs.formRef.clearValidate();
      });
    },
    openEdit(row) {
      this.formMode = 'edit';
      this.editingId = row.id;
      this.formData = {
        name: row.name || '',
      };
      this.formVisible = true;
      this.$nextTick(() => {
        if (this.$refs.formRef) this.$refs.formRef.clearValidate();
      });
    },
    submitForm() {
      this.$refs.formRef.validate((valid) => {
        if (!valid) return;
        this.formSaving = true;
        const payload = { name: this.formData.name };
        const request =
          this.formMode === 'edit'
            ? updateAuctionSessionApi(this.editingId, payload)
            : createAuctionSessionApi(payload);
        request
          .then((res) => {
            this.$message.success(res.msg || (this.formMode === 'edit' ? '修改成功' : '创建成功'));
            this.formVisible = false;
            this.loadList();
          })
          .finally(() => {
            this.formSaving = false;
          });
      });
    },
    handleDelete(row) {
      this.$confirm(`确定删除周期「${row.name}」吗？将自动解绑已关联专场。`, '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning',
      })
        .then(() => deleteAuctionSessionApi(row.id))
        .then((res) => {
          this.$message.success(res.msg || '删除成功');
          this.loadList();
        })
        .catch(() => {});
    },
    openBind(row) {
      this.currentSession = row;
      this.bindForm.cate_a_id = row.cate_a_id ? Number(row.cate_a_id) : null;
      this.bindForm.cate_b_id = row.cate_b_id ? Number(row.cate_b_id) : null;
      if (!this.bindForm.cate_a_id && Array.isArray(row.cate_ids) && row.cate_ids[0]) {
        this.bindForm.cate_a_id = Number(row.cate_ids[0]);
      }
      if (!this.bindForm.cate_b_id && Array.isArray(row.cate_ids) && row.cate_ids[1]) {
        this.bindForm.cate_b_id = Number(row.cate_ids[1]);
      }
      const showBindDialog = () => {
        this.bindSelectOptions = this.buildBindSelectOptions(row);
        this.bindVisible = true;
      };
      if (this.bindOptionPoolLoading) {
        this.loadBindOptionPool().then(showBindDialog);
      } else {
        showBindDialog();
      }
    },
    optionLabel(item) {
      const parts = [`#${item.id} ${item.cate_name}`];
      if (item.status_text) parts.push(item.status_text);
      if (item.is_show_text) parts.push(item.is_show_text);
      return parts.join(' · ');
    },
    submitBind() {
      const cateA = Number(this.bindForm.cate_a_id) || 0;
      const cateB = Number(this.bindForm.cate_b_id) || 0;
      if (!cateA || !cateB) {
        this.$message.warning('请选择专场A和专场B');
        return;
      }
      if (cateA === cateB) {
        this.$message.warning('专场A与专场B不能相同');
        return;
      }
      this.bindSaving = true;
      bindAuctionSessionApi({
        session_id: this.currentSession.id,
        cate_a_id: cateA,
        cate_b_id: cateB,
      })
        .then((res) => {
          this.$message.success(res.msg || '绑定成功');
          this.bindVisible = false;
          this.loadList();
          this.loadBindOptionPool(true);
        })
        .catch((err) => {
          this.$message.error(err.msg || '绑定失败');
        })
        .finally(() => {
          this.bindSaving = false;
        });
    },
  },
};
</script>

<style scoped>
.tips {
  color: #606266;
  font-size: 13px;
  line-height: 1.6;
}
.text-muted {
  color: #909399;
}
.text-danger {
  color: #f56c6c;
}
.mb4 {
  margin-bottom: 4px;
}
.mb12 {
  margin-bottom: 12px;
}
.mb16 {
  margin-bottom: 16px;
}
.mr8 {
  margin-right: 8px;
}
</style>

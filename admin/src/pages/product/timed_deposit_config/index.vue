<template>
  <div class="divBox">
    <el-card :bordered="false" shadow="never" class="ivu-mt">
      <div slot="header" class="clearfix">
        <span>限时拍保证金设置</span>
      </div>
      <el-form ref="formRef" :model="form" :rules="rules" label-width="160px" v-loading="loading">
        <el-form-item label="保证金金额（元）" prop="deposit_amount">
          <el-input-number
            v-model="form.deposit_amount"
            :min="1"
            :precision="2"
            :step="100"
            controls-position="right"
            style="width: 240px"
          />
          <div v-if="effectiveAmount != null" class="effective-hint">
            当前线上生效金额：<strong>{{ effectiveAmount }}</strong> 元（保存成功后立即更新）
          </div>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :loading="saving" v-db-click @click="handleSave">保存</el-button>
        </el-form-item>
        <el-form-item>
          <div class="tips">
            <p>保证金按<strong>用户账户</strong>管理，与拍卖周期、号牌相互独立。</p>
            <p>缴足后在未退款前，可参拍限时拍专场；余额不足按差额补缴（need_pay）；退款完成后须重新缴纳。</p>
            <p>绑定双场周期时：号牌周期内只办一次；保证金仍走上述独立账户规则。</p>
          </div>
        </el-form-item>
      </el-form>
    </el-card>
  </div>
</template>

<script>
import { getTimedDepositConfigApi, saveTimedDepositConfigApi } from '@/api/timedDeposit';

export default {
  name: 'TimedDepositConfig',
  data() {
    return {
      loading: false,
      saving: false,
      form: {
        deposit_amount: 1,
      },
      effectiveAmount: null,
      rules: {
        deposit_amount: [
          { required: true, message: '请输入保证金金额', trigger: 'blur' },
          {
            type: 'number',
            min: 1,
            message: '金额须大于 0',
            trigger: 'change',
          },
        ],
      },
    };
  },
  mounted() {
    this.loadConfig();
  },
  methods: {
    loadConfig() {
      this.loading = true;
      getTimedDepositConfigApi()
        .then((res) => {
          const amount = res.data && res.data.deposit_amount != null ? res.data.deposit_amount : 1;
          const num = Number(amount);
          this.form.deposit_amount = num;
          this.effectiveAmount = num;
        })
        .catch((err) => {
          this.$message.error(err.msg || '读取配置失败');
        })
        .finally(() => {
          this.loading = false;
        });
    },
    handleSave() {
      this.$refs.formRef.validate((valid) => {
        if (!valid) return;
        this.saving = true;
        saveTimedDepositConfigApi({
          deposit_amount: this.form.deposit_amount,
        })
          .then((res) => {
            this.$message.success(res.msg || '保存成功');
            this.loadConfig();
          })
          .finally(() => {
            this.saving = false;
          });
      });
    },
  },
};
</script>

<style scoped>
.tips {
  color: #909399;
  font-size: 13px;
  line-height: 1.8;
}
.tips p {
  margin: 0 0 6px;
}
.effective-hint {
  margin-top: 8px;
  color: #606266;
  font-size: 13px;
}
</style>

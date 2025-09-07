<template>
  <div>
    <Select
      v-model="selected"
      :options="options"
      :label="t('tenant')"
      classInput="h-8"
      :aria-label="t('tenant')"
    />
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, watch, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useTenantStore } from '@/stores/tenant';
import Select from '@dc/components/Select';

const { t } = useI18n();
const tenantStore = useTenantStore();
const selected = ref<string | number | null>(tenantStore.currentTenantId);
const options = computed(() =>
  tenantStore.tenants.map((t) => ({ value: String(t.id), label: t.name })),
);

onMounted(async () => {
  if (!tenantStore.tenants.length) {
    await tenantStore.loadTenants();
  }
});

watch(selected, (val) => {
  const tenant = tenantStore.tenants.find((t) => String(t.id) === String(val));
  if (tenant) {
    tenantStore.setTenant(tenant.id);
  }
});
</script>

<style scoped>
.classinput {
  min-width: 8rem;
}
</style>

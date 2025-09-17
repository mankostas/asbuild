<template>
  <div>
    <Select
      v-model="selected"
      :options="options"
      classInput="h-8"
      :placeholder="t('tenant')"
      :aria-label="t('tenant')"
    />
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, watch, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useTenantStore } from '@/stores/tenant';
import { useAuthStore } from '@/stores/auth';
import Select from '@dc/components/Select';

const { t } = useI18n();
const tenantStore = useTenantStore();
const authStore = useAuthStore();
const props = defineProps({
  impersonate: { type: Boolean, default: true },
});
// Initialize the selection with the current tenant. For super admins that
// start without a tenant context this ensures the "Super Admin" option is
// considered selected so choosing it again later is a no-op.
const selected = ref<string | number | null>(
  tenantStore.currentTenantId || 'super_admin',
);
const options = computed(() =>
  tenantStore.tenants.map((t) => ({ value: String(t.id), label: t.name })),
);

onMounted(async () => {
  if (!tenantStore.tenants.length) {
    await tenantStore.loadTenants();
  }
});

watch(
  () => tenantStore.currentTenantId,
  (id) => {
    selected.value = id || 'super_admin';
  },
);

watch(selected, async (val) => {
  // Selecting the synthetic "Super Admin" tenant should not trigger an
  // impersonation request. Instead reset the tenant context.
  if (String(val) === 'super_admin') {
    tenantStore.setTenant('');
    if (props.impersonate && authStore.isImpersonating) {
      await authStore.unimpersonate();
      window.location.reload();
    }
    return;
  }

  const tenant = tenantStore.tenants.find((t) => String(t.id) === String(val));
  if (tenant && String(tenant.id) !== tenantStore.currentTenantId) {
    if (props.impersonate) {
      await authStore.impersonate(tenant.id, tenant.name);
      window.location.reload();
    } else {
      tenantStore.setTenant(tenant.id);
    }
  }
});
</script>

<style scoped>
.classinput {
  min-width: 8rem;
}
</style>

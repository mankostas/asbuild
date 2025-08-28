<template>
  <select
    v-model="selected"
    @change="onChange"
    class="border rounded px-2 py-1 text-sm"
  >
    <option
      v-for="t in tenantStore.tenants"
      :key="t.id"
      :value="t.id"
    >
      {{ t.name }}
    </option>
  </select>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useTenantStore } from '@/stores/tenant';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';

const tenantStore = useTenantStore();
const authStore = useAuthStore();
const router = useRouter();
const selected = ref(tenantStore.currentTenantId);

onMounted(async () => {
  if (!tenantStore.tenants.length) {
    await tenantStore.loadTenants();
  }
});

async function onChange() {
  const tenant = tenantStore.tenants.find(
    (t) => String(t.id) === String(selected.value),
  );
  if (tenant) {
    await authStore.impersonate(tenant.id, tenant.name);
    router.replace({ name: 'dashboard' });
  }
}
</script>

<style scoped>
select {
  min-width: 8rem;
}
</style>

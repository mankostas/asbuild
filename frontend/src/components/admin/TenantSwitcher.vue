<template>
  <div>
    <span class="block mb-1 text-sm">Tenant</span>
    <select
      id="tenant-switcher"
      v-model="selected"
      class="border rounded px-2 py-1 text-sm"
      aria-label="Tenant"
      @change="onChange"
    >
      <option
        v-for="t in tenantStore.tenants"
        :key="t.id"
        :value="t.id"
      >
        {{ t.name }}
      </option>
    </select>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useTenantStore } from '@/stores/tenant';
import { useAuthStore } from '@/stores/auth';
const tenantStore = useTenantStore();
const authStore = useAuthStore();
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
    window.location.reload();
  }
}
</script>

<style scoped>
select {
  min-width: 8rem;
}
</style>

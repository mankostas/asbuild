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

const tenantStore = useTenantStore();
const selected = ref(tenantStore.currentTenantId);

onMounted(async () => {
  if (!tenantStore.tenants.length) {
    await tenantStore.loadTenants();
  }
});

function onChange() {
  tenantStore.setTenant(selected.value);
}
</script>

<style scoped>
select {
  min-width: 8rem;
}
</style>

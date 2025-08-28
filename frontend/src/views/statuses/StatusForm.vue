<template>
  <div>
    <form @submit.prevent="onSubmit" class="max-w-md grid gap-4">
      <div v-if="auth.isSuperAdmin">
        <label class="block font-medium mb-1" for="tenant">Tenant</label>
        <select id="tenant" v-model="tenantId" class="border rounded p-2 w-full">
          <option value="">Global</option>
          <option v-for="t in tenantStore.tenants" :key="t.id" :value="t.id">{{ t.name }}</option>
        </select>
      </div>
      <div>
        <label class="block font-medium mb-1" for="name">Name<span class="text-red-600">*</span></label>
        <input id="name" v-model="name" class="border rounded p-2 w-full" />
      </div>
      <div v-if="serverError" class="text-red-600 text-sm">{{ serverError }}</div>
      <button
        type="submit"
        class="px-4 py-2 bg-blue-600 text-white rounded"
        :disabled="!canSubmit"
      >Save</button>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '@/services/api';
import { useAuthStore } from '@/stores/auth';
import { useTenantStore } from '@/stores/tenant';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const tenantStore = useTenantStore();

const name = ref('');
const serverError = ref('');
const tenantId = ref<string | number | ''>('');

const isEdit = computed(() => route.name === 'statuses.edit');

onMounted(async () => {
  if (auth.isSuperAdmin && !tenantStore.tenants.length) {
    await tenantStore.loadTenants();
  }
  if (isEdit.value) {
    const { data } = await api.get(`/statuses/${route.params.id}`);
    name.value = data.name;
    tenantId.value = data.tenant_id || '';
  }
});

const canSubmit = computed(() => !!name.value);

async function onSubmit() {
  serverError.value = '';
  if (!canSubmit.value) return;
  const payload: any = { name: name.value };
  if (auth.isSuperAdmin) {
    payload.tenant_id = tenantId.value || undefined;
  }
  try {
    if (isEdit.value) {
      await api.patch(`/statuses/${route.params.id}`, payload);
    } else {
      await api.post('/statuses', payload);
    }
    router.push({ name: 'statuses.list' });
  } catch (e: any) {
    serverError.value = e.message || 'Failed to save';
  }
}
</script>

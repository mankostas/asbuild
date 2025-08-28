<template>
  <div>
    <form @submit.prevent="onSubmit" class="max-w-md grid gap-4">
      <div>
        <label class="block font-medium mb-1" for="name">Name<span class="text-red-600">*</span></label>
        <input id="name" v-model="name" class="border rounded p-2 w-full" />
      </div>
      <div>
        <label class="block font-medium mb-1" for="slug">Slug<span class="text-red-600">*</span></label>
        <input id="slug" v-model="slug" class="border rounded p-2 w-full" />
      </div>
      <div>
        <label class="block font-medium mb-1" for="abilities">Abilities (comma separated)</label>
        <input id="abilities" v-model="abilities" class="border rounded p-2 w-full" />
      </div>
      <div v-if="auth.isSuperAdmin">
        <VueSelect label="Tenant">
          <vSelect
            v-model="tenantId"
            :options="tenantOptions"
            :reduce="(t: any) => t.id"
            label="name"
          />
        </VueSelect>
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
import { useNotify } from '@/plugins/notify';
import { useAuthStore } from '@/stores/auth';
import { useTenantStore } from '@/stores/tenant';
import VueSelect from '@/components/ui/Select/VueSelect.vue';
import vSelect from 'vue-select';

const route = useRoute();
const router = useRouter();
const notify = useNotify();
const auth = useAuthStore();
const tenantStore = useTenantStore();

const name = ref('');
const slug = ref('');
const abilities = ref('');
const tenantId = ref<string>(auth.isSuperAdmin ? '' : tenantStore.currentTenantId);
const serverError = ref('');

const tenantOptions = computed(() => [
  { id: '', name: 'Global' },
  ...tenantStore.tenants.map((t: any) => ({ id: t.id, name: t.name })),
]);

const isEdit = computed(() => route.name === 'roles.edit');

onMounted(async () => {
  if (auth.isSuperAdmin && !tenantStore.tenants.length) {
    await tenantStore.loadTenants();
  }
  if (isEdit.value) {
    const { data } = await api.get(`/roles/${route.params.id}`);
    if (data.name === 'SuperAdmin') {
      notify.error('Cannot modify SuperAdmin role');
      router.push({ name: 'roles.list' });
      return;
    }
    name.value = data.name;
    slug.value = data.slug || '';
    abilities.value = (data.abilities || []).join(', ');
    tenantId.value = data.tenant_id || '';
  }
});

const canSubmit = computed(() => !!name.value && !!slug.value && name.value !== 'SuperAdmin');

async function onSubmit() {
  serverError.value = '';
  if (!canSubmit.value) return;
  const payload: any = {
    name: name.value,
    slug: slug.value,
    abilities: abilities.value
      .split(',')
      .map((t) => t.trim())
      .filter((t) => t),
  };
  if (auth.isSuperAdmin) {
    payload.tenant_id = tenantId.value || null;
  }
  try {
    if (isEdit.value) {
      await api.patch(`/roles/${route.params.id}`, payload);
    } else {
      await api.post('/roles', payload);
    }
    router.push({ name: 'roles.list' });
  } catch (e: any) {
    serverError.value = e.message || 'Failed to save';
  }
}
</script>

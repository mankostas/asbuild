<template>
  <div v-if="canAccess">
    <form class="max-w-md grid gap-4" @submit.prevent="onSubmit">
      <div v-if="auth.isSuperAdmin">
        <span class="block font-medium mb-1">Tenant</span>
        <select
          id="tenant"
          v-model="tenantId"
          class="border rounded p-2 w-full"
          aria-label="Tenant"
        >
          <option value="">Global</option>
          <option
            v-for="t in tenantStore.tenants"
            :key="t.id"
            :value="String(t.id)"
          >
            {{ t.name }}
          </option>
        </select>
        <div v-if="errors.tenant_id" class="text-red-600 text-sm">
          {{ errors.tenant_id }}
        </div>
      </div>
      <div>
        <span class="block font-medium mb-1">Name<span class="text-red-600">*</span></span>
        <input
          id="name"
          v-model="name"
          class="border rounded p-2 w-full"
          aria-label="Name"
        />
        <div v-if="errors.name" class="text-red-600 text-sm">{{ errors.name }}</div>
      </div>
      <div>
        <span class="block font-medium mb-1">Slug</span>
        <input
          id="slug"
          v-model="slug"
          class="border rounded p-2 w-full"
          aria-label="Slug"
        />
        <div v-if="errors.slug" class="text-red-600 text-sm">{{ errors.slug }}</div>
      </div>
      <div>
        <span class="block font-medium mb-1">Color</span>
        <input
          id="color"
          v-model="color"
          type="color"
          class="h-10 w-20 rounded border border-slate-200"
          aria-label="Color"
        />
        <div v-if="errors.color" class="text-red-600 text-sm">{{ errors.color }}</div>
      </div>
      <div>
        <span class="block font-medium mb-1">Position</span>
        <input
          id="position"
          v-model.number="position"
          type="number"
          class="border rounded p-2 w-full"
          aria-label="Position"
        />
        <div v-if="errors.position" class="text-red-600 text-sm">{{ errors.position }}</div>
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
import { ref, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api, { extractFormErrors } from '@/services/api';
import { useAuthStore, can } from '@/stores/auth';
import { useTenantStore } from '@/stores/tenant';
import { useForm } from 'vee-validate';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const tenantStore = useTenantStore();

const name = ref('');
const slug = ref('');
const color = ref('');
const position = ref(0);
const serverError = ref('');
const tenantId = ref('');

const isEdit = computed(() => route.name === 'taskStatuses.edit');
const canAccess = computed(() =>
  isEdit.value
    ? can('task_statuses.update') || can('task_statuses.manage')
    : can('task_statuses.create') || can('task_statuses.manage'),
);

async function loadTenantsIfNeeded() {
  if (auth.isSuperAdmin && tenantStore.tenants.length === 0) {
    await tenantStore.loadTenants();
  }
}

async function loadStatus() {
  await loadTenantsIfNeeded();
  if (isEdit.value) {
    const res = await api.get(`/task-statuses/${route.params.id}`);
    const data = res.data.data || res.data; // handle resource-wrapped and plain responses
    name.value = data.name;
    slug.value = data.slug || '';
    color.value = data.color || '';
    position.value = data.position || 0;
    tenantId.value = data.tenant_id ? String(data.tenant_id) : '';
  } else {
    name.value = '';
    slug.value = '';
    color.value = '';
    position.value = 0;
    tenantId.value = '';
  }
}

watch(
  () => route.fullPath,
  () => {
    loadStatus();
  },
  { immediate: true },
);

watch(
  () => auth.isSuperAdmin,
  async (val) => {
    if (val) {
      await loadTenantsIfNeeded();
    }
  },
);

const canSubmit = computed(() => !!name.value);

const { handleSubmit, setErrors, errors } = useForm();

const onSubmit = handleSubmit(async () => {
  serverError.value = '';
  if (!canSubmit.value) return;
  const payload: any = {
    name: name.value,
    slug: slug.value || undefined,
    color: color.value || null,
    position: position.value,
  };
  if (auth.isSuperAdmin) {
    payload.tenant_id = tenantId.value === '' ? null : Number(tenantId.value);
  }
  try {
    if (isEdit.value) {
      await api.patch(`/task-statuses/${route.params.id}`, payload);
    } else {
      await api.post('/task-statuses', payload);
    }
    router.push({ name: 'taskStatuses.list' });
  } catch (e: any) {
    const errs = extractFormErrors(e);
    if (Object.keys(errs).length) {
      setErrors(errs);
    } else {
      serverError.value = e.message || 'Failed to save';
    }
  }
});
</script>

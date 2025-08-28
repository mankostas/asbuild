<template>
  <div v-if="can('roles.manage')">
    <form @submit.prevent="onSubmit" class="max-w-md grid gap-4">
      <div>
        <label class="block font-medium mb-1" for="name">Name<span class="text-red-600">*</span></label>
        <input id="name" v-model="name" class="border rounded p-2 w-full" />
        <div v-if="errors.name" class="text-red-600 text-sm">{{ errors.name }}</div>
      </div>
      <div>
        <label class="block font-medium mb-1" for="slug">Slug<span class="text-red-600">*</span></label>
        <input id="slug" v-model="slug" class="border rounded p-2 w-full" />
        <div v-if="errors.slug" class="text-red-600 text-sm">{{ errors.slug }}</div>
      </div>
      <div>
        <label class="block font-medium mb-1" for="level">Level<span class="text-red-600">*</span></label>
        <input
          id="level"
          type="number"
          min="0"
          v-model.number="level"
          class="border rounded p-2 w-full"
        />
        <div v-if="errors.level" class="text-red-600 text-sm">{{ errors.level }}</div>
      </div>
      <VueSelect label="Abilities" :error="errors.abilities">
        <vSelect v-model="abilities" :options="abilityOptions" multiple />
      </VueSelect>
      <div v-if="auth.isSuperAdmin">
        <VueSelect label="Tenant" :error="errors.tenant_id">
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
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api, { extractFormErrors } from '@/services/api';
import { useNotify } from '@/plugins/notify';
import { useAuthStore, can } from '@/stores/auth';
import { useTenantStore } from '@/stores/tenant';
import VueSelect from '@/components/ui/Select/VueSelect.vue';
import vSelect from 'vue-select';
import { useForm } from 'vee-validate';

const route = useRoute();
const router = useRouter();
const notify = useNotify();
const auth = useAuthStore();
const tenantStore = useTenantStore();

const name = ref('');
const slug = ref('');
const abilities = ref<string[]>([]);
const abilityOptions = ref<string[]>([]);
const level = ref<number>(0);
const tenantId = ref<string>(auth.isSuperAdmin ? '' : tenantStore.currentTenantId);
const serverError = ref('');

const tenantOptions = computed(() => [
  { id: '', name: 'Global' },
  ...tenantStore.tenants.map((t: any) => ({ id: t.id, name: t.name })),
]);

const isEdit = computed(() => route.name === 'roles.edit');

async function loadRole() {
  const { data } = await api.get(`/roles/${route.params.id}`);
  if (data.name === 'SuperAdmin') {
    notify.error('Cannot modify SuperAdmin role');
    router.push({ name: 'roles.list' });
    return;
  }
  name.value = data.name;
  slug.value = data.slug || '';
  abilities.value = data.abilities || [];
  tenantId.value = data.tenant_id || '';
  level.value = data.level ?? 0;
}

onMounted(async () => {
  try {
    const { data: abilityData } = await api.get('/lookups/abilities');
    abilityOptions.value = abilityData;
  } catch (e) {
    abilityOptions.value = [];
  }
  if (auth.isSuperAdmin && !tenantStore.tenants.length) {
    await tenantStore.loadTenants();
  }
  if (isEdit.value) {
    await loadRole();
  }
});

watch(
  () => route.params.id,
  () => {
    if (isEdit.value) {
      loadRole();
    }
  },
);

const canSubmit = computed(() =>
  !!name.value &&
  !!slug.value &&
  name.value !== 'SuperAdmin' &&
  typeof level.value === 'number' &&
  !isNaN(level.value) &&
  level.value >= 0
);

const { handleSubmit, setErrors, errors } = useForm();

const onSubmit = handleSubmit(async () => {
  serverError.value = '';
  if (!canSubmit.value) return;
  const payload: any = {
    name: name.value,
    slug: slug.value,
    abilities: abilities.value,
    level: level.value,
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
    const errs = extractFormErrors(e);
    if (Object.keys(errs).length) {
      setErrors(errs);
    } else {
      serverError.value = e.message || 'Failed to save';
    }
  }
});
</script>

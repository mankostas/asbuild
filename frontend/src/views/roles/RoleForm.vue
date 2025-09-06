<template>
  <div v-if="canAccess">
    <form class="max-w-md grid gap-4" @submit.prevent="onSubmit">
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
        <span class="block font-medium mb-1">Slug<span class="text-red-600">*</span></span>
        <input
          id="slug"
          v-model="slug"
          class="border rounded p-2 w-full"
          aria-label="Slug"
        />
        <div v-if="errors.slug" class="text-red-600 text-sm">{{ errors.slug }}</div>
      </div>
      <div>
        <span class="block font-medium mb-1">Level<span class="text-red-600">*</span></span>
        <input
          id="level"
          v-model.number="level"
          type="number"
          min="0"
          class="border rounded p-2 w-full"
          aria-label="Level"
        />
        <div v-if="errors.level" class="text-red-600 text-sm">{{ errors.level }}</div>
      </div>
      <div v-if="auth.isSuperAdmin">
        <VueSelect label="Tenant" :error="errors.tenant_id">
          <template #default="{ inputId }">
            <vSelect
              :id="inputId"
              v-model="tenantId"
              :options="tenantOptions"
              :reduce="(t: any) => t.id"
              label="name"
            />
          </template>
        </VueSelect>
      </div>
      <VueSelect
        v-if="!auth.isSuperAdmin || tenantId !== null"
        label="Abilities"
        :error="errors.abilities"
      >
        <template #default="{ inputId }">
          <vSelect
            :id="inputId"
            v-model="abilities"
            :options="abilityOptions"
            multiple
            label="label"
            :reduce="(a: any) => a.value"
          />
        </template>
      </VueSelect>
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
import { useRolesStore } from '@/stores/roles';
import VueSelect from '@/components/ui/Select/VueSelect.vue';
import vSelect from 'vue-select';
import { TENANT_HEADER } from '@/config/app';
import { useForm } from 'vee-validate';
import { featureMap } from '@/constants/featureMap';

const route = useRoute();
const router = useRouter();
const notify = useNotify();
const auth = useAuthStore();
const tenantStore = useTenantStore();
const rolesStore = useRolesStore();

const name = ref('');
const slug = ref('');
const abilities = ref<string[]>([]);
const abilityOptions = ref<{ label: string; value: string }[]>([]);
const level = ref<number>(0);
const tenantId = ref<string | null>(
  auth.isSuperAdmin ? null : tenantStore.currentTenantId,
);
const serverError = ref('');

const tenantFeatures = computed(() => {
  if (tenantId.value) {
    const tenant = tenantStore.tenants.find(
      (t: any) => String(t.id) === tenantId.value,
    );
    return tenant?.features || [];
  }
  return [];
});
const tenantFeatureAbilities = computed(() =>
  tenantId.value
    ? tenantStore.tenantAllowedAbilities(tenantId.value)
    : {},
);

const tenantOptions = computed(() => [
  { id: '', name: 'Global' },
  ...tenantStore.tenants.map((t: any) => ({ id: t.id, name: t.name })),
]);

const isEdit = computed(() => route.name === 'roles.edit');
const canAccess = computed(() =>
  isEdit.value
    ? can('roles.update') || can('roles.manage')
    : can('roles.create') || can('roles.manage'),
);

async function loadRole() {
  const { data } = await api.get(`/roles/${route.params.id}`);
  const role = data.data || data; // handle resource-wrapped and plain responses
  if (role.name === 'SuperAdmin') {
    notify.error('Cannot modify SuperAdmin role');
    router.push({ name: 'roles.list' });
    return;
  }
  name.value = role.name;
  slug.value = role.slug || '';
  abilities.value = role.abilities || [];
  tenantId.value = role.tenant_id || '';
  level.value = role.level ?? 0;
}

onMounted(async () => {
  if (auth.isSuperAdmin) {
    await tenantStore.loadTenants();
  }
  if (isEdit.value) {
    await loadRole();
  }
  if (!auth.isSuperAdmin || tenantId.value !== null) {
    await loadAbilityOptions();
  }
});

async function loadAbilityOptions() {
  try {
    const params = tenantId.value ? { forTenant: 1 } : undefined;
    const headers = tenantId.value
      ? { [TENANT_HEADER]: tenantId.value }
      : undefined;
    const { data } = await api.get('/lookups/abilities', { params, headers });
    const perFeature = tenantFeatureAbilities.value;
    const allowed = new Set(
      Object.keys(perFeature).length
        ? Object.values(perFeature).flat()
        : tenantFeatures.value.flatMap(
            (f: string) => featureMap[f]?.abilities || [],
          ),
    );
    abilityOptions.value = (data || [])
      .filter((a: string) => (allowed.size ? allowed.has(a) : true))
      .map((a: string) => ({
        label: a,
        value: a,
      }));
  } catch (e) {
    abilityOptions.value = [];
  }
}

watch(tenantId, (val) => {
  if (val !== null) {
    loadAbilityOptions();
  }
});

watch(
  () => tenantStore.tenantAllowedAbilities(tenantId.value || ''),
  () => {
    if (!auth.isSuperAdmin || tenantId.value !== null) {
      loadAbilityOptions();
    }
  },
  { deep: true },
);
watch(tenantFeatures, () => {
  if (!auth.isSuperAdmin || tenantId.value !== null) {
    loadAbilityOptions();
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
    await rolesStore.fetch();
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

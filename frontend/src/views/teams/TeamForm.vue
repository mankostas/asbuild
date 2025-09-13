<template>
  <div v-if="canAccess">
    <form class="grid gap-4 max-w-lg" @submit.prevent="submit">
      <VueSelect v-if="auth.isSuperAdmin" label="Tenant">
        <template #default="{ inputId }">
          <vSelect
            :id="inputId"
            v-model="tenantId"
            :options="tenant.tenants"
            :reduce="(t: any) => t.id"
            label="name"
          />
        </template>
        <div v-if="errors.tenant_id" class="text-red-600 text-sm">{{ errors.tenant_id }}</div>
      </VueSelect>
      <Textinput v-model="form.name" label="Name" :error="errors.name" />
      <Textinput v-model="form.description" label="Description" :error="errors.description" />
      <VueSelect label="Employees">
        <template #default="{ inputId }">
          <vSelect
            :id="inputId"
            v-model="selectedEmployees"
            :options="employeeOptions"
            :reduce="(e: any) => e.id"
            label="name"
            multiple
          />
        </template>
      </VueSelect>

      <Card v-if="availableFeatures.length" class="rounded-2xl" bodyClass="p-6">
        <h3 class="text-lg font-semibold mb-4">Feature Grants</h3>
        <div class="grid gap-4">
          <div v-for="f in availableFeatures" :key="f" class="space-y-1">
            <span class="font-medium">{{ featureMap[f].label }}</span>
            <VueSelect>
              <template #default="{ inputId }">
                <vSelect
                  :id="inputId"
                  v-model="featureGrants[f]"
                  :options="featureMap[f].abilities"
                  multiple
                />
              </template>
            </VueSelect>
          </div>
        </div>
      </Card>
      <Button
        type="submit"
        :text="isEdit ? 'Save' : 'Create'"
        btnClass="btn-dark"
      />
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api, { extractFormErrors, extractData } from '@/services/api';
import Textinput from '@/components/ui/Textinput/index.vue';
import VueSelect from '@/components/ui/Select/VueSelect.vue';
import Button from '@/components/ui/Button/index.vue';
import Card from '@/components/ui/Card/index.vue';
import vSelect from 'vue-select';
import { useTeamsStore } from '@/stores/teams';
import { can, useAuthStore } from '@/stores/auth';
import { useTenantStore } from '@/stores/tenant';
import { featureMap } from '@/constants/featureMap';
import {
  ensureHiddenRole,
  assignHiddenRoleToTeam,
  removeHiddenRoleFromTeam,
} from '@/services/featureGrants';
import { useForm } from 'vee-validate';

const route = useRoute();
const router = useRouter();
const teamsStore = useTeamsStore();
const auth = useAuthStore();
const tenant = useTenantStore();

const isEdit = computed(() => route.name === 'teams.edit');
const canAccess = computed(() =>
  isEdit.value
    ? can('teams.update') || can('teams.manage')
    : can('teams.create') || can('teams.manage'),
);

const form = ref({
  name: '',
  description: '',
});

const tenantId = ref<string | number | ''>('');

const employeeOptions = ref<any[]>([]);
const selectedEmployees = ref<number[]>([]);
const featureGrants = ref<Record<string, string[]>>({});
const hiddenRoles = ref<Record<string, any>>({});

const availableFeatures = computed(() =>
  auth.features.filter((f) => featureMap[f]),
);

watch(tenantId, async (newTenant, oldTenant) => {
  if (!auth.isSuperAdmin || newTenant === oldTenant) return;
  const previousTenant = tenant.tenantId;
  tenant.setTenant(String(newTenant));
  await loadEmployees();
  selectedEmployees.value = [];
  hiddenRoles.value = {};
  featureGrants.value = {} as Record<string, string[]>;
  if (isEdit.value) {
    await loadTeam();
  }
  availableFeatures.value.forEach((f) => {
    if (!featureGrants.value[f]) featureGrants.value[f] = [];
  });
  tenant.setTenant(previousTenant);
});

async function loadEmployees() {
  const { data } = await api.get('/employees');
  employeeOptions.value = extractData(data);
}

async function loadTeam() {
  if (!isEdit.value) return;
  const { data } = await api.get(`/teams/${route.params.id}`);
  form.value.name = data.name || '';
  form.value.description = data.description || '';
  tenantId.value = data.tenant_id ? String(data.tenant_id) : '';
  selectedEmployees.value = (data.employees || []).map((e: any) => e.id);
  hiddenRoles.value = {};
  featureGrants.value = {} as Record<string, string[]>;
  (data.roles || []).forEach((r: any) => {
    if (r.slug?.startsWith('__fg__')) {
      const parts = r.slug.split('__').filter(Boolean);
      const feature = parts[2];
      hiddenRoles.value[feature] = r;
      featureGrants.value[feature] = r.abilities || [];
    }
  });
}

const { handleSubmit, setErrors, errors } = useForm();

const submit = handleSubmit(async () => {
  const payload = {
    name: form.value.name,
    description: form.value.description,
  };
  if (auth.isSuperAdmin) {
    payload.tenant_id = tenantId.value === '' ? undefined : Number(tenantId.value);
  }
  const selectedTenant = auth.isSuperAdmin
    ? String(tenantId.value)
    : tenant.tenantId;
  const previousTenant = tenant.tenantId;
  tenant.setTenant(selectedTenant);
  let teamId: number;
  try {
    if (isEdit.value) {
      const updated = await teamsStore.update(Number(route.params.id), payload);
      teamId = updated.id;
    } else {
      const created = await teamsStore.create(payload);
      teamId = created.id;
    }
    await teamsStore.syncEmployees(teamId, selectedEmployees.value);
    await reconcileFeatureGrants(teamId, selectedTenant);
    router.push({ name: 'teams.list' });
  } catch (e: any) {
    setErrors(extractFormErrors(e));
  } finally {
    tenant.setTenant(previousTenant);
  }
});

onMounted(async () => {
  await loadEmployees();
  if (auth.isSuperAdmin) {
    await tenant.loadTenants({ per_page: 100 });
  }
  await loadTeam();
  availableFeatures.value.forEach((f) => {
    if (!featureGrants.value[f]) featureGrants.value[f] = [];
  });
});

async function reconcileFeatureGrants(
  teamId: number,
  selectedTenant: string | number,
) {
  for (const feature of availableFeatures.value) {
    const selected = featureGrants.value[feature] || [];
    const existing = hiddenRoles.value[feature];
    if (selected.length) {
      const role = await ensureHiddenRole(selectedTenant, feature, selected);
      if (!existing || existing.id !== role.id) {
        await assignHiddenRoleToTeam(teamId, role.id);
        if (existing && existing.id !== role.id) {
          await removeHiddenRoleFromTeam(teamId, existing.id);
        }
      }
    } else if (existing) {
      await removeHiddenRoleFromTeam(teamId, existing.id);
    }
  }
}
</script>


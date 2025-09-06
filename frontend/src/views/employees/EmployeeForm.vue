<template>
  <div v-if="canAccess">
    <form class="grid gap-4 max-w-lg" @submit.prevent="submit">
      <Textinput v-model="form.name" label="Name" />
      <Textinput v-model="form.email" label="Email" type="email" />
      <Textinput v-model="form.phone" label="Phone" />
      <Textinput v-model="form.address" label="Address" />
      <VueSelect label="Roles">
        <template #default="{ inputId }">
          <vSelect
            :id="inputId"
            v-model="form.roles"
            :options="roleOptions"
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
        :text="isEdit ? 'Save' : 'Invite'"
        btnClass="btn-dark"
      />
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '@/services/api';
import Textinput from '@/components/ui/Textinput/index.vue';
import VueSelect from '@/components/ui/Select/VueSelect.vue';
import Button from '@/components/ui/Button/index.vue';
import Card from '@/components/ui/Card/index.vue';
import vSelect from 'vue-select';
import { can, useAuthStore } from '@/stores/auth';
import { useTenantStore } from '@/stores/tenant';
import { featureMap } from '@/constants/featureMap';
import {
  ensureHiddenRole,
  assignHiddenRoleToUser,
  removeHiddenRoleFromUser,
} from '@/services/featureGrants';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const tenant = useTenantStore();

const isEdit = computed(() => route.name === 'employees.edit');
const canAccess = computed(() =>
  isEdit.value
    ? can('employees.update') || can('employees.manage')
    : can('employees.create') || can('employees.manage'),
);

const roleOptions = ref<string[]>([]);
const form = ref({
  name: '',
  email: '',
  phone: '',
  address: '',
  roles: [] as string[],
});

const featureGrants = ref<Record<string, string[]>>({});
const hiddenRoles = ref<Record<string, any>>({});

const availableFeatures = computed(() =>
  auth.features.filter((f) => featureMap[f]),
);

async function loadRoles() {
  const { data } = await api.get('/roles');
  roleOptions.value = data
    .map((r: any) => r.name)
    .filter((n: string) => n !== 'SuperAdmin');
}

async function loadEmployee() {
  if (!isEdit.value) return;
  const { data } = await api.get(`/employees/${route.params.id}`);
  const roles: string[] = [];
  hiddenRoles.value = {};
  featureGrants.value = {} as Record<string, string[]>;
  (data.roles || []).forEach((r: any) => {
    if (r.slug?.startsWith('__fg__')) {
      const parts = r.slug.split('__').filter(Boolean);
      const feature = parts[2];
      hiddenRoles.value[feature] = r;
      featureGrants.value[feature] = r.abilities || [];
    } else if (r.name !== 'SuperAdmin') {
      roles.push(r.name);
    }
  });
  form.value = {
    name: data.name || '',
    email: data.email || '',
    phone: data.phone || '',
    address: data.address || '',
    roles,
  };
}

async function submit() {
  const payload = {
    name: form.value.name,
    email: form.value.email,
    phone: form.value.phone,
    address: form.value.address,
    roles: form.value.roles.filter((r) => r !== 'SuperAdmin'),
  };
  let userId: number;
  if (isEdit.value) {
    await api.post(`/employees/${route.params.id}`, payload);
    userId = Number(route.params.id);
  } else {
    const { data } = await api.post('/employees', payload);
    userId = data.id;
  }
  await reconcileFeatureGrants(userId);
  router.push({ name: 'employees.list' });
}

onMounted(async () => {
  await loadRoles();
  await loadEmployee();
  availableFeatures.value.forEach((f) => {
    if (!featureGrants.value[f]) featureGrants.value[f] = [];
  });
});

async function reconcileFeatureGrants(userId: number) {
  for (const feature of availableFeatures.value) {
    const selected = featureGrants.value[feature] || [];
    const existing = hiddenRoles.value[feature];
    if (selected.length) {
      const role = await ensureHiddenRole(tenant.tenantId, feature, selected);
      if (!existing || existing.id !== role.id) {
        await assignHiddenRoleToUser(userId, role.id);
        if (existing && existing.id !== role.id) {
          await removeHiddenRoleFromUser(userId, existing.id);
        }
      }
    } else if (existing) {
      await removeHiddenRoleFromUser(userId, existing.id);
    }
  }
}
</script>


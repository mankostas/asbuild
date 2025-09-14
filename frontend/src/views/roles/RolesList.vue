<template>
  <div>
    <TenantSwitcher
      v-if="auth.isSuperAdmin"
      class="mb-4"
      :impersonate="false"
    />
    <RolesTable
      v-if="!loading"
      :rows="all"
      @edit="edit"
      @delete="remove"
      @assign="openAssign"
      @copy="copy"
      @delete-selected="removeMany"
      @copy-selected="copyMany"
    >
      <template #header-actions>
        <Select
          v-if="auth.isSuperAdmin"
          v-model="tenantFilter"
          :options="tenantOptions"
          class="w-40"
          classInput="text-xs !h-8 !min-h-0"
          :aria-label="t('tenants')"
        />
        <Button
          v-if="hasAny(['roles.create', 'roles.manage'])"
          link="/roles/create"
          btnClass="btn-primary btn-sm min-w-[100px] !h-8 !py-0"
          icon="heroicons-outline:plus"
          iconClass="w-4 h-4"
          :text="t('roles.addRole')"
          :aria-label="t('roles.addRole')"
        />
      </template>
    </RolesTable>
    <div v-else class="p-4">
      <SkeletonTable :count="10" />
    </div>
    <AssignRoleModal
      v-if="assignRoleId"
      :role-id="assignRoleId"
      @close="assignRoleId = null"
      @assigned="reload"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue';
import { useRouter } from 'vue-router';
import RolesTable from '@/components/roles/RolesTable.vue';
import SkeletonTable from '@/components/ui/Skeleton/Table.vue';
import Button from '@/components/ui/Button';
import Select from '@/components/ui/Select/index.vue';
import Swal from 'sweetalert2';
import { useRolesStore } from '@/stores/roles';
import { useAuthStore, hasAny } from '@/stores/auth';
import { useTenantStore } from '@/stores/tenant';
import AssignRoleModal from './AssignRoleModal.vue';
import { useI18n } from 'vue-i18n';
import TenantSwitcher from '@/components/admin/TenantSwitcher.vue';

interface RoleRow {
  id: number;
  name: string;
  description?: string | null;
  level: number;
  abilities: string[];
  created_at?: string;
  updated_at?: string;
  tenant?: { id: number; name: string } | null;
  tenant_id?: number | null;
  users_count: number;
}

const router = useRouter();
const { t } = useI18n();
const rolesStore = useRolesStore();
const auth = useAuthStore();
const tenantStore = useTenantStore();

const all = ref<RoleRow[]>([]);
const loading = ref(true);
const tenantFilter = ref<string | number | ''>('');
const assignRoleId = ref<number | null>(null);

const tenantOptions = computed(() => [
  { value: '', label: t('allTenants') },
  ...tenantStore.tenants.map((ten: any) => ({ value: ten.id, label: ten.name })),
]);

async function load() {
  const isFilteringByTenant =
    auth.isSuperAdmin &&
    tenantFilter.value !== '' &&
    tenantFilter.value !== 'super_admin';
  const scope: 'tenant' | 'global' | 'all' = auth.isSuperAdmin
    ? isFilteringByTenant
      ? 'tenant'
      : 'all'
    : 'tenant';
  const tenantId: string | number | undefined = auth.isSuperAdmin
    ? isFilteringByTenant
      ? tenantFilter.value
      : undefined
    : tenantStore.currentTenantId || undefined;

  await rolesStore.fetch({ scope, tenant_id: tenantId });
  await tenantStore.loadTenants({ per_page: 100 });
  const tenantMap = tenantStore.tenants.reduce(
    (acc: Record<number, any>, t) => ({ ...acc, [t.id]: t }),
    {},
  );
  all.value = rolesStore.roles.map((r: any) => ({
    id: r.id,
    name: r.name,
    description: r.description,
    level: r.level,
    abilities: r.abilities || [],
    tenant: r.tenant || tenantMap[r.tenant_id] || null,
    tenant_id: r.tenant_id,
    created_at: r.created_at,
    updated_at: r.updated_at,
    users_count: r.users_count,
  }));
  loading.value = false;
}

onMounted(load);

function reload() {
  loading.value = true;
  all.value = [];
  load();
}

watch(tenantFilter, reload);

watch(
  () => tenantStore.currentTenantId,
  () => {
    if (!auth.isSuperAdmin) reload();
  },
);

function edit(id: number) {
  router.push({ name: 'roles.edit', params: { id } });
}

async function remove(id: number) {
  const res = await Swal.fire({
    title: 'Delete role?',
    icon: 'warning',
    showCancelButton: true,
  });
  if (res.isConfirmed) {
    await rolesStore.remove(id);
    reload();
  }
}

function openAssign(id: number) {
  assignRoleId.value = id;
}

async function copy(id: number) {
  let tenantId: string | number | undefined;
  if (auth.isSuperAdmin) {
    await tenantStore.loadTenants();
    const inputOptions = tenantStore.tenants.reduce(
      (acc: any, t: any) => ({ ...acc, [t.id]: t.name }),
      {},
    );
    const res = await Swal.fire({
      title: 'Copy to tenant',
      input: 'select',
      inputOptions,
      showCancelButton: true,
    });
    if (!res.isConfirmed || !res.value) return;
    tenantId = res.value;
  }
  await rolesStore.copyToTenant(id, tenantId);
  reload();
}

async function removeMany(ids: number[]) {
  const res = await Swal.fire({
    title: 'Delete selected roles?',
    icon: 'warning',
    showCancelButton: true,
  });
  if (res.isConfirmed) {
    await rolesStore.deleteMany(ids);
    reload();
  }
}

async function copyMany(ids: number[]) {
  let tenantId: string | number | undefined;
  if (auth.isSuperAdmin) {
    await tenantStore.loadTenants();
    const inputOptions = tenantStore.tenants.reduce(
      (acc: any, t: any) => ({ ...acc, [t.id]: t.name }),
      {},
    );
    const res = await Swal.fire({
      title: 'Copy to tenant',
      input: 'select',
      inputOptions,
      showCancelButton: true,
    });
    if (!res.isConfirmed || !res.value) return;
    tenantId = res.value;
  }
  await rolesStore.copyManyToTenant(ids, tenantId);
  reload();
}
</script>

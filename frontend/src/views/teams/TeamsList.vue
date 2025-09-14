<template>
  <div>
    <TeamsTable
      v-if="!loading"
      :rows="all"
      :is-super-admin="auth.isSuperAdmin"
      @edit="edit"
      @delete="remove"
      @delete-selected="removeMany"
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
          v-if="can('teams.create') || can('teams.manage')"
          link="/teams/create"
          btnClass="btn-primary btn-sm min-w-[100px] !h-8 !py-0"
          icon="heroicons-outline:plus"
          iconClass="w-4 h-4"
          :text="t('teams.addTeam')"
          :aria-label="t('teams.addTeam')"
        />
      </template>
    </TeamsTable>
    <div v-else class="p-4">
      <SkeletonTable :count="10" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue';
import { useRouter } from 'vue-router';
import TeamsTable from '@/components/teams/TeamsTable.vue';
import SkeletonTable from '@/components/ui/Skeleton/Table.vue';
import Button from '@/components/ui/Button';
import Select from '@/components/ui/Select/index.vue';
import Swal from 'sweetalert2';
import { useTeamsStore } from '@/stores/teams';
import { useAuthStore, can } from '@/stores/auth';
import { useTenantStore } from '@/stores/tenant';
import { useI18n } from 'vue-i18n';

interface TeamRow {
  id: number;
  name: string;
  description: string | null;
  members: { name: string; avatar?: string | null }[];
  created_at: string;
  updated_at: string;
  tenant?: { id: number; name: string } | null;
  tenant_id?: number | null;
}

const router = useRouter();
const teamsStore = useTeamsStore();
const auth = useAuthStore();
const tenantStore = useTenantStore();
const { t } = useI18n();

const all = ref<TeamRow[]>([]);
const loading = ref(true);
const tenantFilter = ref<string | number | ''>('');

const tenantOptions = computed(() => [
  { value: '', label: t('allTenants') },
  ...tenantStore.tenants.map((t: any) => ({ value: t.id, label: t.name })),
]);

async function load() {
  const params: any = {};
  if (auth.isSuperAdmin && tenantFilter.value) {
    params.tenant_id = tenantFilter.value;
  }
  await tenantStore.loadTenants({ per_page: 100 });
  await teamsStore.fetch(params);
  const tenantMap = tenantStore.tenants.reduce(
    (acc: Record<number, any>, t: any) => ({ ...acc, [t.id]: t }),
    {},
  );
  all.value = teamsStore.teams.map((t: any) => ({
    id: t.id,
    name: t.name,
    description: t.description,
    members: (t.employees || []).map((e: any) => ({ name: e.name, avatar: e.avatar })),
    created_at: t.created_at,
    updated_at: t.updated_at,
    tenant: t.tenant || tenantMap[t.tenant_id] || null,
    tenant_id: t.tenant_id,
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
    if (!auth.isSuperAdmin) {
      all.value = [];
      reload();
    }
  },
);

function edit(id: number) {
  router.push({ name: 'teams.edit', params: { id } });
}

async function remove(id: number) {
  const res = await Swal.fire({
    title: 'Delete team?',
    icon: 'warning',
    showCancelButton: true,
  });
  if (res.isConfirmed) {
    await teamsStore.remove(id);
    reload();
  }
}

async function removeMany(ids: number[]) {
  const res = await Swal.fire({
    title: 'Delete selected teams?',
    icon: 'warning',
    showCancelButton: true,
  });
  if (res.isConfirmed) {
    for (const id of ids) {
      await teamsStore.remove(id);
    }
    reload();
  }
}
</script>

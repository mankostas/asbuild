<template>
  <div>
    <TeamsTable
      v-if="!loading"
      :rows="all"
      @edit="edit"
      @delete="remove"
      @delete-selected="removeMany"
    >
      <template #header-actions>
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
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import TeamsTable from '@/components/teams/TeamsTable.vue';
import SkeletonTable from '@/components/ui/Skeleton/Table.vue';
import Button from '@/components/ui/Button';
import Swal from 'sweetalert2';
import { useTeamsStore } from '@/stores/teams';
import { can } from '@/stores/auth';
import { useI18n } from 'vue-i18n';

interface TeamRow {
  id: number;
  name: string;
  description: string | null;
  members: string;
  created_at: string;
  updated_at: string;
}

const router = useRouter();
const teamsStore = useTeamsStore();
const { t } = useI18n();

const all = ref<TeamRow[]>([]);
const loading = ref(true);

async function load() {
  await teamsStore.fetch();
  all.value = teamsStore.teams.map((t: any) => ({
    id: t.id,
    name: t.name,
    description: t.description,
    members: (t.employees || []).map((e: any) => e.name).join(', '),
    created_at: t.created_at,
    updated_at: t.updated_at,
  }));
  loading.value = false;
}

onMounted(load);

function reload() {
  loading.value = true;
  all.value = [];
  load();
}

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

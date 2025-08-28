<template>
  <div>
    <div class="mb-4">
      <RouterLink
        class="bg-blue-600 text-white px-4 py-2 rounded flex items-center gap-2"
        :to="{ name: 'teams.create' }"
      >
        <Icon icon="heroicons-outline:plus" class="w-5 h-5" />
        Add Team
      </RouterLink>
    </div>
    <DashcodeServerTable
      :key="tableKey"
      :columns="columns"
      :fetcher="fetchTeams"
    >
      <template #actions="{ row }">
        <div class="flex gap-2">
          <button class="text-blue-600" title="Edit" @click="edit(row.id)">
            <Icon icon="heroicons-outline:pencil-square" class="w-5 h-5" />
          </button>
          <button class="text-red-600" title="Delete" @click="remove(row.id)">
            <Icon icon="heroicons-outline:trash" class="w-5 h-5" />
          </button>
        </div>
      </template>
    </DashcodeServerTable>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import DashcodeServerTable from '@/components/datatable/DashcodeServerTable.vue';
import Swal from 'sweetalert2';
import Icon from '@/components/ui/Icon';
import { useNotify } from '@/plugins/notify';
import { useTeamsStore } from '@/stores/teams';

const router = useRouter();
const notify = useNotify();
const teamsStore = useTeamsStore();

const tableKey = ref(0);

const columns = [
  { label: 'Name', field: 'name', sortable: true },
  { label: 'Description', field: 'description', sortable: true },
  { label: 'Members', field: 'members', sortable: false },
];

async function fetchTeams({ page, perPage, sort, search }: any) {
  await teamsStore.fetch();
  let rows = teamsStore.teams.slice().map((t: any) => ({
    id: t.id,
    name: t.name,
    description: t.description,
    members: (t.employees || []).map((e: any) => e.name).join(', '),
  }));
  if (search) {
    const q = String(search).toLowerCase();
    rows = rows.filter((r) =>
      Object.values(r).some((v) => String(v ?? '').toLowerCase().includes(q)),
    );
  }
  if (sort && sort.field) {
    rows.sort((a: any, b: any) => {
      const fa = a[sort.field] ?? '';
      const fb = b[sort.field] ?? '';
      if (fa < fb) return sort.type === 'asc' ? -1 : 1;
      if (fa > fb) return sort.type === 'asc' ? 1 : -1;
      return 0;
    });
  }
  const total = rows.length;
  const start = (page - 1) * perPage;
  const paged = rows.slice(start, start + perPage);
  return { rows: paged, total };
}

function reload() {
  tableKey.value++;
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
  if (!res.isConfirmed) return;
  try {
    await teamsStore.remove(id);
    reload();
  } catch (e: any) {
    notify.error('Failed to delete');
  }
}
</script>


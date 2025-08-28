<template>
    <div>
      <div class="mb-4">
        <Button
          v-if="can('tenants.create') || can('tenants.manage')"
          btnClass="btn-primary"
          text="Add Tenant"
          link="/tenants/create"
        />
      </div>
      <DashcodeServerTable
        :key="tableKey"
      :columns="columns"
      :fetcher="fetchTenants"
    >
      <template #actions="{ row }">
        <div class="flex gap-2">
          <Button
            v-if="can('tenants.view') || can('tenants.manage')"
            btnClass="btn-outline-primary btn-sm"
            text="View"
            @click="view(row.id)"
          />
          <Button
            v-if="can('tenants.update') || can('tenants.manage')"
            :link="`/tenants/${row.id}/edit`"
            btnClass="btn-outline-primary btn-sm"
            text="Edit"
          />
          <Button
            v-if="can('tenants.manage')"
            btnClass="btn-outline-secondary btn-sm"
            text="Impersonate"
            @click="impersonate(row)"
          />
          <Button
            v-if="can('tenants.delete') || can('tenants.manage')"
            btnClass="btn-outline-danger btn-sm"
            text="Delete"
            @click="remove(row.id)"
          />
        </div>
      </template>
    </DashcodeServerTable>
  </div>
</template>

<script setup lang="ts">
import { ref, inject } from 'vue';
import { useRouter } from 'vue-router';
import DashcodeServerTable from '@/components/datatable/DashcodeServerTable.vue';
import Button from '@/components/ui/Button/index.vue';
import api from '@/services/api';
import { useAuthStore, can } from '@/stores/auth';

const auth = useAuthStore();
const router = useRouter();
const swal = inject('$swal');
const tableKey = ref(0);
const all = ref<any[]>([]);

const columns = [
  { label: 'ID', field: 'id', sortable: true },
  { label: 'Name', field: 'name', sortable: true },
  { label: 'Phone', field: 'phone', sortable: true },
  { label: 'Address', field: 'address', sortable: false },
];

async function fetchTenants({ page, perPage, sort, search }: any) {
  if (!all.value.length) {
    // The tenants API responds with an object containing
    // `{ data: Tenant[], meta: PaginationMeta }`. We only need the
    // array of tenants for client-side pagination, so extract the
    // `data` property explicitly before assigning to `all`.
    const { data } = await api.get('/tenants');
    all.value = data.data;
  }
  let rows = all.value.slice();
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

async function impersonate(t: any) {
  await auth.impersonate(t.id, t.name);
  window.location.reload();
}

function view(id: number) {
  router.push(`/tenants/${id}`);
}

async function remove(id: number) {
  const result = await swal?.fire({
    title: 'Delete tenant?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete',
  });
  if (!result?.isConfirmed) return;
  await api.delete(`/tenants/${id}`);
  all.value = [];
  reload();
}
</script>


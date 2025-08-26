<template>
  <div>
    <h2 class="text-xl font-bold mb-4">Employees</h2>
    <div class="mb-4">
      <Button
        btnClass="btn-primary"
        text="Invite Employee"
        link="/employees/create"
      />
    </div>
    <DashcodeServerTable
      :key="tableKey"
      :columns="columns"
      :fetcher="fetchEmployees"
    >
      <template #actions="{ row }">
        <div class="flex gap-2">
          <Button
            :link="`/employees/${row.id}/edit`"
            btnClass="btn-outline-primary btn-sm"
            text="Edit"
          />
          <Button
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
import { ref } from 'vue';
import DashcodeServerTable from '@/components/datatable/DashcodeServerTable.vue';
import Button from '@/components/ui/Button/index.vue';
import api from '@/services/api';
import { useNotify } from '@/plugins/notify';
import Swal from 'sweetalert2';

const notify = useNotify();
const tableKey = ref(0);
const all = ref<any[]>([]);

const columns = [
  { label: 'Name', field: 'name', sortable: true },
  { label: 'Email', field: 'email', sortable: true },
  { label: 'Roles', field: 'roles', sortable: false },
  { label: 'Phone', field: 'phone', sortable: true },
  { label: 'Address', field: 'address', sortable: false },
];

function formatRoles(roles: any[]) {
  return roles
    .filter((r: any) => r.name !== 'SuperAdmin')
    .map((r: any) => r.name)
    .join(', ');
}

async function fetchEmployees({ page, perPage, sort, search }: any) {
  if (!all.value.length) {
    const { data } = await api.get('/employees');
    all.value = data;
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
  const paged = rows.slice(start, start + perPage).map((r: any) => ({
    id: r.id,
    name: r.name,
    email: r.email,
    roles: formatRoles(r.roles),
    phone: r.phone,
    address: r.address,
  }));
  return { rows: paged, total };
}

function reload() {
  tableKey.value++;
}

async function remove(id: number) {
  const result = await Swal.fire({
    title: 'Delete employee?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete',
  });
  if (!result.isConfirmed) return;
  try {
    await api.delete(`/employees/${id}`);
    all.value = [];
    reload();
  } catch (e: any) {
    if (e.status === 403) {
      notify.error('Cannot delete user with SuperAdmin role');
    } else {
      notify.error('Failed to delete');
    }
  }
}
</script>


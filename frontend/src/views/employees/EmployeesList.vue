<template>
  <div>
    <h2 class="text-xl font-bold mb-4">Employees</h2>
    <div class="mb-4">
      <RouterLink
        class="bg-blue-600 text-white px-4 py-2 rounded"
        :to="{ name: 'employees.create' }"
      >Invite Employee</RouterLink>
    </div>
    <table class="min-w-full border">
      <thead>
        <tr class="bg-gray-100 text-left">
          <th class="p-2 border">Name</th>
          <th class="p-2 border">Email</th>
          <th class="p-2 border">Roles</th>
          <th class="p-2 border">Phone</th>
          <th class="p-2 border">Address</th>
          <th class="p-2 border w-32">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="e in employees" :key="e.id" class="border-t">
          <td class="p-2 border">{{ e.name }}</td>
          <td class="p-2 border">{{ e.email }}</td>
          <td class="p-2 border">{{ formatRoles(e.roles) }}</td>
          <td class="p-2 border">{{ e.phone }}</td>
          <td class="p-2 border">{{ e.address }}</td>
          <td class="p-2 border flex gap-2">
            <RouterLink
              class="text-blue-600"
              :to="{ name: 'employees.edit', params: { id: e.id } }"
            >Edit</RouterLink>
            <button class="text-red-600" @click="remove(e.id)">Delete</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useToast } from '@/plugins/toast';
import api from '@/services/api';
import { RouterLink } from 'vue-router';

const employees = ref<any[]>([]);
const toast = useToast();

function formatRoles(roles: any[]) {
  return roles
    .filter((r) => r.name !== 'SuperAdmin')
    .map((r) => r.name)
    .join(', ');
}

async function load() {
  const { data } = await api.get('/employees');
  employees.value = data;
}

async function remove(id: number) {
  if (!confirm('Delete employee?')) return;
  try {
    await api.delete(`/employees/${id}`);
    await load();
  } catch (e: any) {
    if (e.status === 403) {
      toast.add({
        severity: 'error',
        summary: 'Cannot delete user with SuperAdmin role',
        detail: '',
      });
    } else {
      toast.add({ severity: 'error', summary: 'Failed to delete', detail: '' });
    }
  }
}

onMounted(load);
</script>


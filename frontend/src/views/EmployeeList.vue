<template>
  <div>
    <h2 class="text-xl font-bold mb-4">Employees</h2>
    <button class="bg-blue-600 text-white px-4 py-2" @click="showForm = !showForm">
      Invite Employee
    </button>
    <EmployeeForm v-if="showForm" @saved="load" />
    <ul class="mt-4">
      <li v-for="e in employees" :key="e.id" class="mb-2 flex gap-2 items-center">
        <span>{{ e.name }} - {{ e.email }}</span>
        <span class="text-sm text-gray-600">{{ e.roles.map(r => r.name).join(', ') }}</span>
        <button class="text-red-600 ml-auto" @click="remove(e.id)">Delete</button>
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '@/services/api';
import EmployeeForm from '@/components/employees/EmployeeForm.vue';

const employees = ref<any[]>([]);
const showForm = ref(false);

async function load() {
  const { data } = await api.get('/employees');
  employees.value = data;
  showForm.value = false;
}

async function remove(id: number) {
  if (confirm('Delete employee?')) {
    await api.delete(`/employees/${id}`);
    await load();
  }
}

onMounted(load);
</script>


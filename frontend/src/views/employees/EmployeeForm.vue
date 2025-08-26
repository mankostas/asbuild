<template>
  <div>
    <h2 class="text-xl font-bold mb-4">{{ isEdit ? 'Edit' : 'Invite' }} Employee</h2>
    <form @submit.prevent="submit" class="grid gap-4 max-w-lg">
      <div>
        <label class="block mb-1">Name</label>
        <input v-model="form.name" class="border p-2 w-full" />
      </div>
      <div>
        <label class="block mb-1">Email</label>
        <input v-model="form.email" type="email" class="border p-2 w-full" />
      </div>
      <div>
        <label class="block mb-1">Phone</label>
        <input v-model="form.phone" class="border p-2 w-full" />
      </div>
      <div>
        <label class="block mb-1">Address</label>
        <input v-model="form.address" class="border p-2 w-full" />
      </div>
      <div>
        <label class="block mb-1">Roles</label>
        <select v-model="form.roles" multiple class="border p-2 w-full">
          <option v-for="r in roleOptions" :key="r" :value="r">{{ r }}</option>
        </select>
      </div>
      <button class="bg-blue-600 text-white px-4 py-2 rounded">
        {{ isEdit ? 'Save' : 'Invite' }}
      </button>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '@/services/api';

const route = useRoute();
const router = useRouter();

const isEdit = computed(() => route.name === 'employees.edit');

const roleOptions = ref<string[]>([]);
const form = ref({
  name: '',
  email: '',
  phone: '',
  address: '',
  roles: [] as string[],
});

async function loadRoles() {
  const { data } = await api.get('/roles');
  roleOptions.value = data
    .map((r: any) => r.name)
    .filter((n: string) => n !== 'SuperAdmin');
}

async function loadEmployee() {
  if (!isEdit.value) return;
  const { data } = await api.get(`/employees/${route.params.id}`);
  form.value = {
    name: data.name || '',
    email: data.email || '',
    phone: data.phone || '',
    address: data.address || '',
    roles: (data.roles || [])
      .map((r: any) => r.name)
      .filter((n: string) => n !== 'SuperAdmin'),
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
  if (isEdit.value) {
    await api.post(`/employees/${route.params.id}`, payload);
  } else {
    await api.post('/employees', payload);
  }
  router.push({ name: 'employees.list' });
}

onMounted(async () => {
  await loadRoles();
  await loadEmployee();
});
</script>


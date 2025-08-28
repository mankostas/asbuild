<template>
  <div v-if="canAccess">
    <form @submit.prevent="submit" class="grid gap-4 max-w-lg">
      <Textinput label="Name" v-model="form.name" />
      <Textinput label="Email" type="email" v-model="form.email" />
      <Textinput label="Phone" v-model="form.phone" />
      <Textinput label="Address" v-model="form.address" />
      <VueSelect label="Roles">
        <vSelect v-model="form.roles" :options="roleOptions" multiple />
      </VueSelect>
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
import vSelect from 'vue-select';
import { can } from '@/stores/auth';

const route = useRoute();
const router = useRouter();

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


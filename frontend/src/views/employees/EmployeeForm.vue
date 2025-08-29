<template>
  <div v-if="canAccess">
    <form class="grid gap-4 max-w-lg" @submit.prevent="submit">
      <Textinput v-model="form.name" label="Name" />
      <Textinput v-model="form.email" label="Email" type="email" />
      <Textinput v-model="form.phone" label="Phone" />
      <Textinput v-model="form.address" label="Address" />
      <VueSelect label="Roles">
        <template #default="{ inputId }">
          <vSelect
            :id="inputId"
            v-model="form.roles"
            :options="roleOptions"
            multiple
          />
        </template>
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


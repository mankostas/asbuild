<template>
  <form @submit.prevent="save" class="space-y-4">
    <Textinput label="Name" v-model="form.name" />
    <Textinput label="Email" type="email" v-model="form.email" />
    <Textinput
      label="Password"
      type="password"
      hasicon
      v-model="form.password"
    />
    <Textinput
      label="Confirm Password"
      type="password"
      hasicon
      v-model="form.password_confirmation"
    />
    <Button type="submit" :isDisabled="!dirty" btnClass="btn-dark"
      >Save Profile</Button
    >
  </form>
</template>

<script setup lang="ts">
import { reactive, computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import api from '@/services/api';
import Textinput from '@/components/ui/Textinput/index.vue';
import Button from '@/components/ui/Button/index.vue';
import { useToast } from '@/plugins/toast';

const auth = useAuthStore();
const toast = useToast();
const initial = {
  name: auth.user?.name || '',
  email: auth.user?.email || '',
};
const form = reactive({
  ...initial,
  password: '',
  password_confirmation: '',
});

const dirty = computed(
  () =>
    form.name !== initial.name ||
    form.email !== initial.email ||
    form.password !== '' ||
    form.password_confirmation !== '',
);

async function save() {
  if (!dirty.value) return;
  const { data } = await api.put('/settings/profile', form);
  auth.user = data;
  initial.name = form.name = data.name;
  initial.email = form.email = data.email;
  form.password = '';
  form.password_confirmation = '';
  toast.add({ severity: 'success', summary: 'Profile saved', detail: '' });
}
</script>

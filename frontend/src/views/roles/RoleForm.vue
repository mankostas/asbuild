<template>
  <div>
    <form @submit.prevent="onSubmit" class="max-w-md grid gap-4">
      <div>
        <label class="block font-medium mb-1" for="name">Name<span class="text-red-600">*</span></label>
        <input id="name" v-model="name" class="border rounded p-2 w-full" />
      </div>
      <div v-if="serverError" class="text-red-600 text-sm">{{ serverError }}</div>
      <button
        type="submit"
        class="px-4 py-2 bg-blue-600 text-white rounded"
        :disabled="!canSubmit"
      >Save</button>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '@/services/api';
import { useNotify } from '@/plugins/notify';

const route = useRoute();
const router = useRouter();
const notify = useNotify();

const name = ref('');
const serverError = ref('');

const isEdit = computed(() => route.name === 'roles.edit');

onMounted(async () => {
  if (isEdit.value) {
    const { data } = await api.get(`/roles/${route.params.id}`);
    if (data.name === 'SuperAdmin') {
      notify.error('Cannot modify SuperAdmin role');
      router.push({ name: 'roles.list' });
      return;
    }
    name.value = data.name;
  }
});

const canSubmit = computed(() => !!name.value && name.value !== 'SuperAdmin');

async function onSubmit() {
  serverError.value = '';
  if (!canSubmit.value) return;
  const payload = { name: name.value };
  try {
    if (isEdit.value) {
      await api.patch(`/roles/${route.params.id}`, payload);
    } else {
      await api.post('/roles', payload);
    }
    router.push({ name: 'roles.list' });
  } catch (e: any) {
    serverError.value = e.message || 'Failed to save';
  }
}
</script>

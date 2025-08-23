<template>
  <div>
    <h2 class="text-xl font-bold mb-4">Appointments</h2>
    <button
      v-if="isAdmin"
      class="bg-blue-600 text-white px-4 py-2 mb-4"
      @click="create"
    >
      New Appointment
    </button>
    <ul>
      <li v-for="a in appointments" :key="a.id" class="mb-2 flex gap-2 items-center">
        <router-link :to="`/appointments/${a.id}`" class="text-blue-600">
          {{ a.title }} - {{ a.completedSteps }}/{{ a.totalSteps }}
        </router-link>
        <button v-if="isAdmin" class="text-red-600 ml-auto" @click="remove(a.id)">
          Delete
        </button>
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { onMounted, computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useAppointmentsStore } from '@/stores/appointments';
import api from '@/services/api';
import { useAuthStore } from '@/stores/auth';

const store = useAppointmentsStore();
const { appointments } = storeToRefs(store);
const auth = useAuthStore();
const isAdmin = computed(() => auth.user?.roles?.some((r: any) => r.name === 'ClientAdmin'));

onMounted(() => {
  store.fetch();
});

async function create() {
  const title = prompt('Title');
  if (title) {
    await api.post('/appointments', { title });
    await store.fetch();
  }
}

async function remove(id: number) {
  if (confirm('Delete appointment?')) {
    await api.delete(`/appointments/${id}`);
    await store.fetch();
  }
}
</script>

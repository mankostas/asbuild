<template>
  <div>
    <h2 class="text-xl font-bold mb-4">Appointments</h2>
    <ul>
      <li v-for="a in appointments" :key="a.id" class="mb-2">
        <router-link :to="`/appointments/${a.id}`" class="text-blue-600">
          {{ a.title }} - {{ a.completedSteps }}/{{ a.totalSteps }}
        </router-link>
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import { storeToRefs } from 'pinia';
import { useAppointmentsStore } from '@/stores/appointments';

const store = useAppointmentsStore();
const { appointments } = storeToRefs(store);

onMounted(() => {
  store.fetch();
});
</script>

<template>
  <div>
    <SubtaskList :task-id="Number(route.params.id)" />
  </div>
</template>

<script setup lang="ts">
import { useRoute } from 'vue-router';
import SubtaskList from '@/components/tasks/SubtaskList.vue';

const route = useRoute();
</script>

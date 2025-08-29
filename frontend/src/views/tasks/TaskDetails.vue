<template>
  <div>
    <header class="flex items-center justify-end mb-4">
      <button
        v-if="canWatch"
        class="btn btn-sm"
        :aria-label="isWatching ? t('tasks.unwatch') : t('tasks.watch')"
        :aria-pressed="isWatching.toString()"
        type="button"
        @click="toggleWatch"
        @keyup.enter.prevent="toggleWatch"
        @keyup.space.prevent="toggleWatch"
      >
        {{ isWatching ? t('tasks.unwatch') : t('tasks.watch') }}
      </button>
    </header>
    <SubtaskList :task-id="Number(route.params.id)" />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/auth';
import api from '@/services/api';
import SubtaskList from '@/components/tasks/SubtaskList.vue';

const route = useRoute();
const { t } = useI18n();
const auth = useAuthStore();

const isWatching = ref(false);
const canWatch = computed(() => auth.can('tasks.watch'));

async function load() {
  if (!canWatch.value) return;
  const { data } = await api.get(`/tasks/${route.params.id}`);
  isWatching.value = !!data.is_watching;
}

async function toggleWatch() {
  const id = route.params.id;
  if (isWatching.value) {
    await api.delete(`/tasks/${id}/watch`);
    isWatching.value = false;
  } else {
    await api.post(`/tasks/${id}/watch`);
    isWatching.value = true;
  }
}

onMounted(load);
</script>

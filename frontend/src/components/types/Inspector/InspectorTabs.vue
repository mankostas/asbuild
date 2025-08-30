<template>
  <div>
    <div v-if="selected" class="inspector">
      <nav class="mb-2 flex gap-2">
        <button
          v-for="tab in tabs"
          :key="tab"
          @click="active = tab"
          :class="['px-2 py-1 text-sm rounded', active===tab ? 'bg-indigo-600 text-white':'bg-gray-100']"
          :aria-current="active===tab ? 'page' : undefined"
        >
          {{ tab }}
        </button>
      </nav>
      <div v-if="active === 'Basics'" class="space-y-2">
        <label class="block text-sm">
          <span class="block mb-1">{{ t('Label') }}</span>
          <input v-model="selected.label" class="w-full border rounded px-2 py-1" aria-label="Field label" />
        </label>
        <label class="block text-sm">
          <span class="block mb-1">{{ t('Required') }}</span>
          <input type="checkbox" v-model="selected.required" aria-label="Required" />
        </label>
      </div>
      <div v-else class="text-sm text-gray-500" tabindex="0">{{ t('Not implemented') }}</div>
    </div>
    <div v-else class="text-sm text-gray-500">{{ t('Select a field') }}</div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{ selected: any | null }>();
const { t } = useI18n();
const tabs = ['Basics', 'Validation', 'Logic', 'Roles', 'i18n', 'Data'];
const active = ref('Basics');
</script>

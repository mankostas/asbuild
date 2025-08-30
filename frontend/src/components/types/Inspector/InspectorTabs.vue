<template>
  <div>
    <div v-if="selected" class="inspector">
        <nav class="mb-2 flex gap-2">
          <button
            v-for="tab in tabs"
            :key="tab"
            :class="['px-2 py-1 text-sm rounded', active===tab ? 'bg-indigo-600 text-white':'bg-gray-100']"
            :aria-current="active===tab ? 'page' : undefined"
            @click="active = tab"
          >
            {{ tab }}
          </button>
        </nav>
        <div v-if="active === 'Basics'" class="space-y-2">
          <label class="block text-sm" for="fieldLabel">
            <span class="block mb-1">{{ t('Label') }}</span>
            <input
              id="fieldLabel"
              v-model="label"
              class="w-full border rounded px-2 py-1"
              aria-label="Field label"
            />
          </label>
          <label class="block text-sm" for="fieldRequired">
            <span class="block mb-1">{{ t('Required') }}</span>
            <input
              id="fieldRequired"
              v-model="required"
              type="checkbox"
              aria-label="Required"
            />
          </label>
        </div>
      <div v-else class="text-sm text-gray-500" tabindex="0">{{ t('Not implemented') }}</div>
    </div>
    <div v-else class="text-sm text-gray-500">{{ t('Select a field') }}</div>
  </div>
</template>

<script setup lang="ts">
/* eslint-disable vue/no-mutating-props */
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{ selected: any | null }>();
const { t } = useI18n();
const tabs = ['Basics', 'Validation', 'Logic', 'Roles', 'i18n', 'Data'];
const active = ref('Basics');
const label = computed({
  get: () => props.selected?.label ?? '',
  set: (val: string) => {
    if (props.selected) props.selected.label = val;
  },
});
const required = computed({
  get: () => props.selected?.required ?? false,
  set: (val: boolean) => {
    if (props.selected) props.selected.required = val;
  },
});
</script>

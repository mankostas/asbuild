<template>
  <div class="p-4 field-palette">
    <input
      v-model="search"
      type="text"
      class="mb-2 w-full border rounded px-2 py-1"
      :placeholder="t('Search')"
      aria-label="Search fields"
    />
    <div v-for="group in filteredGroups" :key="group.label" class="mb-4">
      <h4 class="font-semibold text-sm mb-1">{{ group.label }}</h4>
      <ul>
        <li v-for="item in group.items" :key="item.key">
          <button
            type="button"
            class="w-full text-left px-2 py-1 rounded hover:bg-gray-100"
            @click="$emit('select', item)"
            :aria-label="`Add ${item.label}`"
          >
            {{ item.label }}
          </button>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{ groups: Array<{ label: string; items: any[] }> }>();
const emit = defineEmits<{ (e: 'select', item: any): void }>();
const { t } = useI18n();
const search = ref('');

const filteredGroups = computed(() =>
  props.groups
    .map((g) => ({
      label: g.label,
      items: g.items.filter((i) =>
        i.label.toLowerCase().includes(search.value.toLowerCase()),
      ),
    }))
    .filter((g) => g.items.length),
);
</script>

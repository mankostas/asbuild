<template>
  <div class="p-4 field-palette">
    <Textinput
      id="fieldPaletteSearch"
      v-model="search"
      :label="t('types.form.search')"
      class="mb-2"
      classInput="w-full"
      classLabel="sr-only"
    />
    <div v-for="group in filteredGroups" :key="group.label" class="mb-4">
      <h4 class="font-semibold text-sm mb-1">{{ group.label }}</h4>
      <ul>
        <li v-for="item in group.items" :key="item.key">
          <Button
            type="button"
            btnClass="w-full text-left px-2 py-1 rounded hover:bg-gray-100"
            :aria-label="`${t('actions.add')} ${item.label}`"
            @click="$emit('select', item)"
          >
            {{ item.label }}
          </Button>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import Textinput from '@/components/ui/Textinput/index.vue';
import Button from '@/components/ui/Button/index.vue';

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

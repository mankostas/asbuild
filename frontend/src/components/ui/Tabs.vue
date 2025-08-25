<template>
  <div>
    <div role="tablist" class="flex border-b">
      <button
        v-for="t in tabs"
        :key="t.id"
        role="tab"
        :aria-selected="modelValue === t.id"
        @click="update(t.id)"
        class="-mb-px border-b-2 px-3 py-2 text-sm focus-ring"
        :class="
          modelValue === t.id
            ? 'border-primary text-primary'
            : 'border-transparent text-foreground/70 hover:text-foreground'
        "
      >
        {{ t.label }}
      </button>
    </div>
    <div class="p-4">
      <slot :active="modelValue" />
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  modelValue: string;
  tabs: { id: string; label: string }[];
}>();
const emit = defineEmits(['update:modelValue']);

function update(id: string) {
  emit('update:modelValue', id);
}
</script>

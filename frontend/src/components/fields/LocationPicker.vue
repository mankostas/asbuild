<template>
  <div class="flex gap-2">
    <label class="flex-1">
      <span class="sr-only">{{ label }} Latitude</span>
      <input
        type="number"
        class="form-input w-full"
        :value="modelValue.lat"
        aria-label="Latitude"
        @input="onLat"
      />
    </label>
    <label class="flex-1">
      <span class="sr-only">{{ label }} Longitude</span>
      <input
        type="number"
        class="form-input w-full"
        :value="modelValue.lng"
        aria-label="Longitude"
        @input="onLng"
      />
    </label>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{ label: string; modelValue: { lat: number; lng: number } }>();
const emit = defineEmits<{ 'update:modelValue': [{ lat: number; lng: number }] }>();
function onLat(e: Event) {
  const val = parseFloat((e.target as HTMLInputElement).value);
  emit('update:modelValue', { ...props.modelValue, lat: val });
}
function onLng(e: Event) {
  const val = parseFloat((e.target as HTMLInputElement).value);
  emit('update:modelValue', { ...props.modelValue, lng: val });
}
</script>

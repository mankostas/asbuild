<template>
  <div class="flex gap-2">
    <input
      :id="`lat-${uid}`"
      type="number"
      class="form-input w-full flex-1"
      :value="modelValue.lat"
      :aria-label="`${label} Latitude`"
      @input="onLat"
    />
    <input
      :id="`lng-${uid}`"
      type="number"
      class="form-input w-full flex-1"
      :value="modelValue.lng"
      :aria-label="`${label} Longitude`"
      @input="onLng"
    />
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{ label: string; modelValue: { lat: number; lng: number } }>();
const emit = defineEmits<{ 'update:modelValue': [{ lat: number; lng: number }] }>();
const uid = `loc-${Math.random().toString(36).slice(2)}`;
function onLat(e: Event) {
  const val = parseFloat((e.target as HTMLInputElement).value);
  emit('update:modelValue', { ...props.modelValue, lat: val });
}
function onLng(e: Event) {
  const val = parseFloat((e.target as HTMLInputElement).value);
  emit('update:modelValue', { ...props.modelValue, lng: val });
}
</script>

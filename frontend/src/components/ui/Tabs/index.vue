<template>
  <TabGroup>
    <TabList class="lg:space-x-8 md:space-x-4 space-x-0 rtl:space-x-reverse flex">
      <slot name="list" />
    </TabList>
    <TabPanels>
      <slot name="panel" />
    </TabPanels>
  </TabGroup>
</template>

<script setup lang="ts">
import { TabGroup, TabList, TabPanels } from '@headlessui/vue'

defineOptions({
  name: 'UiTabs',
})
</script>

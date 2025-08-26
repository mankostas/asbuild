<template>
  <div class="flex min-h-screen bg-gray-50 dark:bg-slate-900">
    <Sidebar />
    <div class="flex-1 flex flex-col">
      <Header />
      <main class="flex-1 p-4">
        <Breadcrumbs v-if="$slots.breadcrumbs">
          <slot name="breadcrumbs" />
        </Breadcrumbs>
        <slot />
      </main>
      <Footer />
    </div>
    <Settings />
  </div>
</template>
<script setup>
import Header from './Header.vue'
import Sidebar from './Sidebar.vue'
import Breadcrumbs from './Breadcrumbs.vue'
import Footer from './Footer.vue'
import Settings from './Settings.vue'
</script>

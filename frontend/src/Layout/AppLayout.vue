<template>
    <div class="flex min-h-screen bg-gray-50 dark:bg-slate-900">
      <Sidebar />
      <div class="flex flex-1 flex-col">
        <Header />
        <main class="flex-1 p-4">
          <Breadcrumbs />
          <div class="relative">
            <Transition name="page" mode="out-in">
              <router-view />
            </Transition>
          </div>
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
  import { Transition } from 'vue'
  </script>
<style>
/* Slide and fade transition for page navigation */
.page-enter-active,
.page-leave-active {
  transition: opacity 0.3s, transform 0.3s;
}
.page-enter-from {
  opacity: 0;
  transform: translateX(20px);
}
.page-leave-to {
  opacity: 0;
  transform: translateX(-20px);
}
</style>

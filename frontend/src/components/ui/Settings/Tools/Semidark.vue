<template>
  <div>
    <div class="flex justify-between mt-6 items-center">
      <div class="text-slate-600 text-base dark:text-slate-300">Semi Dark</div>
      <div>
        <label
          :for="ids.semidark"
          :class="semidark ? 'bg-primary-500' : 'bg-secondary-500'"
          class="relative inline-flex h-6 w-[46px] items-center rounded-full transition-all duration-150 cursor-pointer"
        >
          <input
            :id="ids.semidark"
            v-model="semidark"
            type="checkbox"
            class="hidden"
          />
          <span
            :class="
              semidark
                ? 'ltr:translate-x-6 rtl:-translate-x-6'
                : 'ltr:translate-x-[2px] rtl:translate-x-[-2px]'
            "
            class="inline-block h-5 w-5 transform rounded-full bg-white transition-all duration-150"
          />
        </label>
      </div>
    </div>
  </div>
</template>
<script>
export default {
  data() {
    return {
      ids: {
        semidark: 'semidark-toggle',
      },
    };
  },
  computed: {
    semidark: {
      get() {
        return this.$store.themeSettingsStore.semidark;
      },
      set(value) {
        this.$store.themeSettingsStore.semidark = value;
      },
    },
  },
  watch: {
    semidark: {
      handler(value) {
        if (value) {
          document.body.classList.remove("semi-light");
          document.body.classList.add("semi-dark");
        } else {
          document.body.classList.remove("semi-dark");
          document.body.classList.add("semi-light");
        }
        localStorage.setItem("semiDark", value);
      },
      immediate: true,
    },
  },
};
</script>
<style lang=""></style>

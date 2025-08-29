<template>
  <div v-if="!isLoading" class="h-20 w-20 mx-auto">
    <img src="@/assets/images/svg/loader.svg" alt="" />
  </div>
</template>
<script>
export default {
  name: "LoaderElement",
  data() {
    return {
      isLoading: false,
    };
  },
  mounted() {
    // onreadystatechange
    document.onreadystatechange = () => {
      if (document.readyState === "complete") {
        this.isLoading = true;
      }
    };
  },
};
</script>
<style lang=""></style>

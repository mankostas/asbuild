<template>
  <div class="space-y-5">
    <InputGroup type="text" placeholder="Button on right">
      <template #append>
        <Button text="Go" btnClass="btn-outline-dark " />
      </template>
    </InputGroup>
    <InputGroup type="text" placeholder="Button on both side">
      <template #prepend>
        <Button icon="heroicons-outline:search" btnClass="btn-outline-dark" />
      </template>
      <template #append>
        <Button text="Search" btnClass="btn-outline-dark" />
      </template>
    </InputGroup>
  </div>
</template>
<script>
import Button from "@/components/Button";
import InputGroup from "@/components/InputGroup";

export default {
  components: {
    InputGroup,
    Button,
  },
};
</script>
<style lang=""></style>

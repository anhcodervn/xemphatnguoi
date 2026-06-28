<script setup lang="ts">
import { onMounted } from 'vue';
import { useUserStore } from './stores/user.store';

const userStore = useUserStore();

onMounted(() => {
    userStore.bootstrap();
});
</script>

<template>
  <router-view />
</template>

<style>
body {
  margin: 0;
  font-family: "Be Vietnam Pro", "IBM Plex Sans", system-ui, -apple-system, sans-serif;
}
</style>

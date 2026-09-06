<template>
    <div id="wrapper">
        <Header />
        <Menu />
        <RouterView />
    </div>
</template>

<script setup>
import { RouterView } from 'vue-router';
import Header from '@/components/Header.vue';
import Menu from '@/components/Menu.vue';
</script>

<style></style>

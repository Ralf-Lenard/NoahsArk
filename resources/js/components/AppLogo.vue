<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-amber-800 text-amber-100">
        <AppLogoIcon class="size-5 fill-current text-white dark:text-amber-300" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-xl"> <!-- Changed here -->
        <span class="mb-0.5 truncate font-semibold leading-none text-amber-900 dark:text-amber-100">
            Noah's Ark
        </span>
    </div>
</template>

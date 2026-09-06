<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 32 32"
        fill="currentColor"
        aria-hidden="true"
        :class="className"
        v-bind="$attrs"
    >
        <path
            fill="currentColor"
            fill-rule="evenodd"
            clip-rule="evenodd"
            d="M16 2.2 30.4 13.5V27a2.4 2.4 0 0 1-2.4 2.4H4A2.4 2.4 0 0 1 1.6 27V13.5L16 2.2Zm0 3.56L27.6 14.86V26.6H4.4V14.86L16 5.76Zm0 11.74a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5Z"
        />
    </svg>
</template>

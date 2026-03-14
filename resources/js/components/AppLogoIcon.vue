<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" :class="className" v-bind="$attrs">
        <circle cx="20" cy="20" r="18" fill="none" stroke="currentColor" stroke-width="2.5" />
        <circle cx="20" cy="20" r="12" fill="none" stroke="currentColor" stroke-width="1.5" opacity="0.5" />
        <circle cx="20" cy="20" r="6" fill="none" stroke="currentColor" stroke-width="1.5" opacity="0.3" />
        <circle cx="20" cy="20" r="2.5" fill="currentColor" />
        <line x1="20" y1="2" x2="20" y2="8" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
        <line x1="20" y1="32" x2="20" y2="38" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
        <line x1="2" y1="20" x2="8" y2="20" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
        <line x1="32" y1="20" x2="38" y2="20" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
    </svg>
</template>

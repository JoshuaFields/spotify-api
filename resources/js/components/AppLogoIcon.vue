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
    <img src="/images/frogplanet.jpeg" alt="Frog Planet Logo" :class="className" v-bind="$attrs" />
</template>

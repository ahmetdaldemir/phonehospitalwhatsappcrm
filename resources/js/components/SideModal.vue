<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-50 overflow-hidden"
                @click.self="$emit('close')"
            >
                <div class="absolute inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                <section class="absolute inset-y-0 right-0 pl-10 max-w-full flex">
                    <Transition
                        enter-active-class="transform transition ease-in-out duration-500"
                        enter-from-class="translate-x-full"
                        enter-to-class="translate-x-0"
                        leave-active-class="transform transition ease-in-out duration-500"
                        leave-from-class="translate-x-0"
                        leave-to-class="translate-x-full"
                    >
                        <div
                            v-if="show"
                            class="w-screen max-w-md"
                        >
                            <div class="h-full flex flex-col bg-white shadow-xl overflow-y-scroll">
                                <div class="flex-1 py-6 px-4 sm:px-6">
                                    <div class="flex items-center justify-between mb-4">
                                        <h2 class="text-lg font-semibold text-gray-900">
                                            {{ title }}
                                        </h2>
                                        <button
                                            @click="$emit('close')"
                                            class="text-gray-400 hover:text-gray-500"
                                        >
                                            <XMarkIcon class="h-6 w-6" />
                                        </button>
                                    </div>
                                    <div>
                                        <slot></slot>
                                    </div>
                                </div>
                                <div v-if="showFooter" class="border-t border-gray-200 px-4 py-3 sm:px-6">
                                    <slot name="footer">
                                        <button
                                            @click="$emit('close')"
                                            class="w-full inline-flex justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                                        >
                                            Close
                                        </button>
                                    </slot>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </section>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { XMarkIcon } from '@heroicons/vue/24/outline';

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: '',
    },
    showFooter: {
        type: Boolean,
        default: true,
    },
});

defineEmits(['close']);
</script>


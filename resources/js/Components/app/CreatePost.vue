<script setup>
import { ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import PostModal from "@/Components/app/PostModal.vue";

const authUser = usePage().props.auth.user;
const showModal = ref(false);

const newPost = ref({
    id: null,
    body: "",
    user: authUser,
});

defineProps({
    group: {
        type: Object,
        default: null,
    },
});

function showCreatePostModal() {
    showModal.value = true;
}
</script>

<template>
    <div class="p-4 bg-white rounded-lg border mb-3">
        <!-- <InputTextarea
            @click="postCreating = true"
            class="mb-3 w-full"
            placeholder="Click here to create new post"
            rows="1"
            v-model="newPostForm.body"
        />
        
        <div v-if="postCreating" class="flex gap-2 justify-between">
            <button
                type="button"
                class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 relative"
            >
                Attach Files
                <input
                    type="file"
                    class="absolute left-0 top-0 right-0 bottom-0 opacity-0"
                />
            </button>
            <button
                @click="submit"
                type="submit"
                class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
            >
                Submit
            </button> -->

        <!-- </div> -->
        <div
            @click="showCreatePostModal"
            class="py-2 px-3 border-2 border-gray-200 text-gray-500 rounded-md mb-3 w-full"
        >
            Click here to create new post
        </div>
        <PostModal :post="newPost" :group="group" v-model="showModal" />
    </div>
</template>

<style scoped></style>

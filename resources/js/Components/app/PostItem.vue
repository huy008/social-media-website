<script setup>
import { router, usePage } from "@inertiajs/vue3";
import { Disclosure, DisclosureButton, DisclosurePanel } from "@headlessui/vue";
import EditDeleteDropdown from "@/Components/app/EditDeleteDropdown.vue";
import PostUserHeader from "@/Components/app/PostUserHeader.vue";
import PostAttachments from "@/Components/app/PostAttachments.vue";
import axiosClient from "@/axiosClient.js";
import {
    ChatBubbleLeftRightIcon,
    HandThumbUpIcon,
} from "@heroicons/vue/24/outline";
import CommentList from "@/Components/app/CommentList.vue";

const props = defineProps({
    post: Object,
});
const emit = defineEmits(["editClick", "attachmentClick"]);

function openEditModal() {
    emit("editClick", props.post);
}

function deletePost() {
    if (window.confirm("Are you sure you want to delete this post?")) {
        router.delete(route("post.destroy", props.post), {
            preserveScroll: true,
        });
    }
}

function openAttachment(ind) {
    emit("attachmentClick", props.post, ind);
}

function sendReaction() {
    axiosClient
        .post(route("post.reaction", props.post), {
            reaction: "like",
        })
        .then(({ data }) => {
            props.post.current_user_has_reaction =
                data.current_user_has_reaction;
            props.post.num_of_reactions = data.num_of_reactions;
        });
}
</script>

<template>
    <div class="bg-white border rounded p-4 mb-3">
        <div class="flex items-center justify-between mb-3">
            <PostUserHeader :post="post" />
            <EditDeleteDropdown
                :user="post.user"
                :post="post"
                @edit="openEditModal"
                @delete="deletePost"
            />
        </div>
        <div class="mb-3">
            <Disclosure v-slot="{ open }">
                <div
                    class="ck-content-output"
                    v-if="!open"
                    v-html="post.body.substring(0, 200)"
                />
                <template v-if="post.body.length > 200">
                    <DisclosurePanel>
                        <div class="ck-content-output" v-html="post.body" />
                    </DisclosurePanel>
                    <div class="flex justify-end">
                        <DisclosureButton class="text-blue-500 hover:underline">
                            {{ open ? "Read less" : "Read More" }}
                        </DisclosureButton>
                    </div>
                </template>
            </Disclosure>
        </div>
        <div
            class="grid gap-3 mb-3"
            :class="[
                post.attachments.length === 1 ? 'grid-cols-1' : 'grid-cols-2',
            ]"
        >
            <PostAttachments
                :attachments="post.attachments"
                @attachmentClick="openAttachment"
            />
        </div>
        <Disclosure v-slot="{ open }">
            <div class="flex gap-2">
                <button
                    @click="sendReaction"
                    class="text-gray-800 flex gap-1 items-center justify-center rounded-lg py-2 px-4 flex-1"
                    :class="[
                        post.current_user_has_reaction
                            ? 'bg-sky-100 hover:bg-sky-200'
                            : 'bg-gray-100  hover:bg-gray-200',
                    ]"
                >
                    <HandThumbUpIcon class="w-5 h-5" />
                    <span class="mr-2">{{ post.num_of_reactions }}</span>
                    {{ post.current_user_has_reaction ? "Unlike" : "Like" }}
                </button>
                <DisclosureButton
                    class="text-gray-800 flex gap-1 items-center justify-center bg-gray-100 rounded-lg hover:bg-gray-200 py-2 px-4 flex-1"
                >
                    <ChatBubbleLeftRightIcon class="w-5 h-5" />
                    <span class="mr-2">{{ post.num_of_comments }}</span>
                    Comment
                </DisclosureButton>
            </div>

            <DisclosurePanel
                class="comment-list mt-3 max-h-[400px] overflow-auto"
            >
                <CommentList :post="post" :data="{ comments: post.comments }" />
            </DisclosurePanel>
        </Disclosure>
    </div>
</template>

<style scoped></style>

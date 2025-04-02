<template>
    <v-card>
        <!-- Title and Create News Button -->
        <v-card-title>
            News
            <v-spacer></v-spacer>
            <v-btn color="primary" @click="$emit('create')">
                <v-icon left>mdi-plus</v-icon>
                Create News
            </v-btn>
        </v-card-title>

        <!-- News List -->
        <v-card-text>
            <v-progress-linear
                v-if="loading"
                indeterminate
                color="primary"
            ></v-progress-linear>

            <v-list v-if="items && items.length > 0">
                <v-list-item
                    v-for="item in items"
                    :key="item.id"
                    @click="$emit('view', item.id)"
                    :title="item.title"
                    :subtitle="formatDate(item.created_at)"
                ></v-list-item>
            </v-list>
            <div v-else-if="!loading" class="text-center pa-4">
                <p>No news items found</p>
            </div>
        </v-card-text>

        <!-- Pagination -->
        <v-card-actions v-if="totalPages > 1" class="justify-center pa-4">
            <v-pagination
                v-model="currentPage"
                :length="totalPages"
                :total-visible="7"
                @update:model-value="$emit('page-change', $event)"
                color="primary"
            ></v-pagination>
        </v-card-actions>
    </v-card>
</template>

<script lang="ts">
import { defineComponent } from "vue";

export interface NewsItem {
    id: number;
    title: string;
    created_at: string;
}

export default defineComponent({
    name: "NewsList",
    props: {
        items: {
            type: Array as () => NewsItem[],
            required: true,
            default: () => [],
        },
        loading: {
            type: Boolean,
            default: false,
        },
        currentPage: {
            type: Number,
            required: true,
        },
        totalPages: {
            type: Number,
            required: true,
        },
    },
    methods: {
        formatDate(date: string): string {
            try {
                if (!date) return "No date";
                const parsedDate = new Date(date);
                if (isNaN(parsedDate.getTime())) return "Invalid date";

                const options: Intl.DateTimeFormatOptions = {
                    year: "numeric",
                    month: "long",
                    day: "numeric",
                };
                return parsedDate.toLocaleDateString(undefined, options);
            } catch (error) {
                console.error("Error formatting date:", error);
                return "Invalid date";
            }
        },
    },
    mounted() {
        console.log("NewsList items:", this.items);
    },
});
</script>

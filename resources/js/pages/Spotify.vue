<script setup lang="ts">
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import SearchResults from '@/components/SearchResults.vue'

const form = useForm({
    query: '',
})

const searchResults = ref(null)

function search() {
    form.post(route('spotify.search'), {
        onSuccess: (page) => {
            searchResults.value = page.props.flash.results
        },
    })
}
</script>

<template>
    <AppLayout>
        <div class="container mx-auto py-12">
            <Card>
                <CardHeader>
                    <CardTitle>Search for a Song</CardTitle>
                    <CardDescription>Enter a song title to search for it on Spotify.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="search">
                        <div class="grid gap-4">
                            <Input v-model="form.query" placeholder="Enter a song title" />
                            <Button type="submit">Search</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <div class="mt-8">
                <SearchResults :searchResults="searchResults" />
            </div>
        </div>
    </AppLayout>
</template>

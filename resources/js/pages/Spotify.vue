<script setup lang="ts">
import { ref, computed } from 'vue'
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

const spotifyApiUrl = computed(() => {
    if (form.query) {
        return `https://api.spotify.com/v1/search?q=${encodeURIComponent(form.query)}&type=track&limit=10`
    }
    return ''
})

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
        <div class="container mx-auto py-12 px-4">
            <h1 class="text-4xl font-bold mb-8">Let's Make a Playlist</h1>
            <Card>
                <CardHeader>
                    <CardTitle>Search for a Song</CardTitle>
                    <CardDescription>Enter a song title to search for it on Spotify.</CardDescription>
                    <p v-if="spotifyApiUrl" class="text-sm text-muted-foreground mt-2"><span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 mr-2">GET</span>API Endpoint: <span class="font-mono text-xs">{{ spotifyApiUrl }}</span></p>
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

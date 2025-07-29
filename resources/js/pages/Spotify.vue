<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import SearchResults from '@/components/SearchResults.vue'

const page = usePage()

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

const authorizeSpotifyUrl = computed(() => {
    const clientId = page.props.spotify.clientId
    const redirectUri = page.props.spotify.redirectUri
    const scopes = 'playlist-modify-public playlist-modify-private'
    return `https://accounts.spotify.com/authorize?client_id=${clientId}&response_type=code&redirect_uri=${encodeURIComponent(redirectUri)}&scope=${encodeURIComponent(scopes)}`
})

const authForm = useForm({
    code: '',
})

function openSpotifyAuthPopup() {
    const width = 500
    const height = 600
    const left = window.screen.width / 2 - width / 2
    const top = window.screen.height / 2 - height / 2
    window.open(authorizeSpotifyUrl.value, 'SpotifyAuth', `width=${width},height=${height},top=${top},left=${left}`)
}

function search() {
    form.post(route('spotify.search'), {
        onSuccess: (page) => {
            searchResults.value = page.props.flash.results
        },
    })
}

onMounted(() => {
    window.addEventListener('message', handlePostMessage)
})

onUnmounted(() => {
    window.removeEventListener('message', handlePostMessage)
})

function handlePostMessage(event: MessageEvent) {
    if (event.origin !== window.location.origin) {
        return // Only accept messages from the same origin
    }

    if (event.data.type === 'spotify-auth-code') {
        authForm.code = event.data.code
        authForm.post(route('spotify.callback'), {
            onSuccess: () => {
                // Optionally, show a success message or refresh the page
                // to reflect the new token status
                page.props.flash.success = 'Spotify authorized successfully!'
            },
            onError: (errors) => {
                page.props.flash.error = 'Failed to authorize Spotify.'
                console.error('Spotify auth error:', errors)
            },
        })
    } else if (event.data.type === 'spotify-auth-error') {
        page.props.flash.error = `Spotify authorization denied: ${event.data.error}`
        console.error('Spotify auth error:', event.data.error)
    }
}
</script>

<template>
    <AppLayout>
        <div class="container mx-auto py-12 px-4">
            <h1 class="text-4xl font-bold mb-8">Let's Make a Playlist</h1>
            <div class="mb-4">
                <Button @click="openSpotifyAuthPopup">Authorize Spotify</Button>
            </div>
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

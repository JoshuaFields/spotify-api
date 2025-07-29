<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { useForm, usePage } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog'

defineProps<{ searchResults: any }>()

const page = usePage()
const successMessage = ref('')
const errorMessage = ref('')
const showJsonModal = ref(false)
const jsonPayload = ref('')

const form = useForm({
    trackId: '',
})

watch(() => page.props.flash.success, (message) => {
    console.log('Success message received:', message)
    successMessage.value = message
    setTimeout(() => successMessage.value = '', 3000)
})

watch(() => page.props.flash.error, (message) => {
    console.log('Error message received:', message)
    errorMessage.value = message
    setTimeout(() => errorMessage.value = '', 3000)
})

function addToPlaylist(trackId: string) {
    form.trackId = trackId
    form.post(route('spotify.playlist.add'))
}

function viewRequest(trackId: string) {
    const playlistId = "SECRET_PLAYLIST_ID"
    const payload = {
        uris: [`spotify:track:${trackId}`],
    }
    jsonPayload.value = JSON.stringify(payload, null, 2)
    showJsonModal.value = true
}
</script>

<template>
    <div>
        <div v-if="successMessage" class="bg-green-500 text-white p-4 rounded-md mb-4">
            {{ successMessage }}
        </div>
        <div v-if="errorMessage" class="bg-red-500 text-white p-4 rounded-md mb-4">
            {{ errorMessage }}
        </div>
        <Card v-if="searchResults?.tracks.items.length > 0">
            <CardHeader>
                <CardTitle>Search Results</CardTitle>
            </CardHeader>
            <CardContent>
                <div v-for="track in searchResults.tracks.items" :key="track.id" class="flex flex-col py-2 rounded-md px-2 hover:bg-muted cursor-pointer transition-colors">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-semibold">{{ track.name }}</p>
                            <p class="text-sm text-muted-foreground">{{ track.artists.map((artist: any) => artist.name).join(', ') }}</p>
                        </div>
                        <Button variant="outline" @click="addToPlaylist(track.id)">Add to Playlist</Button>
                    </div>
                    <div class="mt-2 text-sm text-muted-foreground">
                        <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-700/10 mr-2">POST</span>
                        <span class="font-mono text-xs">https://api.spotify.com/v1/playlists/SECRET_PLAYLIST_ID/tracks</span>
                        <Button variant="link" size="sm" @click="viewRequest(track.id)">View Request</Button>
                    </div>
                </div>
            </CardContent>
        </Card>
        <Card v-else-if="searchResults">
            <CardContent>
                <p>No results found.</p>
            </CardContent>
        </Card>

        <Dialog v-model:open="showJsonModal">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>JSON Request Payload</DialogTitle>
                    <DialogDescription>
                        <pre class="bg-gray-100 p-4 rounded-md overflow-auto text-sm">{{ jsonPayload }}</pre>
                    </DialogDescription>
                </DialogHeader>
            </DialogContent>
        </Dialog>
    </div>
</template>

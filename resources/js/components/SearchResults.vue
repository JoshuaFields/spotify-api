<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { useForm, usePage } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

defineProps<{ searchResults: any }>()

const page = usePage()
const successMessage = ref('')
const errorMessage = ref('')

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
                <div v-for="track in searchResults.tracks.items" :key="track.id" class="flex items-center justify-between py-2">
                    <div>
                        <p class="font-semibold">{{ track.name }}</p>
                        <p class="text-sm text-muted-foreground">{{ track.artists.map((artist: any) => artist.name).join(', ') }}</p>
                    </div>
                    <Button variant="outline" @click="addToPlaylist(track.id)">Add to Playlist</Button>
                </div>
            </CardContent>
        </Card>
        <Card v-else-if="searchResults">
            <CardContent>
                <p>No results found.</p>
            </CardContent>
        </Card>
    </div>
</template>

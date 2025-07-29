<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SpotifyController extends Controller
{
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string',
        ]);

        $token = $this->getAccessToken();

        if (!$token) {
            return redirect()->back()->with('error', 'Spotify authorization required. Please re-authorize the application.');
        }

        $response = Http::withToken($token)->get('https://api.spotify.com/v1/search', [
            'q' => $request->input('query'),
            'type' => 'track',
            'limit' => 10,
        ]);

        if ($response->successful()) {
            return redirect()->back()->with('results', $response->json());
        } else {
            Log::error('Spotify search API error', ['response' => $response->json()]);
            return redirect()->back()->with('error', 'Failed to search Spotify. Please try again.');
        }
    }

    public function addToPlaylist(Request $request)
    {
        Log::info('addToPlaylist request received', ['request' => $request->all()]);

        $request->validate([
            'trackId' => 'required|string',
        ]);

        $token = $this->getAccessToken();

        if (!$token) {
            return redirect()->back()->with('error', 'Spotify authorization required. Please re-authorize the application.');
        }

        $playlistId = env('SPOTIFY_PLAYLIST_ID');
        $trackId = $request->input('trackId');

        Log::info('Adding track to playlist', ['playlistId' => $playlistId, 'trackId' => $trackId]);

        $response = Http::withToken($token)->post("https://api.spotify.com/v1/playlists/{$playlistId}/tracks", [
            'uris' => ["spotify:track:{$trackId}"],
        ]);

        Log::info('Spotify API response', ['response' => $response->json()]);

        if ($response->successful()) {
            return redirect()->back()->with('success', 'Track added to playlist.');
        } else {
            Log::error('Spotify add to playlist API error', ['response' => $response->json()]);
            return redirect()->back()->with('error', 'Failed to add track to playlist. Please try again.');
        }
    }

    public function callback(Request $request)
    {
        $response = Http::asForm()->post('https://accounts.spotify.com/api/token', [
            'grant_type' => 'authorization_code',
            'code' => $request->input('code'),
            'redirect_uri' => config('services.spotify.redirect_uri'),
            'client_id' => config('services.spotify.client_id'),
            'client_secret' => config('services.spotify.client_secret'),
        ]);

        $tokens = $response->json();
        $tokens['expires_at'] = time() + $tokens['expires_in'];
        Storage::disk('local')->put('spotify_tokens.json', json_encode($tokens));

        return 'Tokens stored.';
    }

    private function getAccessToken()
    {
        if (!Storage::disk('local')->exists('spotify_tokens.json')) {
            return null;
        }

        $tokens = json_decode(Storage::disk('local')->get('spotify_tokens.json'), true);

        // Check if tokens are valid JSON and contain necessary keys
        if (!is_array($tokens) || !isset($tokens['access_token']) || !isset($tokens['expires_at'])) {
            Storage::disk('local')->delete('spotify_tokens.json'); // Delete invalid file
            return null;
        }

        if (time() > $tokens['expires_at']) {
            // Access token expired, try to refresh
            if (!isset($tokens['refresh_token'])) {
                Storage::disk('local')->delete('spotify_tokens.json'); // No refresh token, force re-auth
                return null;
            }

            $response = Http::asForm()->post('https://accounts.spotify.com/api/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $tokens['refresh_token'],
                'client_id' => config('services.spotify.client_id'),
                'client_secret' => config('services.spotify.client_secret'),
            ]);

            if ($response->successful()) {
                $newTokens = $response->json();
                $newTokens['expires_at'] = time() + $newTokens['expires_in'];
                // Spotify might return a new refresh token, use it if available
                if (isset($newTokens['refresh_token'])) {
                    $newTokens['refresh_token'] = $newTokens['refresh_token'];
                } else {
                    $newTokens['refresh_token'] = $tokens['refresh_token']; // Keep old if new not provided
                }
                Storage::disk('local')->put('spotify_tokens.json', json_encode($newTokens));
                return $newTokens['access_token'];
            } else {
                // Refresh failed, likely invalid refresh token. Force re-authorization.
                Log::error('Spotify token refresh failed', ['response' => $response->json()]);
                Storage::disk('local')->delete('spotify_tokens.json');
                return null;
            }
        }

        return $tokens['access_token'];
    }
}

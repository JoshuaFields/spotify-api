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

        $response = Http::withToken($token)->get('https://api.spotify.com/v1/search', [
            'q' => $request->input('query'),
            'type' => 'track',
            'limit' => 10,
        ]);

        return redirect()->back()->with('results', $response->json());
    }

    public function addToPlaylist(Request $request)
    {
        $request->validate([
            'trackId' => 'required|string',
        ]);

        $token = $this->getAccessToken();
        $playlistId = env('SPOTIFY_PLAYLIST_ID');
        $trackId = $request->input('trackId');

        $response = Http::withToken($token)->post("https://api.spotify.com/v1/playlists/{$playlistId}/tracks", [
            'uris' => ["spotify:track:{$trackId}"],
        ]);

        if ($response->successful()) {
            return redirect()->back()->with('success', 'Track added to playlist.');
        } else {
            return redirect()->back()->with('error', 'Failed to add track to playlist.');
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
        if (Storage::disk('local')->exists('spotify_tokens.json')) {
            $tokens = json_decode(Storage::disk('local')->get('spotify_tokens.json'), true);
            if (time() > $tokens['expires_at']) {
                $response = Http::asForm()->post('https://accounts.spotify.com/api/token', [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $tokens['refresh_token'],
                    'client_id' => config('services.spotify.client_id'),
                    'client_secret' => config('services.spotify.client_secret'),
                ]);
                $newTokens = $response->json();
                $newTokens['expires_at'] = time() + $newTokens['expires_in'];
                Storage::disk('local')->put('spotify_tokens.json', json_encode($newTokens));
                return $newTokens['access_token'];
            }
            return $tokens['access_token'];
        }

        return null;
    }
}

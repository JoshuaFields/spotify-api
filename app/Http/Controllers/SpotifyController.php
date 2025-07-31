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

        Log::info('Search request received.', ['query' => $request->input('query')]);

        $token = $this->getAccessToken();

        if (!$token) {
            Log::error('Search failed: No access token available.');
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
            if ($response->status() === 401) {
                Storage::disk('local')->delete('spotify_tokens.json');
                return redirect()->back()->with('error', 'Your Spotify session has expired. Please re-authorize the application.');
            }
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
            if ($response->status() === 401) {
                Storage::disk('local')->delete('spotify_tokens.json');
                return redirect()->back()->with('error', 'Your Spotify session has expired. Please re-authorize the application.');
            }
            return redirect()->back()->with('error', 'Failed to add track to playlist. Please try again.');
        }
    }

    public function callback(Request $request)
    {
        return view('spotify.callback');
    }

    public function token(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $response = Http::asForm()->post('https://accounts.spotify.com/api/token', [
            'grant_type' => 'authorization_code',
            'code' => $request->input('code'),
            'redirect_uri' => config('services.spotify.redirect_uri'),
            'client_id' => config('services.spotify.client_id'),
            'client_secret' => config('services.spotify.client_secret'),
        ]);

        if ($response->successful()) {
            $tokens = $response->json();
            Log::info('Spotify token exchange successful.', ['response' => $tokens]);

            $tokens['expires_at'] = time() + $tokens['expires_in'];
            session([
                'spotify_access_token' => $tokens['access_token'],
                'spotify_refresh_token' => $tokens['refresh_token'],
                'spotify_token_expires_at' => $tokens['expires_at'],
            ]);

            Log::info('Spotify tokens stored in session.');

            return redirect()->back()->with('success', 'Spotify authorized successfully!');
        } else {
            Log::error('Spotify token exchange failed', ['response' => $response->json()]);
            return redirect()->route('spotify')->with('error', 'Failed to authorize Spotify. Please try again.');
        }
    }

    private function getAccessToken()
    {
        Log::info('getAccessToken called.');

        if (!session()->has('spotify_access_token')) {
            Log::warning('Spotify access token not found in session.');
            return null;
        }

        Log::info('Spotify access token found in session.');

        if (time() > session('spotify_token_expires_at')) {
            Log::info('Spotify access token expired. Attempting to refresh.');

            if (!session()->has('spotify_refresh_token')) {
                Log::error('Spotify refresh token not found in session. Forcing re-authentication.');
                session()->forget([
                    'spotify_access_token',
                    'spotify_refresh_token',
                    'spotify_token_expires_at',
                ]);
                return null;
            }

            $response = Http::asForm()->post('https://accounts.spotify.com/api/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => session('spotify_refresh_token'),
                'client_id' => config('services.spotify.client_id'),
                'client_secret' => config('services.spotify.client_secret'),
            ]);

            if ($response->successful()) {
                $newTokens = $response->json();
                Log::info('Spotify token refresh successful.', ['response' => $newTokens]);

                $newTokens['expires_at'] = time() + $newTokens['expires_in'];
                
                session([
                    'spotify_access_token' => $newTokens['access_token'],
                    'spotify_token_expires_at' => $newTokens['expires_at'],
                ]);

                if (isset($newTokens['refresh_token'])) {
                    session(['spotify_refresh_token' => $newTokens['refresh_token']]);
                }

                Log::info('Refreshed Spotify tokens stored in session.');

                return $newTokens['access_token'];
            } else {
                Log::error('Spotify token refresh failed', ['response' => $response->json()]);
                session()->forget([
                    'spotify_access_token',
                    'spotify_refresh_token',
                    'spotify_token_expires_at',
                ]);
                return null;
            }
        }

        Log::info('Returning Spotify access token from session.');
        return session('spotify_access_token');
    }
}

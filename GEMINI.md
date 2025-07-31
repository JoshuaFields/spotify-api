This is a Laravel application that uses Vue.js (Version 3), Inertia.js, and Tailwind CSS.

The application features a `/spotify` page that serves as a playground for interacting with the Spotify API. Key functionalities include:

-   **Spotify Authorization:** Users can authorize the application with their Spotify account to obtain a refresh token.
-   **Song Search:** The application allows users to search for songs using the Spotify API.
-   **Playlist Management:** Users can add searched songs to their Spotify playlists.

Key libraries and technologies used in this project include:

-   **Backend:**
    -   Laravel: A PHP web application framework.
    -   `inertiajs/inertia-laravel`: A library for building single-page applications using classic server-side routing and controllers.
    -   `jwilsson/spotify-web-api-php`: A PHP wrapper for the Spotify Web API.

-   **Frontend:**
    -   Vue.js 3: A progressive JavaScript framework for building user interfaces.
    -   Inertia.js: The glue that connects the Laravel backend to the Vue.js frontend.
    -   Tailwind CSS: A utility-first CSS framework for rapid UI development.
    -   Vite: A modern frontend build tool that provides a faster and leaner development experience.
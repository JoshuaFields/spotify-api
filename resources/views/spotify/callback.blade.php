<!DOCTYPE html>
<html>
<head>
    <title>Spotify Authorization Callback</title>
</head>
<body>
    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const code = urlParams.get('code');

        if (window.opener && code) {
            window.opener.postMessage({ type: 'spotify-auth-code', code: code }, window.location.origin);
            window.close();
        } else if (window.opener && urlParams.get('error')) {
            window.opener.postMessage({ type: 'spotify-auth-error', error: urlParams.get('error') }, window.location.origin);
            window.close();
        } else {
            document.body.innerHTML = 'Authentication failed or window not opened by application.';
        }
    </script>
</body>
</html>

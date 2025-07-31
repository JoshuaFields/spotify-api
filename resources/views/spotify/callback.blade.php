<!DOCTYPE html>
<html>
<head>
    <title>Spotify Authorization Callback</title>
</head>
<body>
    <script>
        console.log('Callback window loaded.');
        const urlParams = new URLSearchParams(window.location.search);
        const code = urlParams.get('code');
        console.log('Authorization code:', code);
        console.log('Target origin:', window.location.origin);

        if (window.opener && code) {
            console.log('Opener window found, sending message...');
            window.opener.postMessage({ type: 'spotify-auth-code', code: code }, window.location.origin);
            window.close();
        } else if (window.opener && urlParams.get('error')) {
            console.log('Opener window found, sending error message...');
            window.opener.postMessage({ type: 'spotify-auth-error', error: urlParams.get('error') }, window.location.origin);
            window.close();
        } else {
            console.error('Authentication failed: Could not find opener window or authorization code.');
            document.body.innerHTML = 'Authentication failed or window not opened by application.';
        }
    </script>
</body>
</html>

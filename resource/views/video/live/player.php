<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Live Player</title>
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
</head>
<body>
<h1>Live Stream</h1>
<video id="video" controls autoplay></video>

<script>
    const video = document.getElementById('video');
    const src = 'http://localhost:2346/hls/stream.m3u8';

    if (Hls.isSupported()) {
        const hls = new Hls();
        hls.loadSource(src);
        hls.attachMedia(video);
        hls.on(Hls.Events.MANIFEST_PARSED, () => video.play());
    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
        video.src = src;
        video.addEventListener('loadedmetadata', () => video.play());
    }
</script>
</body>
</html>

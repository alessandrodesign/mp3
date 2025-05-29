<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Publicador</title>
</head>
<body>
<h1>Transmissão</h1>
<video id="localVideo" autoplay muted playsinline></video>

<script>
    const localVideo = document.getElementById('localVideo');
    const peer = new RTCPeerConnection();
    const socket = new WebSocket('ws://localhost:2346');

    socket.onopen = () => {
        socket.send(JSON.stringify({ type: 'role', role: 'publisher' }));
        startStream();
    };

    socket.onmessage = async (event) => {
        const data = JSON.parse(event.data);

        if (data.type === 'answer') {
            await peer.setRemoteDescription(data);
        } else if (data.type === 'candidate') {
            await peer.addIceCandidate(data);
        }
    };

    async function startStream() {
        const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
        localVideo.srcObject = stream;

        stream.getTracks().forEach(track => peer.addTrack(track, stream));

        const offer = await peer.createOffer();
        await peer.setLocalDescription(offer);

        socket.send(JSON.stringify(offer));

        peer.onicecandidate = e => {
            if (e.candidate) {
                socket.send(JSON.stringify({ type: 'candidate', ...e.candidate }));
            }
        };
    }
</script>
</body>
</html>

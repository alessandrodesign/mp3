<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Espectador</title>
</head>
<body>
<h1>Assistir</h1>
<video id="remoteVideo" autoplay playsinline></video>

<script>
    const remoteVideo = document.getElementById('remoteVideo');
    const peer = new RTCPeerConnection();
    const socket = new WebSocket('ws://localhost:2346');

    peer.ontrack = e => {
        remoteVideo.srcObject = e.streams[0];
    };

    socket.onopen = () => {
        socket.send(JSON.stringify({ type: 'role', role: 'viewer' }));
    };

    socket.onmessage = async (event) => {
        const data = JSON.parse(event.data);

        if (data.type === 'offer') {
            await peer.setRemoteDescription(data);
            const answer = await peer.createAnswer();
            await peer.setLocalDescription(answer);
            socket.send(JSON.stringify(answer));
        } else if (data.type === 'candidate') {
            await peer.addIceCandidate(data);
        }
    };

    peer.onicecandidate = e => {
        if (e.candidate) {
            socket.send(JSON.stringify({ type: 'candidate', ...e.candidate }));
        }
    };
</script>
</body>
</html>

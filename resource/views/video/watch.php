<!DOCTYPE html>
<html>
<head>
    <title>Receptor WebRTC</title>
</head>
<body>
<h1>Receptor</h1>
<video id="remoteVideo" autoplay playsinline style="width: 480px; height: 360px; background: black;"></video>
<div id="log"></div>

<script>
    const remoteVideo = document.getElementById('remoteVideo');
    const signalingServerUrl = 'ws://127.0.0.1:8080';
    const ws = new WebSocket(signalingServerUrl);
    const log = document.getElementById('log');

    let peerConnection;
    const config = {iceServers: [{urls: 'stun:stun.l.google.com:19302'}]};

    function logMessage(message) {
        console.log(message);
        log.innerHTML += message + '<br>';
    }

    function createPeerConnection() {
        logMessage('Criando PeerConnection...');
        peerConnection = new RTCPeerConnection(config);

        peerConnection.onicecandidate = event => {
            if (event.candidate) {
                logMessage('Enviando ICE candidate: ' + JSON.stringify(event.candidate));
                ws.send(JSON.stringify({iceCandidate: event.candidate}));
            }
        };

        peerConnection.ontrack = event => {
            logMessage('Recebendo track de vídeo...');
            remoteVideo.srcObject = event.streams[0];
        };
    }

    ws.onopen = () => {
        logMessage('Conectado ao servidor de sinalização.');
    };

    ws.onmessage = async (message) => {
        const data = JSON.parse(message.data);
        logMessage('Mensagem recebida: ' + JSON.stringify(data));

        if (data.offer) {
            if (!peerConnection) {
                createPeerConnection();
            }
            logMessage('Recebendo offer...');
            await peerConnection.setRemoteDescription(new RTCSessionDescription(data.offer));
            const answer = await peerConnection.createAnswer();
            logMessage('Enviando answer...');
            await peerConnection.setLocalDescription(answer);
            ws.send(JSON.stringify({answer}));
        } else if (data.iceCandidate) {
            if (peerConnection) {
                try {
                    logMessage('Adicionando ICE candidate...');
                    await peerConnection.addIceCandidate(data.iceCandidate);
                } catch (e) {
                    console.error('Erro ao adicionar ICE candidate', e);
                    logMessage('Erro ao adicionar ICE candidate: ' + e.message);
                }
            }
        }
    };

    ws.onerror = (error) => {
        logMessage('Erro no WebSocket: ' + error);
    };

    ws.onclose = () => {
        logMessage('Conexão WebSocket fechada.');
    };
</script>
</body>
</html>
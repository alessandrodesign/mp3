<!DOCTYPE html>
<html>
<head>
    <title>Emissor WebRTC com Logs e Seleção de Câmera</title>
</head>
<body>
<h1>Emissor</h1>

<label for="videoSource">Selecione a câmera:</label>
<select id="videoSource"></select>

<video id="localVideo" autoplay muted playsinline style="width: 480px; height: 360px; background: black;"></video>

<div id="log"
     style="white-space: pre-wrap; background: #eee; padding: 10px; margin-top: 10px; height: 150px; overflow-y: auto;"></div>

<script>
    const localVideo = document.getElementById('localVideo');
    const videoSelect = document.getElementById('videoSource');
    const log = document.getElementById('log');
    const signalingServerUrl = 'ws://127.0.0.1:8080';
    let ws;
    let localStream;
    let peerConnection;
    const config = {iceServers: [{urls: 'stun:stun.l.google.com:19302'}]};

    function logMessage(message) {
        console.log(message);
        log.textContent += message + '\n';
        log.scrollTop = log.scrollHeight;
    }

    async function getDevices() {
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const videoDevices = devices.filter(device => device.kind === 'videoinput');
            videoSelect.innerHTML = '';
            videoDevices.forEach(device => {
                const option = document.createElement('option');
                option.value = device.deviceId;
                option.text = device.label || `Camera ${videoSelect.length + 1}`;
                videoSelect.appendChild(option);
            });
            logMessage(`Encontradas ${videoDevices.length} câmeras.`);
        } catch (err) {
            logMessage('Erro ao listar dispositivos: ' + err.message);
        }
    }

    async function start() {
        if (localStream) {
            localStream.getTracks().forEach(track => track.stop());
        }

        const videoSource = videoSelect.value;
        const constraints = {
            video: {deviceId: videoSource ? {exact: videoSource} : undefined},
            audio: true
        };

        try {
            localStream = await navigator.mediaDevices.getUserMedia(constraints);
            logMessage('Captura iniciada com sucesso.');
            logMessage(`Câmeras capturadas: ${localStream.getVideoTracks().length}`);
            logMessage(`Áudio capturado: ${localStream.getAudioTracks().length > 0 ? 'Sim' : 'Não'}`);

            localVideo.srcObject = localStream;

            if (peerConnection) {
                peerConnection.close();
            }

            peerConnection = new RTCPeerConnection(config);

            localStream.getTracks().forEach(track => {
                peerConnection.addTrack(track, localStream);
                logMessage(`Adicionando track: ${track.kind}`);
            });

            peerConnection.onicecandidate = event => {
                if (event.candidate) {
                    logMessage('Enviando ICE candidate...');
                    ws.send(JSON.stringify({iceCandidate: event.candidate}));
                }
            };

            const offer = await peerConnection.createOffer();
            await peerConnection.setLocalDescription(offer);
            logMessage('Enviando offer...');
            ws.send(JSON.stringify({offer}));

        } catch (err) {
            logMessage('Erro ao capturar mídia: ' + err.message);
        }
    }

    function setupWebSocket() {
        ws = new WebSocket(signalingServerUrl);

        ws.onopen = () => {
            logMessage('Conectado ao servidor de sinalização.');
            start();
        };

        ws.onmessage = async (message) => {
            const data = JSON.parse(message.data);
            logMessage('Mensagem recebida: ' + JSON.stringify(data));

            if (data.answer) {
                logMessage('Recebendo answer...');
                await peerConnection.setRemoteDescription(new RTCSessionDescription(data.answer));
            } else if (data.iceCandidate) {
                try {
                    logMessage('Adicionando ICE candidate...');
                    await peerConnection.addIceCandidate(data.iceCandidate);
                } catch (e) {
                    logMessage('Erro ao adicionar ICE candidate: ' + e.message);
                }
            }
        };

        ws.onerror = (error) => {
            logMessage('Erro no WebSocket: ' + error.message);
        };

        ws.onclose = () => {
            logMessage('Conexão WebSocket fechada.');
        };
    }

    videoSelect.onchange = () => {
        logMessage('Câmera selecionada alterada, reiniciando captura...');
        start();
    };

    // Inicialização
    getDevices().then(() => {
        setupWebSocket();
    });
</script>
</body>
</html>
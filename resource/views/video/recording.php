<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gravação de Vídeo</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        main {
            background: #fff;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 100%;
        }

        h1 {
            text-align: center;
            margin-bottom: 1rem;
            font-size: 1.8rem;
        }

        video {
            width: 100%;
            max-height: 360px;
            background: black;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .controls {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        button {
            flex: 1;
            padding: 10px;
            font-size: 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button#startBtn {
            background-color: #28a745;
            color: white;
        }

        button#startBtn:hover {
            background-color: #218838;
        }

        button#stopBtn {
            background-color: #dc3545;
            color: white;
        }

        button#stopBtn:hover {
            background-color: #c82333;
        }

        .info {
            font-size: 14px;
            margin-top: 0.5rem;
        }

        #status {
            margin-top: 1rem;
            font-weight: bold;
        }

        h2 {
            margin-top: 2rem;
            font-size: 1.4rem;
            text-align: center;
        }

        @media (max-width: 600px) {
            main {
                padding: 1rem;
            }
            .controls {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<main>
    <h1>Gravação de Vídeo</h1>

    <video id="preview" autoplay muted playsinline></video>

    <div class="controls">
        <button id="startBtn">Iniciar Gravação</button>
        <button id="stopBtn" disabled>Parar Gravação</button>
    </div>

    <div class="info" id="timer">⏱️ Tempo: 00:00</div>
    <div class="info" id="size">💾 Tamanho: 0 KB</div>
    <div id="status"></div>

    <h2>Prévia da Gravação</h2>
    <video id="recorded" controls></video>
</main>

<script>
    const preview = document.getElementById('preview');
    const recorded = document.getElementById('recorded');
    const startBtn = document.getElementById('startBtn');
    const stopBtn = document.getElementById('stopBtn');
    const status = document.getElementById('status');
    const timerDisplay = document.getElementById('timer');
    const sizeDisplay = document.getElementById('size');

    let mediaRecorder;
    let recordedChunks = [];
    let currentSizeBytes = 0;
    let timerInterval;
    const maxRecordingTime = 10000;
    const maxSizeBytes = 2 * 1024 * 1024;

    function formatTime(ms) {
        const seconds = Math.floor((ms / 1000) % 60).toString().padStart(2, '0');
        const minutes = Math.floor((ms / (1000 * 60)) % 60).toString().padStart(2, '0');
        return `${minutes}:${seconds}`;
    }

    function formatSize(bytes) {
        return `${(bytes / 1024).toFixed(0)} KB`;
    }

    async function initMedia() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
            preview.srcObject = stream;
            mediaRecorder = new MediaRecorder(stream);

            mediaRecorder.ondataavailable = e => {
                if (e.data.size > 0) {
                    recordedChunks.push(e.data);
                    currentSizeBytes += e.data.size;
                    sizeDisplay.textContent = `💾 Tamanho: ${formatSize(currentSizeBytes)}`;

                    if (currentSizeBytes > maxSizeBytes) {
                        stopRecording('⚠️ Tamanho máximo atingido!');
                    }
                }
            };

            mediaRecorder.onstop = async () => {
                clearInterval(timerInterval);
                const blob = new Blob(recordedChunks, { type: 'video/webm' });
                recorded.src = URL.createObjectURL(blob);

                // Reset
                recordedChunks = [];
                currentSizeBytes = 0;

                // Envio
                status.textContent = '📤 Enviando vídeo...';
                const formData = new FormData();
                formData.append('video', blob, 'gravado.webm');

                try {
                    const response = await fetch('/video/upload', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.text();
                    status.textContent = '✅ Upload concluído: ' + result;
                } catch (err) {
                    status.textContent = '❌ Erro no upload: ' + err.message;
                }
            };

            startBtn.onclick = () => {
                recordedChunks = [];
                currentSizeBytes = 0;
                mediaRecorder.start();
                startBtn.disabled = true;
                stopBtn.disabled = false;
                status.textContent = '🔴 Gravando...';
                const startTime = Date.now();

                timerInterval = setInterval(() => {
                    const elapsed = Date.now() - startTime;
                    timerDisplay.textContent = `⏱️ Tempo: ${formatTime(elapsed)}`;
                    if (elapsed >= maxRecordingTime) {
                        stopRecording('⏳ Tempo limite atingido!');
                    }
                }, 100);
            };

            stopBtn.onclick = () => {
                stopRecording('🛑 Gravação encerrada');
            };

        } catch (err) {
            alert('🚫 Erro ao acessar câmera/microfone: ' + err.message);
        }
    }

    function stopRecording(message) {
        if (mediaRecorder && mediaRecorder.state === 'recording') {
            mediaRecorder.stop();
            startBtn.disabled = false;
            stopBtn.disabled = true;
            status.textContent = message;
            clearInterval(timerInterval);
        }
    }

    initMedia();
</script>

</body>
</html>

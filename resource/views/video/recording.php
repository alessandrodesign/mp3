<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Gravação de Vídeo</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        video { border: 1px solid #ccc; width: 480px; height: 360px; background: black; }
        button { margin-top: 10px; padding: 10px 20px; font-size: 16px; }
        #status { margin-top: 10px; }
        #timer { margin-top: 10px; font-size: 14px; }
        #size { margin-top: 10px; font-size: 14px; }
    </style>
</head>
<body>

<h1>Gravação de Vídeo</h1>

<video id="preview" autoplay muted></video>
<br />
<button id="startBtn">Iniciar Gravação</button>
<button id="stopBtn" disabled>Parar Gravação</button>

<div id="status"></div>
<div id="timer">Tempo: 00:00</div>
<div id="size">Tamanho: 0 KB</div>

<h2>Vídeo Gravado</h2>
<video id="recorded" controls></video>

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
    const maxRecordingTime = 10000; // 10 segundos
    const maxSizeBytes = 2 * 1024 * 1024; // 2MB
    let currentSizeBytes = 0;
    let startTime;
    let timerInterval;

    function formatTime(ms) {
        const seconds = Math.floor((ms / 1000) % 60).toString().padStart(2, '0');
        const minutes = Math.floor((ms / (1000 * 60)) % 60).toString().padStart(2, '0');
        return `${minutes}:${seconds}`;
    }

    function formatSize(bytes) {
        const kb = bytes / 1024;
        return `${kb.toFixed(0)} KB`;
    }

    async function init() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
            preview.srcObject = stream;

            mediaRecorder = new MediaRecorder(stream);

            mediaRecorder.ondataavailable = e => {
                if (e.data.size > 0) {
                    currentSizeBytes += e.data.size;
                    sizeDisplay.textContent = `Tamanho: ${formatSize(currentSizeBytes)}`;

                    if (currentSizeBytes > maxSizeBytes) {
                        stopRecording('Tamanho máximo atingido!');
                        return;
                    }
                    recordedChunks.push(e.data);
                }
            };

            mediaRecorder.onstop = async () => {
                clearInterval(timerInterval);
                const blob = new Blob(recordedChunks, { type: 'video/webm' });
                recordedChunks = [];
                currentSizeBytes = 0;

                // Mostrar vídeo gravado localmente
                recorded.src = URL.createObjectURL(blob);

                // Enviar para o servidor
                status.textContent = 'Enviando vídeo...';
                const formData = new FormData();
                formData.append('video', blob, 'recorded.webm');

                try {
                    const response = await fetch('/video/upload', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.text();
                    status.textContent = 'Upload concluído: ' + result;
                } catch (err) {
                    status.textContent = 'Erro no upload: ' + err.message;
                }
            };

            function startRecording() {
                mediaRecorder.start();
                startBtn.disabled = true;
                stopBtn.disabled = false;
                status.textContent = 'Gravando...';
                startTime = Date.now();

                timerInterval = setInterval(() => {
                    const elapsedTime = Date.now() - startTime;
                    timerDisplay.textContent = `Tempo: ${formatTime(elapsedTime)}`;

                    if (elapsedTime >= maxRecordingTime) {
                        stopRecording('Tempo limite atingido!');
                    }
                }, 100);
            }

            function stopRecording(message = 'Parando gravação...') {
                if (mediaRecorder.state === 'recording') {
                    mediaRecorder.stop();
                    startBtn.disabled = false;
                    stopBtn.disabled = true;
                    status.textContent = message;
                    clearInterval(timerInterval);
                }
            }

            startBtn.onclick = () => {
                startRecording();
            };

            stopBtn.onclick = () => {
                stopRecording('Parando gravação...');
            };
        } catch (err) {
            alert('Erro ao acessar câmera/microfone: ' + err.message);
        }
    }

    init();
</script>

</body>
</html>
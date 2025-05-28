<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Player de Música</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #2d2d2d, #1f1f1f);
            color: #fff;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        h1 {
            margin-bottom: 20px;
            font-size: 2rem;
            color: #f0f0f0;
        }

        audio {
            width: 100%;
            max-width: 400px;
            margin-bottom: 20px;
            background: #333;
            border-radius: 8px;
        }

        #playButton {
            background-color: #1db954;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 30px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        #playButton:hover {
            background-color: #17a144;
        }

        @media (max-width: 480px) {
            #playButton {
                width: 90%;
            }
        }
    </style>
</head>
<body>

<h1>🎵 Player de Música</h1>

<audio id="audioPlayer" controls preload="auto">
    <source src="/music/listen/<?= htmlspecialchars($music) ?>" type="<?= htmlspecialchars($mimeType) ?>">
    Seu navegador não suporta o elemento de áudio.
</audio>

<button id="playButton">▶️ Tocar Música</button>

<script>
    const audio = document.getElementById('audioPlayer');
    const playButton = document.getElementById('playButton');

    playButton.addEventListener('click', () => {
        audio.play().catch(error => {
            console.error('Erro ao tentar reproduzir:', error);
        });
    });

    audio.addEventListener('progress', () => {
        if (audio.buffered.length > 0) {
            const bufferedEnd = audio.buffered.end(audio.buffered.length - 1);
            const duration = audio.duration;
            console.log(`Buffered: ${(bufferedEnd / duration * 100).toFixed(2)}%`);
        }
    });

    audio.addEventListener('canplaythrough', () => {
        console.log('Áudio pronto para tocar sem interrupções');
    });

    function tryAutoplay() {
        audio.play().then(() => {
            console.log("Autoplay iniciado com sucesso!");
        }).catch(error => {
            console.warn("Autoplay falhou:", error);
        });
        document.removeEventListener('click', tryAutoplay);
        document.removeEventListener('touchstart', tryAutoplay);
    }

    document.addEventListener('click', tryAutoplay);
    document.addEventListener('touchstart', tryAutoplay);
</script>

</body>
</html>

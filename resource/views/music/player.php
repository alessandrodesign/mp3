<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Player de Música</title>
</head>
<body>

<audio id="audioPlayer" controls preload="auto">
    <source src="/music/listen/<?= htmlspecialchars($music) ?>" type="<?= htmlspecialchars($mimeType) ?>">
    Seu navegador não suporta o elemento de áudio.
</audio>

<button id="playButton">Tocar Música</button>

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

    // Opcional: tentar autoplay após a primeira interação do usuário
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
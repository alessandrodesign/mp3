<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Player de Áudio Customizado</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 400px;
            margin: 20px auto;
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 20px;
        }

        #cover {
            width: 150px;
            height: 150px;
            border-radius: 8px;
            object-fit: cover;
            display: block;
            margin: 0 auto 10px auto;
        }

        #title {
            text-align: center;
            font-size: 1.2em;
            margin: 0;
        }

        #artist {
            text-align: center;
            color: #666;
            margin: 0 0 10px 0;
        }

        #controls {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        #controls button {
            font-size: 1.5em;
            cursor: pointer;
        }

        #progress-container {
            width: 100%;
            height: 8px;
            background: #ddd;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 10px;
        }

        #progress {
            height: 8px;
            background: #4CAF50;
            border-radius: 4px;
            width: 0;
        }

        #time {
            display: flex;
            justify-content: space-between;
            font-size: 0.8em;
            color: #666;
        }

        #volume-container {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        #volume-slider {
            width: 80px;
            margin: 0 10px;
        }

        #lyrics {
            background: #f9f9f9;
            padding: 10px;
            border-radius: 4px;
            max-height: 150px;
            overflow-y: auto;
            white-space: pre-wrap;
            font-size: 0.9em;
        }

        #playlist {
            margin-top: 15px;
            max-height: 150px;
            overflow-y: auto;
            padding: 0;
            list-style: none;
        }

        #playlist li {
            padding: 5px;
            cursor: pointer;
        }

        #playlist li.active {
            background-color: #ddd;
        }

        #addSongBtn {
            margin-top: 15px;
            padding: 8px 12px;
            cursor: pointer;
        }

        #playback-rate-container {
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #playback-rate-select {
            margin-left: 10px;
        }
    </style>
</head>
<body>

<script>
    (function () {
        // Playlist inicial
        const playlist = <?=$musics?>;

        let currentIndex = 0;
        let isPlaying = false;
        let isMuted = false;
        let crossfadeTimeout;

        // Configurações
        const crossfadeDuration = 2; // segundos

        // Criar elementos
        const cover = document.createElement('img');
        cover.id = 'cover';

        const title = document.createElement('h3');
        title.id = 'title';

        const artist = document.createElement('h4');
        artist.id = 'artist';

        const controls = document.createElement('div');
        controls.id = 'controls';

        const btnPrev = document.createElement('button');
        btnPrev.textContent = '⏮️';
        btnPrev.title = 'Anterior';

        const btnPlay = document.createElement('button');
        btnPlay.textContent = '▶️';
        btnPlay.title = 'Play/Pause';

        const btnNext = document.createElement('button');
        btnNext.textContent = '⏭️';
        btnNext.title = 'Próximo';

        controls.appendChild(btnPrev);
        controls.appendChild(btnPlay);
        controls.appendChild(btnNext);

        // Barra de progresso
        const progressContainer = document.createElement('div');
        progressContainer.id = 'progress-container';

        const progress = document.createElement('div');
        progress.id = 'progress';

        progressContainer.appendChild(progress);

        // Tempo
        const time = document.createElement('div');
        time.id = 'time';

        const currentTimeEl = document.createElement('span');
        currentTimeEl.id = 'current-time';
        currentTimeEl.textContent = '0:00';

        const durationTimeEl = document.createElement('span');
        durationTimeEl.id = 'duration-time';
        durationTimeEl.textContent = '0:00';

        time.appendChild(currentTimeEl);
        time.appendChild(durationTimeEl);

        // Volume
        const volumeContainer = document.createElement('div');
        volumeContainer.id = 'volume-container';

        const volumeBtn = document.createElement('button');
        volumeBtn.textContent = '🔊';
        volumeBtn.title = 'Mudo';

        const volumeSlider = document.createElement('input');
        volumeSlider.type = 'range';
        volumeSlider.id = 'volume-slider';
        volumeSlider.min = '0';
        volumeSlider.max = '1';
        volumeSlider.step = '0.01';
        volumeSlider.value = '1';

        volumeContainer.appendChild(volumeBtn);
        volumeContainer.appendChild(volumeSlider);

        const lyrics = document.createElement('div');
        lyrics.id = 'lyrics';

        const playlistEl = document.createElement('ul');
        playlistEl.id = 'playlist';

        const addSongBtn = document.createElement('button');
        addSongBtn.id = 'addSongBtn';
        addSongBtn.textContent = 'Adicionar Música Exemplo';

        // Velocidade de reprodução
        const playbackRateContainer = document.createElement('div');
        playbackRateContainer.id = 'playback-rate-container';

        const playbackRateLabel = document.createElement('label');
        playbackRateLabel.textContent = 'Velocidade:';

        const playbackRateSelect = document.createElement('select');
        playbackRateSelect.id = 'playback-rate-select';

        const playbackRates = [0.5, 0.75, 1, 1.25, 1.5, 2];
        playbackRates.forEach(rate => {
            const option = document.createElement('option');
            option.value = rate;
            option.textContent = rate + 'x';
            playbackRateSelect.appendChild(option);
        });

        playbackRateContainer.appendChild(playbackRateLabel);
        playbackRateContainer.appendChild(playbackRateSelect);

        // Criar elemento audio via JS (não no HTML)
        const audio = new Audio();

        // Função para formatar o tempo
        function formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = Math.floor(seconds % 60);
            return `${minutes}:${remainingSeconds < 10 ? '0' : ''}${remainingSeconds}`;
        }

        // Função para atualizar UI com música atual
        function updateUI() {
            const song = playlist[currentIndex];
            cover.src = song.cover;
            cover.alt = `Capa de ${song.title}`;
            title.textContent = song.title;
            artist.textContent = song.artist;
            lyrics.textContent = song.lyrics;
            audio.src = song.src + '?token=<?=$token?>';
            updatePlaylistUI();
        }

        // Atualizar lista da playlist com destaque
        function updatePlaylistUI() {
            playlistEl.innerHTML = '';
            playlist.forEach((song, i) => {
                const li = document.createElement('li');
                li.textContent = `${song.title} - ${song.artist}`;
                if (i === currentIndex) li.classList.add('active');
                li.addEventListener('click', () => {
                    currentIndex = i;
                    playSong();
                });
                playlistEl.appendChild(li);
            });
        }

        // Play ou pause
        function togglePlay() {
            if (isPlaying) {
                audio.pause();
            } else {
                audio.play();
            }
        }

        // Função para iniciar o crossfade
        function startCrossfade() {
            let volume = 1;
            const fadeOutInterval = setInterval(() => {
                if (volume > 0) {
                    volume -= 0.1;
                    if (volume > 0) {
                        audio.volume = volume;
                    } else {
                        audio.volume = 0;
                    }
                } else {
                    clearInterval(fadeOutInterval);
                    audio.pause();
                    audio.volume = 1; // reset volume
                    nextSong();
                }
            }, (crossfadeDuration * 1000) / 10); // Ajustar o intervalo
        }

        // Play música atual
        function playSong() {
            updateUI();
            audio.addEventListener('canplaythrough', function () {
                audio.play();
            });
        }

        // Próxima música
        function nextSong() {
            currentIndex = (currentIndex + 1) % playlist.length;
            playSong();
        }

        // Música anterior
        function prevSong() {
            currentIndex = (currentIndex - 1 + playlist.length) % playlist.length;
            playSong();
        }

        // Eventos
        btnPlay.addEventListener('click', () => {
            togglePlay();
        });

        btnNext.addEventListener('click', () => {
            startCrossfade();
        });

        btnPrev.addEventListener('click', () => {
            prevSong();
        });

        audio.addEventListener('play', () => {
            isPlaying = true;
            btnPlay.textContent = '⏸️';
        });

        audio.addEventListener('pause', () => {
            isPlaying = false;
            btnPlay.textContent = '▶️';
        });

        audio.addEventListener('ended', () => {
            startCrossfade();
        });

        audio.addEventListener('timeupdate', () => {
            const currentTime = audio.currentTime;
            const duration = audio.duration;

            // Atualizar barra de progresso
            const progressPercent = (currentTime / duration) * 100;
            progress.style.width = `${progressPercent}%`;

            // Atualizar tempo
            currentTimeEl.textContent = formatTime(currentTime);
            durationTimeEl.textContent = formatTime(duration);
        });

        progressContainer.addEventListener('click', (e) => {
            const width = progressContainer.offsetWidth;
            const clickX = e.offsetX;
            const duration = audio.duration;
            audio.currentTime = (clickX / width) * duration;
        });

        // Volume
        volumeBtn.addEventListener('click', () => {
            isMuted = !isMuted;
            if (isMuted) {
                audio.muted = true;
                volumeBtn.textContent = '🔇';
            } else {
                audio.muted = false;
                volumeBtn.textContent = '🔊';
            }
        });

        volumeSlider.addEventListener('input', () => {
            audio.volume = volumeSlider.value;
        });

        // Velocidade de reprodução
        playbackRateSelect.addEventListener('change', () => {
            audio.playbackRate = playbackRateSelect.value;
        });

        addSongBtn.addEventListener('click', () => {
            playlist.push({
                title: 'New Song',
                artist: 'New Artist',
                src: 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-3.mp3',
                cover: 'https://via.placeholder.com/150?text=New+Cover',
                lyrics: 'New song lyrics line 1\nNew song lyrics line 2'
            });
            updatePlaylistUI();
        });

        // Montar DOM
        document.body.appendChild(cover);
        document.body.appendChild(title);
        document.body.appendChild(artist);
        document.body.appendChild(controls);
        document.body.appendChild(progressContainer);
        document.body.appendChild(time);
        document.body.appendChild(volumeContainer);
        document.body.appendChild(lyrics);
        document.body.appendChild(playlistEl);
        document.body.appendChild(playbackRateContainer);
        document.body.appendChild(addSongBtn);

        // Inicializar player
        updateUI();

    })();
</script>

</body>
</html>
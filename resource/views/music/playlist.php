<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"/>
    <title>🎵 Player de Música</title>
    <style>
        body {
            font-family: sans-serif;
            text-align: center;
            background: #111;
            color: #fff;
        }

        img {
            width: 200px;
            border-radius: 1rem;
            margin: 1rem auto;
            display: block;
        }

        .controls button, select {
            margin: 0.3rem;
            padding: 0.5rem 1rem;
        }

        .progress-container {
            width: 80%;
            height: 10px;
            background: #444;
            margin: 1rem auto;
            cursor: pointer;
            border-radius: 5px;
        }

        .progress {
            height: 100%;
            background: #4caf50;
            width: 0;
            border-radius: 5px;
        }

        .lyrics, .lrc-line {
            white-space: pre-wrap;
            margin: 1rem auto;
            max-width: 600px;
            text-align: left;
        }

        .highlight {
            color: #4caf50;
            font-weight: bold;
        }
    </style>
</head>
<body>

<img id="cover" src="" alt="Capa"/>
<h2 id="title"></h2>
<p id="artist"></p>

<div class="controls">
    <button id="btnPrev">⏮️</button>
    <button id="btnPlay">▶️</button>
    <button id="btnNext">⏭️</button>
    <button id="btnMute">🔊</button>
    <select id="speed">
        <option value="1">1x</option>
        <option value="1.5">1.5x</option>
        <option value="2">2x</option>
    </select>
</div>

<div class="progress-container" id="progressContainer">
    <div class="progress" id="progress"></div>
</div>

<audio id="audio"></audio>

<pre class="lyrics" id="lyrics">Carregando letra...</pre>

<h3>🎶 Playlist</h3>
<ul id="playlist" style="list-style: none; padding: 0; max-width: 600px; margin: 1rem auto;">
    <!-- Itens gerados via JS -->
</ul>

<script>
    const audio = document.getElementById('audio');
    const title = document.getElementById('title');
    const artist = document.getElementById('artist');
    const cover = document.getElementById('cover');
    const progress = document.getElementById('progress');
    const progressContainer = document.getElementById('progressContainer');
    const btnPlay = document.getElementById('btnPlay');
    const btnPrev = document.getElementById('btnPrev');
    const btnNext = document.getElementById('btnNext');
    const btnMute = document.getElementById('btnMute');
    const speed = document.getElementById('speed');
    const lyrics = document.getElementById('lyrics');

    let currentIndex = 0;
    let isPlaying = false;
    let isMuted = false;
    let lrcLines = [];

    const playlist = <?=$musics?>;

    function formatTime(time) {
        const m = Math.floor(time / 60);
        const s = Math.floor(time % 60).toString().padStart(2, '0');
        return `${m}:${s}`;
    }

    function updateUI() {
        const song = playlist[currentIndex];
        title.textContent = song.title;
        artist.textContent = song.artist;
        cover.src = song.cover;
        audio.src = song.src;
        document.title = '🎵 ' + song.title;

        loadLRC(song.lyrics);
        renderPlaylist();
    }

    function playSong() {
        audio.play();
        isPlaying = true;
        btnPlay.textContent = '⏸️';
    }

    function pauseSong() {
        audio.pause();
        isPlaying = false;
        btnPlay.textContent = '▶️';
    }

    function saveState() {
        localStorage.setItem('player_state', JSON.stringify({
            index: currentIndex,
            volume: audio.volume,
            time: audio.currentTime,
            speed: audio.playbackRate
        }));
    }

    function loadState() {
        const state = JSON.parse(localStorage.getItem('player_state') || '{}');
        if (typeof state.index === 'number' && playlist[state.index]) currentIndex = state.index;
        if (typeof state.volume === 'number') audio.volume = state.volume;
        if (typeof state.speed === 'number') {
            audio.playbackRate = state.speed;
            speed.value = state.speed;
        }
        updateUI();
        if (typeof state.time === 'number') {
            audio.addEventListener('loadedmetadata', () => {
                audio.currentTime = state.time;
            }, {once: true});
        }
    }

    async function loadLRC(lrcFile) {
        try {
            if (lrcFile === '' || lrcFile === undefined) {
                throw new Error('lrcFile not found');
            }
            const res = await fetch(lrcFile);
            const text = await res.text();
            lrcLines = parseLRC(text);
            lyrics.textContent = '';
        } catch {
            await fetchLyricsFromProxy();
        }
    }

    function renderPlaylist() {
        const list = document.getElementById('playlist');
        list.innerHTML = '';

        playlist.forEach((song, index) => {
            const li = document.createElement('li');
            li.textContent = `${song.title} - ${song.artist}`;
            li.style.cursor = 'pointer';
            li.style.padding = '0.5rem';
            li.style.background = index === currentIndex ? '#4caf50' : '#222';
            li.style.borderBottom = '1px solid #333';
            li.onclick = () => {
                currentIndex = index;
                updateUI();
                playSong();
            };
            list.appendChild(li);
        });
    }

    function parseLRC(lrc) {
        return lrc.split('\n').map(line => {
            const match = line.match(/\[(\d+):(\d+\.\d+)](.*)/);
            if (match) {
                const time = parseInt(match[1]) * 60 + parseFloat(match[2]);
                return {time, text: match[3]};
            }
            return null;
        }).filter(Boolean);
    }

    function updateLRC() {
        if (!lrcLines.length) return;
        const current = audio.currentTime;
        let display = '';
        for (let i = 0; i < lrcLines.length; i++) {
            const line = lrcLines[i];
            const next = lrcLines[i + 1] ? lrcLines[i + 1].time : Infinity;
            if (current >= line.time && current < next) {
                display += `<div class="lrc-line highlight">${line.text}</div>`;
            } else {
                display += `<div class="lrc-line">${line.text}</div>`;
            }
        }
        lyrics.innerHTML = display;
    }

    async function fetchLyricsFromProxy() {
        const song = playlist[currentIndex];
        try {
            const res = await fetch(`/music/proxy?artista=${encodeURIComponent(song.artist)}&musica=${encodeURIComponent(song.title)}`);
            const data = await res.json();
            lyrics.textContent = data.letra || 'Letra não encontrada.';
        } catch {
            lyrics.textContent = 'Erro ao buscar letra.';
        }
    }

    btnPlay.onclick = () => isPlaying ? pauseSong() : playSong();
    btnPrev.onclick = () => {
        currentIndex = (currentIndex - 1 + playlist.length) % playlist.length;
        updateUI();
        playSong();
    };
    btnNext.onclick = () => {
        currentIndex = (currentIndex + 1) % playlist.length;
        updateUI();
        playSong();
    };
    btnMute.onclick = () => {
        isMuted = !isMuted;
        audio.muted = isMuted;
        btnMute.textContent = isMuted ? '🔇' : '🔊';
    };
    speed.onchange = () => {
        audio.playbackRate = parseFloat(speed.value);
        saveState();
    };

    audio.ontimeupdate = () => {
        const pct = (audio.currentTime / audio.duration) * 100;
        progress.style.width = pct + '%';
        updateLRC();
        saveState();
    };

    progressContainer.onclick = (e) => {
        const x = e.offsetX / progressContainer.clientWidth;
        audio.currentTime = x * audio.duration;
    };

    document.addEventListener('keydown', (e) => {
        if (['input', 'textarea'].includes(e.target.tagName.toLowerCase())) return;
        switch (e.key) {
            case ' ':
                e.preventDefault();
                isPlaying ? pauseSong() : playSong();
                break;
            case 'ArrowLeft':
                audio.currentTime = Math.max(0, audio.currentTime - 5);
                break;
            case 'ArrowRight':
                audio.currentTime = Math.min(audio.duration, audio.currentTime + 5);
                break;
            case '+':
                audio.playbackRate = Math.min(3, audio.playbackRate + 0.25);
                speed.value = audio.playbackRate;
                break;
            case '-':
                audio.playbackRate = Math.max(0.25, audio.playbackRate - 0.25);
                speed.value = audio.playbackRate;
                break;
        }
    });

    loadState();
</script>
</body>
</html>
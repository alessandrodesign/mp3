<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Chat - Sala Pública #<?=$id?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 20px auto;
        }
        #messages {
            height: 300px;
            overflow-y: auto;
            border: 1px solid #ccc;
            padding: 10px;
            background: #f9f9f9;
            margin-bottom: 10px;
        }
        .msg {
            margin: 5px 0;
        }
        .msg time {
            font-size: 0.8em;
            color: #999;
        }
        .system {
            color: green;
            font-style: italic;
        }
    </style>
</head>
<body>
<h1>Chat - Sala Pública #<?=$id?></h1>

<div id="status"><strong>Conectando...</strong></div>
<div id="users"></div>

<div id="messages"></div>

<input id="input" placeholder="Digite sua mensagem" style="width:70%"/>
<button id="send">Enviar</button>
<div id="typing" style="margin-top:10px; color: #666;"></div>

<script>
    const roomId = <?=json_encode($id)?>;
    const userId = Math.floor(Math.random() * 10000); // ID aleatório para simular login
    const ws = new WebSocket("ws://127.0.0.1:8080");

    const statusDiv = document.getElementById('status');
    const usersDiv = document.getElementById('users');
    const messagesDiv = document.getElementById('messages');
    const input = document.getElementById('input');
    const sendBtn = document.getElementById('send');
    const typingDiv = document.getElementById('typing');

    let usersOnline = new Set();
    let typingUsers = new Set();
    let typingTimeouts = {};

    ws.onopen = () => {
        statusDiv.innerHTML = `<span style="color:green">Servidor online</span>`;
        ws.send(JSON.stringify({ type: 'join_room', roomId, userId }));
    };

    ws.onmessage = (event) => {
        const data = JSON.parse(event.data);
        switch (data.type) {
            case 'user_connected':
                usersOnline.add(data.userId);
                updateUsers();
                addSystemMessage(`Usuário ${data.userId} entrou na sala.`);
                break;

            case 'user_disconnected':
                usersOnline.delete(data.userId);
                updateUsers();
                addSystemMessage(`Usuário ${data.userId} saiu da sala.`);
                break;

            case 'message':
                addMessage(data.from, data.text, data.timestamp);
                break;

            case 'typing':
                if (data.from !== userId) {
                    typingUsers.add(data.from);
                    updateTyping();
                    clearTimeout(typingTimeouts[data.from]);
                    typingTimeouts[data.from] = setTimeout(() => {
                        typingUsers.delete(data.from);
                        updateTyping();
                    }, 3000);
                }
                break;

            case 'server_online':
                statusDiv.innerHTML = `<span style="color:green">Servidor online</span>`;
                break;

            case 'server_offline':
                statusDiv.innerHTML = `<span style="color:red">Servidor offline</span>`;
                break;

            default:
                if (data.info) addSystemMessage(data.info);
                if (data.error) alert(data.error);
                break;
        }
    };

    ws.onerror = () => {
        statusDiv.innerHTML = `<span style="color:red">Erro na conexão</span>`;
    };

    ws.onclose = () => {
        statusDiv.innerHTML = `<span style="color:red">Conexão encerrada</span>`;
    };

    sendBtn.onclick = () => {
        const text = input.value.trim();
        if (text) {
            ws.send(JSON.stringify({ type: 'message', text }));
            input.value = '';
        }
    };

    input.addEventListener('input', () => {
        ws.send(JSON.stringify({ type: 'typing' }));
    });

    function addMessage(from, text, timestamp) {
        const p = document.createElement('p');
        p.classList.add('msg');
        const time = new Date(timestamp * 1000).toLocaleTimeString();
        p.innerHTML = `<time>[${time}]</time> <strong>${from}</strong>: ${text}`;
        messagesDiv.appendChild(p);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }

    function addSystemMessage(text) {
        const p = document.createElement('p');
        p.className = 'system';
        p.textContent = text;
        messagesDiv.appendChild(p);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }

    function updateUsers() {
        usersDiv.innerHTML = 'Usuários online: ' + Array.from(usersOnline).join(', ');
    }

    function updateTyping() {
        if (typingUsers.size > 0) {
            typingDiv.textContent = 'Digitando: ' + Array.from(typingUsers).join(', ');
        } else {
            typingDiv.textContent = '';
        }
    }
</script>
</body>
</html>

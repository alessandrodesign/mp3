<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Chat Privado</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f2f2f2;
            margin: 0;
            padding: 0;
        }

        h1 {
            text-align: center;
            background: #007BFF;
            color: white;
            margin: 0;
            padding: 15px;
        }

        #container {
            max-width: 600px;
            margin: 20px auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
        }

        #status {
            font-weight: bold;
            margin-bottom: 10px;
            color: #555;
        }

        #users, #typing {
            margin-bottom: 10px;
            font-size: 0.9em;
            color: #777;
        }

        #messages {
            height: 300px;
            overflow-y: auto;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 10px;
            background: #f9f9f9;
        }

        #messages p {
            margin: 5px 0;
        }

        .my-message {
            text-align: right;
            color: #007BFF;
        }

        .system-message {
            color: green;
            font-style: italic;
        }

        #input-area {
            display: flex;
            gap: 10px;
        }

        #input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1em;
        }

        #send {
            padding: 10px 20px;
            background: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        #send:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>

<h1>Chat Privado</h1>
<div id="container">
    <div id="status">Conectando...</div>
    <div id="users">Usuários online:</div>
    <div id="messages"></div>
    <div id="typing"></div>

    <div id="input-area">
        <input id="input" placeholder="Digite a mensagem" autocomplete="off" />
        <button id="send">Enviar</button>
    </div>
</div>

<script>
    // IDs vindos do PHP com segurança
    const user1 = <?= json_encode($from) ?>;
    const user2 = <?= json_encode($to) ?>;
    const userId = user1;

    const ws = new WebSocket('ws://127.0.0.1:8080');

    const statusDiv = document.getElementById('status');
    const usersDiv = document.getElementById('users');
    const messagesDiv = document.getElementById('messages');
    const typingDiv = document.getElementById('typing');
    const input = document.getElementById('input');
    const sendBtn = document.getElementById('send');

    const usersOnline = new Set();
    const typingUsers = new Set();
    const typingTimeouts = new Map();

    // Escapa conteúdo HTML para evitar XSS
    function escapeHTML(str) {
        return str.replace(/[&<>"']/g, m => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;',
            '"': '&quot;', "'": '&#039;'
        })[m]);
    }

    function updateUsers() {
        usersDiv.textContent = 'Usuários online: ' + Array.from(usersOnline).join(', ');
    }

    function updateTyping() {
        typingDiv.textContent = typingUsers.size > 0
            ? 'Digitando: ' + Array.from(typingUsers).join(', ')
            : '';
    }

    function addMessage(from, text, timestamp) {
        const p = document.createElement('p');
        const formattedTime = new Date(timestamp * 1000).toLocaleTimeString();
        const safeText = escapeHTML(text);
        const safeFrom = escapeHTML(from);

        p.innerHTML = `[${formattedTime}] <strong>${safeFrom}</strong>: ${safeText}`;
        if (from == userId) {
            p.classList.add('my-message');
        }

        messagesDiv.appendChild(p);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }

    function addSystemMessage(text) {
        const p = document.createElement('p');
        p.textContent = text;
        p.classList.add('system-message');
        messagesDiv.appendChild(p);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }

    ws.onopen = () => {
        statusDiv.textContent = 'Servidor online';
        ws.send(JSON.stringify({
            type: 'join_private',
            user1: user1,
            user2: user2,
            userId: userId
        }));
    };

    ws.onmessage = (event) => {
        const data = JSON.parse(event.data);

        switch (data.type) {
            case 'user_connected':
                usersOnline.add(data.userId);
                updateUsers();
                addSystemMessage(`Usuário ${data.userId} conectou.`);
                break;

            case 'user_disconnected':
                usersOnline.delete(data.userId);
                updateUsers();
                addSystemMessage(`Usuário ${data.userId} desconectou.`);
                break;

            case 'message':
                addMessage(data.from, data.text, data.timestamp);
                break;

            case 'typing':
                typingUsers.add(data.from);
                updateTyping();

                clearTimeout(typingTimeouts.get(data.from));
                typingTimeouts.set(data.from, setTimeout(() => {
                    typingUsers.delete(data.from);
                    updateTyping();
                }, 3000));
                break;

            case 'server_online':
                statusDiv.textContent = 'Servidor online';
                break;

            case 'server_offline':
                statusDiv.textContent = 'Servidor offline';
                break;

            default:
                if (data.info) addSystemMessage(data.info);
                if (data.error) alert(data.error);
                break;
        }
    };

    ws.onerror = () => {
        statusDiv.textContent = 'Erro na conexão';
    };

    ws.onclose = () => {
        statusDiv.textContent = 'Servidor offline';
    };

    sendBtn.onclick = () => {
        const text = input.value.trim();
        if (text) {
            ws.send(JSON.stringify({ type: 'message', from: userId, text }));
            input.value = '';
        }
    };

    input.addEventListener('input', () => {
        ws.send(JSON.stringify({ type: 'typing', from: userId }));
    });

    input.addEventListener('keypress', e => {
        if (e.key === 'Enter') sendBtn.click();
    });
</script>

</body>
</html>

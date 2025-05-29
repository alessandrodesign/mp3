# NorteDev Framework

Este projeto é um framework PHP 8.3 personalizado, focado em flexibilidade, desempenho e extensibilidade, com funcionalidades modernas como roteamento via atributos, middleware, injeção de dependência, internacionalização, autenticação, gravação e streaming de vídeo, chat em tempo real, entre outras.

---

## Funcionalidades Principais

### 1. Estrutura e Configuração Inicial
- Organização clara de diretórios para código, recursos públicos, templates e traduções.
- Autoload via Composer.
- Singleton `App` para gerenciamento central da aplicação.

### 2. Roteamento
- Rotas definidas via atributos PHP 8.3.
- Suporte a múltiplos métodos HTTP.
- Extração automática de parâmetros de rota.
- Registro automático de controllers.

### 3. Middleware
- Suporte a middlewares globais e específicos.
- Injeção de dependências em middlewares.
- Middleware para cache de resposta, validação, autenticação, etc.

### 4. Injeção de Dependência
- Container para registro e resolução automática de dependências.
- Registro automático de controllers, middlewares e serviços.

### 5. Internacionalização (i18n)
- Suporte a múltiplos idiomas.
- Sistema de tradução com placeholders.
- Detecção automática de idioma.
- Helpers para tradução.

### 6. Validação de Requisições
- Validação usando Symfony Validator.
- DTOs para dados validados.
- Middleware para validação e retorno de erros.

### 7. Autenticação e Autorização
- JWT para autenticação.
- Middleware para proteção de rotas.
- Sistema de roles e permissões.

### 8. Servir Arquivos Estáticos
- Controller para servir arquivos com suporte a cache e range requests.

### 9. Gravação e Streaming de Vídeo
- Frontend para captura de vídeo via webcam.
- Backend para upload, armazenamento e streaming.
- Limites de tempo e tamanho.
- Indicadores de tempo e tamanho durante gravação.

### 10. Chat em Tempo Real com Salas e Privacidade
- Servidor WebSocket com Workerman para sinalização e chat.
- Salas públicas e privadas.
- Status de usuários (conectado, desconectado).
- Indicador "usuário está digitando".
- Comandos CLI para iniciar/parar servidor de sinalização.

---

## Estrutura de Pastas Atualizada

```
/
├── app/
│   ├── Console/Commands/       # Comandos CLI (ex: criação de controllers, models, middlewares, servidor signaling)
│   ├── Controllers/            # Controllers da aplicação
│   ├── Middlewares/            # Middlewares
│   ├── Models/                 # Models Eloquent
│   ├── Services/               # Serviços (ex: SignalingService)
│   └── ...
├── core/                      # Código do framework (routing, DI, contratos, etc)
├── public/                    # Arquivos públicos (index.php, router.php, assets)
├── resources/
│   ├── views/                 # Templates Twig
│   ├── translations/          # Arquivos de tradução
│   └── js/                    # Scripts JS (ex: capture.js, watch.js)
├── storage/
│   ├── cache/                 # Cache da aplicação
│   ├── logs/                  # Logs
│   └── videos/                # Vídeos gravados
├── vendor/                    # Dependências Composer
├── composer.json
├── cli.php                    # Arquivo principal CLI
├── chat-server.php            # Servidor WebSocket para chat e signaling
└── README.md
```

---

## Como Rodar

1. Instale dependências:

```bash
composer install
```

2. Inicie o servidor WebSocket para chat e signaling:

```bash
php nortedev signaling:chat
```

3. Acesse as rotas públicas e privadas de chat:

- Sala pública: `/chat/room/{id}`
- Sala privada: `/chat/private/{user1}/{user2}`

4. Use os comandos CLI para criar controllers, models, middlewares:

```bash
php nortedev make:controller NomeController
php nortedev make:model NomeModel --table=nome_tabela
php nortedev make:middleware NomeMiddleware
```

---

## Tecnologias Utilizadas

- PHP 8.3
- Symfony Components (Console, HttpFoundation, Validator, Translation, Mime)
- Illuminate Eloquent ORM
- Workerman (WebSocket)
- JavaScript (WebRTC, WebSocket)
- Composer

---

## Contato

Para dúvidas ou contribuições, entre em contato com a equipe NorteDev.


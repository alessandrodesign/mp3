# Sistema de Gerenciamento de Framework PHP Personalizado

Este README resume o desenvolvimento de um framework PHP personalizado, abordando desde a configuração inicial até a implementação de funcionalidades avançadas como internacionalização, roteamento, middleware, injeção de dependência, validação, templating e autenticação.

---

## Visão Geral

O projeto consiste na criação de um framework PHP 8.3 focado em desempenho, flexibilidade e extensibilidade. O framework utiliza componentes do Symfony para algumas funcionalidades, mas mantém uma estrutura própria para facilitar a personalização e o aprendizado.

---

## Funcionalidades Implementadas

### 1. Configuração Inicial

- **Estrutura de diretórios:** Organização clara para código fonte, arquivos públicos, templates e traduções.
- **Composer:** Gerenciamento de dependências com `composer.json`.
- **Autoload:** Carregamento automático de classes.

### 2. Singleton `App`

- Implementação de um singleton para gerenciar a aplicação.
- Bootstrap básico para inicializar componentes essenciais.

### 3. Roteamento com Atributos

- Definição de rotas usando atributos PHP 8.3.
- Suporte para múltiplos métodos HTTP (GET, POST, etc.).
- Conversão de rotas com parâmetros para expressões regulares.
- Extração de parâmetros da rota para injeção nos controllers.

### 4. Middleware

- Implementação de middlewares para interceptar e processar requisições.
- Middlewares globais e específicos para rotas.
- Injeção de dependências nos middlewares.

### 5. Injeção de Dependência

- Uso de um container de injeção de dependência para gerenciar as dependências da aplicação.
- Registro automático de controllers e middlewares no container.
- Resolução de dependências nos controllers e middlewares.

### 6. Internacionalização (i18n)

- Suporte a múltiplos idiomas.
- Helpers para tradução de textos.
- Detecção automática de idioma via headers ou URL.
- Sistema de gestão de traduções com interface web (opcional).
- Armazenamento de traduções em arquivos PHP.
- Adição automática de traduções faltantes.

### 7. Validação de Requisições

- Validação de dados de requisição usando Symfony Validator.
- Criação de Data Transfer Objects (DTOs) para representar os dados validados.
- Middleware para validar as requisições e retornar erros.

### 8. Templating com Twig

- Integração com o motor de templates Twig.
- Criação de um serviço para renderizar templates.
- Uso de Twig nos controllers para gerar HTML.

### 9. Autenticação e Autorização

- Autenticação baseada em JWT (JSON Web Tokens).
- Criação de um serviço para gerar e validar tokens JWT.
- User provider para gerenciar os dados dos usuários.
- Middleware para proteger as rotas com autenticação.
- Sistema de roles e permissões para autorização.

### 10. Servindo Arquivos Estáticos

- Controller para servir arquivos estáticos com alta performance.
- Suporte a cache HTTP (ETag, Last-Modified).
- Suporte a Range Requests para streaming de vídeo.

### 11. Gravação, Armazenamento e Streaming de Vídeo

- Frontend para gravar vídeos com a webcam.
- Backend para receber, armazenar e servir os vídeos.
- Limite de tempo e tamanho para as gravações.
- Display de tempo e tamanho durante a gravação.

---

## Tecnologias Utilizadas

- PHP 8.3
- Symfony Components (HttpFoundation, Validator, Translation, Mime)
- Twig
- FastRoute
- PHP-DI
- JavaScript (puro)
- HTML
- CSS

---

## Estrutura do Projeto

```
/
├── app/                # Código da aplicação
│   ├── Controllers/    # Controllers
│   ├── Middlewares/    # Middlewares
│   ├── Services/       # Serviços
│   └── ...
├── core/               # Código do framework
│   ├── Routing/        # Roteamento
│   ├── DependencyInjection/ # Injeção de Dependência
│   ├── Contracts/      # Interfaces
│   └── ...
├── public/             # Arquivos públicos
│   ├── index.php       # Front controller
│   └── .htaccess       # Configuração do Apache
├── templates/          # Templates Twig
├── translations/       # Arquivos de tradução
├── vendor/             # Dependências do Composer
├── composer.json       # Arquivo de configuração do Composer
└── README.md           # Este arquivo
```

---

## Próximos Passos

- Implementar testes unitários e de integração.
- Criar um sistema de logs.
- Adicionar suporte a banco de dados.
- Melhorar a segurança da aplicação.
- Criar uma interface de linha de comando (CLI) para facilitar a gestão do framework.

---

Este framework foi desenvolvido com o objetivo de fornecer uma base sólida para a criação de aplicações PHP modernas, com foco em desempenho, flexibilidade e facilidade de uso.

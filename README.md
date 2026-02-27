# Agendo API

API RESTful desenvolvida em Laravel para gerenciamento de agendamentos de serviços em estabelecimentos comerciais.

## 📋 Sobre o Projeto

O Agendo API é uma solução completa para gerenciamento de agendamentos, permitindo que estabelecimentos comerciais (salões, clínicas, consultórios, etc.) gerenciem seus serviços, profissionais, horários e agendamentos de forma eficiente.

## ✨ Funcionalidades Principais

- 🔐 **Autenticação**: Login/Registro tradicional e via Google OAuth
- 👥 **Gestão de Usuários**: Clientes, Profissionais e Estabelecimentos
- 📅 **Agendamentos**: Sistema completo de agendamento com verificação de disponibilidade
- 🏢 **Estabelecimentos**: CRUD completo de estabelecimentos
- 💼 **Serviços**: Gerenciamento de serviços oferecidos
- ⏰ **Horários de Funcionamento**: Configuração flexível de horários
- 🔔 **Notificações**: Sistema de lembretes configuráveis por usuário
- 🚫 **Bloqueios**: Profissionais podem bloquear horários na agenda

## 🛠️ Tecnologias

- **Laravel 12** - Framework PHP
- **Laravel Sanctum** - Autenticação via API tokens
- **Laravel Socialite** - Autenticação OAuth (Google)
- **SQLite** - Banco de dados (configurável para MySQL/PostgreSQL)

## 📚 Documentação

A documentação completa está disponível na pasta `docs/`:

- [📖 Documentação da API](docs/API.md) - Endpoints e exemplos de uso
- [🔐 Autenticação](docs/AUTHENTICATION.md) - Login, registro e OAuth
- [🗄️ Modelos e Estrutura](docs/MODELS.md) - Estrutura de dados e relacionamentos
- [⚙️ Configuração e Instalação](docs/SETUP.md) - Guia de instalação e configuração

## 🚀 Início Rápido

### Pré-requisitos

- PHP >= 8.2
- Composer
- Node.js e NPM (opcional, para assets)

### Instalação

1. Clone o repositório:
```bash
git clone https://github.com/JulioBuenoSilva/agendo-api.git
cd agendo-api
```

2. Instale as dependências:
```bash
composer install
npm install
```

3. Configure o ambiente:
```bash
cp .env.example .env
php artisan key:generate
```

4. Configure o banco de dados no arquivo `.env`

5. Execute as migrations:
```bash
php artisan migrate
```

6. Inicie o servidor:
```bash
php artisan serve
```

A API estará disponível em `http://localhost:8000/api`

## 📝 Variáveis de Ambiente

Principais variáveis que devem ser configuradas no `.env`:

```env
APP_NAME=Agendo API
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
DB_DATABASE=/caminho/para/database.sqlite

GOOGLE_CLIENT_ID=seu_client_id
GOOGLE_CLIENT_SECRET=seu_client_secret
GOOGLE_REDIRECT_URI=http://localhost:8000/api/auth/google/callback

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=seu_usuario
MAIL_PASSWORD=sua_senha
```

## 🔑 Tipos de Usuário

- **Cliente**: Usuário que faz agendamentos (ativo por padrão)
- **Profissional**: Funcionário do estabelecimento (requer aprovação)
- **Estabelecimento**: Dono do estabelecimento (requer aprovação do admin)

## 📡 Base URL da API

```
http://localhost:8000/api
```

## 🔒 Autenticação

A API utiliza **Laravel Sanctum** para autenticação via tokens. Todas as rotas protegidas requerem o header:

```
Authorization: Bearer {token}
```

## 📄 Licença

Este projeto é de código aberto e está disponível sob a licença MIT.

## 👨‍💻 Desenvolvedor

Desenvolvido por [Julio Bueno Silva](https://github.com/JulioBuenoSilva)

## 🤝 Contribuindo

Contribuições são bem-vindas! Sinta-se à vontade para abrir issues e pull requests.

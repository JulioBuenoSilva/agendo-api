# Configuração e Instalação

Guia completo para configurar e instalar a API Agendo.

## 📋 Pré-requisitos

Antes de começar, certifique-se de ter instalado:

- **PHP >= 8.2**
- **Composer** (gerenciador de dependências PHP)
- **Node.js >= 18** e **NPM** (para assets frontend, opcional)
- **SQLite** (padrão) ou **MySQL/PostgreSQL** (produção)
- **Git**

### Verificar Instalações

```bash
php -v          # Deve mostrar PHP 8.2 ou superior
composer -v     # Deve mostrar versão do Composer
node -v         # Deve mostrar Node.js 18 ou superior
npm -v          # Deve mostrar versão do NPM
```

---

## 🚀 Instalação

### 1. Clonar o Repositório

```bash
git clone https://github.com/JulioBuenoSilva/agendo-api.git
cd agendo-api
```

### 2. Instalar Dependências PHP

```bash
composer install
```

### 3. Instalar Dependências JavaScript (Opcional)

```bash
npm install
```

### 4. Configurar Ambiente

Copie o arquivo de exemplo e configure as variáveis:

```bash
cp .env.example .env
```

Edite o arquivo `.env` com suas configurações:

```env
APP_NAME=Agendo API
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug
```

### 5. Gerar Chave da Aplicação

```bash
php artisan key:generate
```

### 6. Configurar Banco de Dados

#### Opção A: SQLite (Desenvolvimento - Padrão)

O SQLite já está configurado por padrão. Certifique-se de que o arquivo existe:

```bash
touch database/database.sqlite
```

No arquivo `.env`, configure:

```env
DB_CONNECTION=sqlite
DB_DATABASE=/caminho/absoluto/para/database/database.sqlite
```

#### Opção B: MySQL (Produção)

No arquivo `.env`, configure:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=agendo_db
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha
```

#### Opção C: PostgreSQL (Produção)

No arquivo `.env`, configure:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=agendo_db
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha
```

### 7. Executar Migrations

```bash
php artisan migrate
```

Isso criará todas as tabelas necessárias no banco de dados.

### 8. (Opcional) Popular Banco com Dados de Teste

```bash
php artisan db:seed
```

---

## ⚙️ Configuração Adicional

### Configurar Google OAuth

1. **Criar Projeto no Google Cloud Console:**
   - Acesse: https://console.cloud.google.com/
   - Crie um novo projeto ou selecione existente
   - Ative a API "Google Identity"

2. **Criar Credenciais OAuth 2.0:**
   - Vá em "Credenciais" → "Criar credenciais" → "ID do cliente OAuth 2.0"
   - Tipo de aplicativo: "Aplicativo da Web"
   - Adicione URIs de redirecionamento autorizados:
     - `http://localhost:8000/api/auth/google/callback` (desenvolvimento)
     - `https://seudominio.com/api/auth/google/callback` (produção)

3. **Configurar no .env:**
```env
GOOGLE_CLIENT_ID=seu_client_id_aqui
GOOGLE_CLIENT_SECRET=seu_client_secret_aqui
GOOGLE_REDIRECT_URI=http://localhost:8000/api/auth/google/callback
```

### Configurar Email (Opcional)

Para envio de emails de notificação, configure no `.env`:

#### Mailtrap (Desenvolvimento)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=seu_usuario_mailtrap
MAIL_PASSWORD=sua_senha_mailtrap
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@agendo.com
MAIL_FROM_NAME="${APP_NAME}"
```

#### Gmail (Produção)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=seu_email@gmail.com
MAIL_PASSWORD=sua_senha_app
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=seu_email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

---

## 🏃 Executar a Aplicação

### Modo Desenvolvimento

```bash
php artisan serve
```

A API estará disponível em: `http://localhost:8000`

### Modo Produção

Configure um servidor web (Nginx/Apache) apontando para a pasta `public/`.

---

## 🔧 Comandos Úteis

### Limpar Cache

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Recriar Banco de Dados

```bash
php artisan migrate:fresh
php artisan db:seed
```

### Executar Comando de Lembretes

O sistema possui um comando agendado que envia lembretes de agendamento:

```bash
php artisan app:enviar-lembretes-agendamento
```

Este comando é executado automaticamente a cada minuto via scheduler do Laravel.

### Verificar Rotas

```bash
php artisan route:list
```

### Executar Testes

```bash
php artisan test
```

---

## 📁 Estrutura de Diretórios

```
agendo-api/
├── app/
│   ├── Console/
│   │   └── Commands/          # Comandos artisan
│   ├── Http/
│   │   ├── Controllers/       # Controladores da API
│   │   └── Middleware/         # Middlewares customizados
│   ├── Mail/                   # Classes de email
│   ├── Models/                 # Modelos Eloquent
│   ├── Notifications/          # Notificações
│   └── Services/               # Serviços de negócio
├── config/                     # Arquivos de configuração
├── database/
│   ├── migrations/             # Migrations do banco
│   └── seeders/                # Seeders
├── docs/                       # Documentação
├── public/                     # Pasta pública (web root)
├── resources/
│   └── views/                  # Views Blade
├── routes/
│   ├── api.php                 # Rotas da API
│   └── web.php                 # Rotas web
└── storage/                    # Arquivos de storage
```

---

## 🔒 Segurança

### Produção

1. **Altere APP_DEBUG para false:**
```env
APP_DEBUG=false
```

2. **Use HTTPS:**
   - Configure SSL/TLS no servidor
   - Force HTTPS no `.env`: `APP_URL=https://seudominio.com`

3. **Configure CORS adequadamente:**
   - Edite `config/cors.php` para permitir apenas domínios confiáveis

4. **Proteja arquivos sensíveis:**
   - Nunca commite o arquivo `.env`
   - Mantenha `.env` fora do diretório público

5. **Configure rate limiting:**
   - Laravel já possui rate limiting configurado
   - Ajuste em `app/Http/Kernel.php` se necessário

---

## 🐛 Troubleshooting

### Erro: "SQLSTATE[HY000] [14] unable to open database file"

**Solução:** Verifique se o arquivo `database/database.sqlite` existe e tem permissões de escrita:

```bash
touch database/database.sqlite
chmod 664 database/database.sqlite
```

### Erro: "Class 'PDO' not found"

**Solução:** Instale a extensão PDO do PHP:

```bash
# Ubuntu/Debian
sudo apt-get install php-pdo php-sqlite3

# macOS (Homebrew)
brew install php
```

### Erro: "No application encryption key has been specified"

**Solução:** Gere a chave da aplicação:

```bash
php artisan key:generate
```

### Erro ao conectar com Google OAuth

**Solução:** 
1. Verifique se as credenciais estão corretas no `.env`
2. Verifique se o URI de redirecionamento está configurado no Google Cloud Console
3. Verifique se a API "Google Identity" está ativada

### Erro: "The stream or file could not be opened"

**Solução:** Configure permissões na pasta storage:

```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

---

## 📚 Próximos Passos

Após a instalação:

1. ✅ Leia a [Documentação da API](API.md)
2. ✅ Configure autenticação Google (se necessário)
3. ✅ Configure email (se necessário)
4. ✅ Teste os endpoints usando Postman ou cURL
5. ✅ Configure o scheduler para lembretes automáticos

---

## 🤝 Suporte

Para problemas ou dúvidas:

1. Verifique a documentação completa em `docs/`
2. Abra uma issue no GitHub
3. Entre em contato com o desenvolvedor

---

## 📝 Notas

- O sistema utiliza SQLite por padrão para facilitar desenvolvimento
- Em produção, recomenda-se usar MySQL ou PostgreSQL
- O scheduler do Laravel precisa estar configurado no cron para funcionar automaticamente
- Tokens de autenticação não expiram automaticamente (implemente renovação se necessário)

# Autenticação

Documentação completa sobre autenticação na API Agendo.

## Visão Geral

A API utiliza **Laravel Sanctum** para autenticação via tokens. Existem três métodos de autenticação disponíveis:

1. **Registro/Login Tradicional** - Email e senha
2. **Autenticação via Google OAuth** - Login social
3. **Tokens de API** - Para acesso autenticado

---

## 🔐 Autenticação Tradicional

### Registro

**Endpoint:** `POST /api/register`

Registra um novo usuário no sistema. Ao registrar, o usuário recebe automaticamente:
- Um token de acesso
- Uma configuração de lembrete padrão (1 dia antes)
- Email marcado como verificado (se usar Google)

**Exemplo de Requisição:**
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "João Silva",
    "email": "joao@example.com",
    "password": "senha123",
    "password_confirmation": "senha123",
    "telefone": "11999999999",
    "tipo": "cliente"
  }'
```

**Tipos de Usuário:**
- `cliente` - Usuário que faz agendamentos (ativo por padrão)
- `profissional` - Funcionário do estabelecimento (requer aprovação)
- `estabelecimento` - Dono do estabelecimento (requer aprovação)

**Resposta:**
```json
{
  "access_token": "1|abcdef123456...",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "João Silva",
    "email": "joao@example.com",
    "tipo": "cliente"
  },
  "mensagem": "Cadastro realizado com sucesso!"
}
```

---

### Login

**Endpoint:** `POST /api/login`

Autentica um usuário existente e retorna um token de acesso.

**Exemplo de Requisição:**
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "joao@example.com",
    "password": "senha123",
    "device_name": "iPhone do João"
  }'
```

**Parâmetros:**
- `email` (required): Email do usuário
- `password` (required): Senha do usuário
- `device_name` (required): Nome identificador do dispositivo

**Resposta:**
```json
{
  "token": "1|abcdef123456...",
  "user": {
    "id": 1,
    "name": "João Silva",
    "tipo": "cliente"
  }
}
```

---

### Logout

**Endpoint:** `POST /api/logout`

Revoga o token atual do usuário.

**Headers:**
```
Authorization: Bearer {token}
```

**Exemplo:**
```bash
curl -X POST http://localhost:8000/api/logout \
  -H "Authorization: Bearer 1|abcdef123456..."
```

**Resposta:**
```json
{
  "message": "Logout realizado."
}
```

---

## 🔵 Autenticação via Google OAuth

### Configuração Inicial

1. **Criar Credenciais no Google Cloud Console:**
   - Acesse: https://console.cloud.google.com/
   - Crie um novo projeto ou selecione um existente
   - Ative a API "Google Identity"
   - Vá em "Credenciais" → "Criar credenciais" → "ID do cliente OAuth 2.0"
   - Configure os URIs de redirecionamento permitidos

2. **Configurar Variáveis de Ambiente:**
```env
GOOGLE_CLIENT_ID=seu_client_id_aqui
GOOGLE_CLIENT_SECRET=seu_client_secret_aqui
GOOGLE_REDIRECT_URI=http://localhost:8000/api/auth/google/callback
```

---

### Login/Registro via Google

**Endpoint:** `POST /api/auth/google`

Este endpoint funciona tanto para login quanto para registro:
- Se o usuário já existir (por `google_id` ou `email`): faz login
- Se o usuário não existir: cria automaticamente como cliente

**Exemplo de Requisição:**
```bash
curl -X POST http://localhost:8000/api/auth/google \
  -H "Content-Type: application/json" \
  -d '{
    "access_token": "ya29.a0AfH6SMB...",
    "device_name": "App Mobile",
    "tipo": "cliente"
  }'
```

**Parâmetros:**
- `access_token` (required): Token de acesso do Google
- `device_name` (required): Nome identificador do dispositivo
- `tipo` (optional): Tipo de usuário (`cliente`, `profissional`, `estabelecimento`). Padrão: `cliente`

**Resposta (Login - usuário existente):**
```json
{
  "token": "1|abcdef123456...",
  "user": {
    "id": 1,
    "name": "João Silva",
    "email": "joao@gmail.com",
    "tipo": "cliente"
  },
  "is_new_user": false,
  "mensagem": "Login realizado com sucesso via Google!"
}
```

**Resposta (Registro - novo usuário):**
```json
{
  "token": "1|abcdef123456...",
  "user": {
    "id": 2,
    "name": "Maria Santos",
    "email": "maria@gmail.com",
    "tipo": "cliente"
  },
  "is_new_user": true,
  "mensagem": "Cadastro realizado com sucesso via Google!"
}
```

---

### Registro como Estabelecimento via Google

Para registrar como estabelecimento, envie campos adicionais:

```json
{
  "access_token": "ya29.a0AfH6SMB...",
  "device_name": "App Mobile",
  "tipo": "estabelecimento",
  "nome_estabelecimento": "Salão Beleza",
  "endereco": "Rua X, 123",
  "ramo": "beleza"
}
```

**Ramos disponíveis:** `beleza`, `saude`, `terapia`, `outros`

---

### Registro como Profissional via Google

Para registrar como profissional, é necessário o `estabelecimento_id`:

```json
{
  "access_token": "ya29.a0AfH6SMB...",
  "device_name": "App Mobile",
  "tipo": "profissional",
  "estabelecimento_id": 1
}
```

---

## 🔑 Usando Tokens de Autenticação

Após obter um token (via login ou registro), você deve incluí-lo em todas as requisições protegidas:

### Header Authorization

```
Authorization: Bearer {seu_token_aqui}
```

### Exemplo com cURL

```bash
curl -X GET http://localhost:8000/api/user \
  -H "Authorization: Bearer 1|abcdef123456..."
```

### Exemplo com JavaScript (Fetch)

```javascript
fetch('http://localhost:8000/api/user', {
  headers: {
    'Authorization': 'Bearer 1|abcdef123456...',
    'Content-Type': 'application/json'
  }
})
.then(response => response.json())
.then(data => console.log(data));
```

### Exemplo com Axios

```javascript
axios.get('http://localhost:8000/api/user', {
  headers: {
    'Authorization': 'Bearer 1|abcdef123456...'
  }
})
.then(response => console.log(response.data));
```

---

## 👥 Tipos de Usuário e Permissões

### Cliente
- ✅ Pode fazer agendamentos
- ✅ Pode cancelar seus próprios agendamentos
- ✅ Pode confirmar presença
- ✅ Pode gerenciar configurações de lembrete
- ❌ Não pode gerenciar serviços
- ❌ Não pode ver agenda de profissionais

### Profissional
- ✅ Todas as permissões de Cliente
- ✅ Pode consultar sua agenda
- ✅ Pode atualizar status de agendamentos
- ✅ Pode criar/remover bloqueios
- ✅ Pode fazer agendamentos manuais
- ✅ Pode gerenciar serviços do estabelecimento
- ✅ Pode gerenciar horários de funcionamento
- ⚠️ Requer aprovação do dono do estabelecimento
- ⚠️ Deve estar ativo para acessar rotas de profissional

### Estabelecimento (Dono)
- ✅ Todas as permissões de Profissional
- ✅ É automaticamente o primeiro profissional
- ✅ Recebe notificações de novos profissionais
- ⚠️ Requer aprovação do admin do sistema

---

## 🔒 Middlewares de Segurança

### auth:sanctum
Protege rotas que requerem autenticação. Verifica se o token é válido.

### profissional
Verifica se o usuário é do tipo `profissional` ou `estabelecimento`.

### ativo
Verifica se o usuário está ativo. Profissionais inativos não podem acessar rotas protegidas.

---

## ⚠️ Tratamento de Erros de Autenticação

### Token Inválido ou Expirado

**Status:** `401 Unauthorized`

```json
{
  "message": "Unauthenticated."
}
```

### Token Não Fornecido

**Status:** `401 Unauthorized`

```json
{
  "message": "Unauthenticated."
}
```

### Usuário Não Autorizado

**Status:** `403 Forbidden`

```json
{
  "message": "Acesso negado."
}
```

### Usuário Inativo

**Status:** `403 Forbidden`

```json
{
  "message": "Usuário inativo. Aguarde aprovação."
}
```

---

## 🔄 Fluxo de Aprovação

### Cliente
- ✅ Ativo automaticamente após registro
- ✅ Pode usar o sistema imediatamente

### Profissional
- ⏳ Inativo após registro
- 📧 Dono do estabelecimento recebe email de notificação
- ✅ Após aprovação pelo dono, fica ativo
- ✅ Pode então acessar rotas de profissional

### Estabelecimento
- ⏳ Inativo após registro
- 📧 Admin do sistema recebe email de notificação
- ✅ Após aprovação pelo admin, fica ativo
- ✅ Pode então gerenciar seu estabelecimento

---

## 📝 Boas Práticas

1. **Armazene o token com segurança** - Use armazenamento seguro (ex: SecureStorage no Flutter, Keychain no iOS)

2. **Renove tokens quando necessário** - Tokens não expiram automaticamente, mas você pode implementar renovação

3. **Use device_name descritivo** - Facilita identificar dispositivos no futuro

4. **Trate erros 401** - Sempre redirecione para login quando receber 401

5. **Não exponha tokens** - Nunca commite tokens no código ou logs

6. **Use HTTPS em produção** - Sempre use HTTPS para proteger tokens em trânsito

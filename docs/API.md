# Documentação da API

Documentação completa dos endpoints da API Agendo.

## Base URL

```
http://localhost:8000/api
```

## Autenticação

A maioria dos endpoints requer autenticação via token Bearer:

```
Authorization: Bearer {seu_token_aqui}
```

---

## 📍 Rotas Públicas

### Registro de Usuário

**POST** `/register`

Registra um novo usuário no sistema.

**Body:**
```json
{
  "name": "João Silva",
  "email": "joao@example.com",
  "password": "senha123",
  "password_confirmation": "senha123",
  "telefone": "11999999999",
  "tipo": "cliente"
}
```

**Tipos disponíveis:** `cliente`, `profissional`, `estabelecimento`

**Resposta (201):**
```json
{
  "access_token": "token_aqui",
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

**POST** `/login`

Autentica um usuário e retorna um token.

**Body:**
```json
{
  "email": "joao@example.com",
  "password": "senha123",
  "device_name": "iPhone do João"
}
```

**Resposta (200):**
```json
{
  "token": "token_aqui",
  "user": {
    "id": 1,
    "name": "João Silva",
    "tipo": "cliente"
  }
}
```

---

### Login/Registro via Google

**POST** `/auth/google`

Autentica ou registra um usuário usando Google OAuth. Se o usuário não existir, cria automaticamente.

**Body:**
```json
{
  "access_token": "token_do_google",
  "device_name": "App Mobile",
  "tipo": "cliente"
}
```

**Resposta (200/201):**
```json
{
  "token": "token_aqui",
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

---

### Buscar Estabelecimento por Nome

**GET** `/estabelecimentos/buscar?nome={nome}`

Busca estabelecimentos por nome (busca parcial).

**Query Parameters:**
- `nome` (required): Nome ou parte do nome do estabelecimento

**Resposta (200):**
```json
{
  "total": 2,
  "estabelecimentos": [
    {
      "id": 1,
      "nome": "Salão Beleza",
      "identificador": "salao-beleza-abc12",
      "endereco": "Rua X, 123",
      "ramo": "beleza"
    }
  ]
}
```

---

### Detalhes Completos do Estabelecimento

**GET** `/estabelecimentos/{id}/detalhes`

Retorna todas as informações de um estabelecimento.

**Resposta (200):**
```json
{
  "id": 1,
  "nome": "Salão Beleza",
  "identificador": "salao-beleza-abc12",
  "endereco": "Rua X, 123",
  "ramo": "beleza",
  "fuso_horario": "America/Sao_Paulo",
  "horarios_funcionamento": [
    {
      "id": 1,
      "dia_semana": 1,
      "hora_abertura": "09:00",
      "hora_fechamento": "18:00"
    }
  ],
  "servicos": [
    {
      "id": 1,
      "nome": "Corte de Cabelo",
      "duracao_minutos": 30,
      "preco": 50.00
    }
  ],
  "profissionais": [
    {
      "id": 2,
      "name": "Maria Santos",
      "telefone": "11988888888",
      "email": "maria@example.com"
    }
  ],
  "dono": {
    "id": 3,
    "name": "Carlos Silva",
    "email": "carlos@example.com",
    "telefone": "11977777777"
  },
  "total_profissionais": 2,
  "total_servicos": 5
}
```

---

### Listar Serviços

**GET** `/servicos?estabelecimento_id={id}`

Lista serviços ativos de um estabelecimento.

**Query Parameters:**
- `estabelecimento_id` (required): ID do estabelecimento

**Resposta (200):**
```json
[
  {
    "id": 1,
    "nome": "Corte de Cabelo",
    "duracao_minutos": 30,
    "preco": 50.00,
    "observacao": "Inclui lavagem",
    "ativo": true
  }
]
```

---

## 🔒 Rotas Protegidas (Requerem Autenticação)

### Obter Dados do Usuário Logado

**GET** `/user`

Retorna os dados do usuário autenticado.

**Headers:**
```
Authorization: Bearer {token}
```

**Resposta (200):**
```json
{
  "id": 1,
  "name": "João Silva",
  "email": "joao@example.com",
  "tipo": "cliente",
  "ativo": true
}
```

---

### Logout

**POST** `/logout`

Revoga o token atual do usuário.

**Headers:**
```
Authorization: Bearer {token}
```

**Resposta (200):**
```json
{
  "message": "Logout realizado."
}
```

---

## 🔔 Configurações de Lembrete

### Obter Configuração de Lembrete

**GET** `/user/lembrete-config`

Retorna a configuração de lembrete do usuário logado.

**Resposta (200):**
```json
{
  "id": 1,
  "user_id": 1,
  "minutos_antes": 1440,
  "created_at": "2026-01-15T10:00:00.000000Z",
  "updated_at": "2026-01-15T10:00:00.000000Z"
}
```

**Resposta (404) - Se não existir:**
```json
{
  "message": "Configuração não encontrada. Use POST para criar uma nova configuração.",
  "config": null
}
```

---

### Criar/Atualizar Configuração de Lembrete

**POST** `/user/lembrete-config`

Cria ou atualiza a configuração de lembrete do usuário.

**Body:**
```json
{
  "minutos_antes": 1440
}
```

**Validação:**
- `minutos_antes`: obrigatório, inteiro, mínimo 1, máximo 10080 (7 dias)

**Resposta (201):**
```json
{
  "message": "Configuração salva com sucesso.",
  "config": {
    "id": 1,
    "user_id": 1,
    "minutos_antes": 1440
  }
}
```

---

### Atualizar Configuração de Lembrete

**PUT/PATCH** `/user/lembrete-config`

Atualiza a configuração de lembrete existente.

**Body:**
```json
{
  "minutos_antes": 60
}
```

**Resposta (200):**
```json
{
  "message": "Configuração atualizada com sucesso.",
  "config": {
    "id": 1,
    "user_id": 1,
    "minutos_antes": 60
  }
}
```

---

### Remover Configuração de Lembrete

**DELETE** `/user/lembrete-config`

Remove a configuração de lembrete do usuário.

**Resposta (200):**
```json
{
  "message": "Configuração removida com sucesso."
}
```

---

## 📅 Agendamentos

### Verificar Disponibilidade

**GET** `/agendamentos/disponibilidade`

Verifica horários disponíveis para agendamento.

**Query Parameters:**
- `servico_id` (required): ID do serviço
- `data` (required): Data no formato Y-m-d
- `profissional_id` (optional): ID do profissional específico

**Resposta (200):**
```json
[
  "09:00",
  "09:30",
  "10:00",
  "14:00",
  "15:00"
]
```

---

### Criar Agendamento

**POST** `/agendamentos`

Cria um novo agendamento.

**Body:**
```json
{
  "estabelecimento_id": 1,
  "profissional_id": 2,
  "servico_id": 1,
  "inicio_horario": "2026-01-20 14:00:00"
}
```

**Resposta (201):**
```json
{
  "mensagem": "Agendamento realizado com sucesso!",
  "dados": {
    "id": 1,
    "cliente_id": 1,
    "profissional_id": 2,
    "servico_id": 1,
    "inicio_horario": "2026-01-20 14:00:00",
    "fim_horario": "2026-01-20 14:30:00",
    "status": "pendente"
  }
}
```

---

### Cancelar Agendamento

**PATCH** `/agendamentos/{id}/cancelar`

Cancela um agendamento existente.

**Resposta (200):**
```json
{
  "mensagem": "Agendamento cancelado com sucesso."
}
```

---

### Confirmar Presença

**POST** `/agendamentos/{id}/confirmar-presenca`

Confirma a presença do cliente no agendamento.

**Resposta (200):**
```json
{
  "mensagem": "Presença confirmada com sucesso."
}
```

---

### Listar Horários de Funcionamento

**GET** `/horarios-funcionamento?estabelecimento_id={id}`

Lista os horários de funcionamento de um estabelecimento.

**Query Parameters:**
- `estabelecimento_id` (required): ID do estabelecimento

**Resposta (200):**
```json
[
  {
    "id": 1,
    "dia_semana": 1,
    "hora_abertura": "09:00",
    "hora_fechamento": "18:00"
  }
]
```

**Dias da semana:** 0 = Domingo, 1 = Segunda, ..., 6 = Sábado

---

## 👨‍💼 Rotas de Profissional (Requerem Middleware)

### Consultar Agenda do Profissional

**GET** `/profissional/consultar-agenda`

Retorna a agenda do profissional logado.

**Resposta (200):**
```json
[
  {
    "id": 1,
    "cliente_nome": "João Silva",
    "servico": {
      "nome": "Corte de Cabelo"
    },
    "inicio_horario": "2026-01-20 14:00:00",
    "fim_horario": "2026-01-20 14:30:00",
    "status": "confirmado"
  }
]
```

---

### Atualizar Status do Agendamento

**PATCH** `/agendamentos/{id}/atualizar-status`

Atualiza o status de um agendamento.

**Body:**
```json
{
  "status": "confirmado"
}
```

**Status disponíveis:** `pendente`, `confirmado`, `cancelado`, `concluido`, `aguardando_confirmacao`

**Resposta (200):**
```json
{
  "mensagem": "Status atualizado com sucesso."
}
```

---

### Criar Bloqueio na Agenda

**POST** `/profissional/bloqueios`

Cria um bloqueio de horário na agenda do profissional.

**Body:**
```json
{
  "data": "2026-01-20",
  "hora_inicio": "12:00",
  "hora_fim": "14:00",
  "motivo": "Almoço"
}
```

**Resposta (201):**
```json
{
  "mensagem": "Bloqueio criado com sucesso.",
  "bloqueio": {
    "id": 1,
    "profissional_id": 2,
    "data": "2026-01-20",
    "hora_inicio": "12:00",
    "hora_fim": "14:00"
  }
}
```

---

### Remover Bloqueio

**DELETE** `/profissional/bloqueios/{id}`

Remove um bloqueio da agenda.

**Resposta (200):**
```json
{
  "mensagem": "Bloqueio removido com sucesso."
}
```

---

### Agendamento Manual

**POST** `/profissional/agendar-manual`

Permite que o profissional crie um agendamento manualmente.

**Body:**
```json
{
  "cliente_nome": "Cliente sem cadastro",
  "servico_id": 1,
  "inicio_horario": "2026-01-20 15:00:00",
  "observacoes": "Cliente preferencial"
}
```

**Resposta (201):**
```json
{
  "mensagem": "Agendamento manual criado com sucesso."
}
```

---

### Criar Serviço

**POST** `/servicos`

Cria um novo serviço no estabelecimento do profissional.

**Body:**
```json
{
  "nome": "Manicure",
  "duracao_minutos": 60,
  "preco": 80.00,
  "observacao": "Inclui esmaltação"
}
```

**Resposta (201):**
```json
{
  "id": 2,
  "nome": "Manicure",
  "duracao_minutos": 60,
  "preco": 80.00,
  "ativo": true
}
```

---

### Atualizar Serviço

**PATCH** `/servicos/{id}`

Atualiza um serviço existente.

**Body:**
```json
{
  "preco": 90.00,
  "ativo": true
}
```

**Resposta (200):**
```json
{
  "id": 2,
  "nome": "Manicure",
  "preco": 90.00
}
```

---

### Remover Serviço

**DELETE** `/servicos/{id}`

Desativa um serviço (não remove do banco).

**Resposta (200):**
```json
{
  "message": "Serviço desativado com sucesso."
}
```

---

### Criar Horário de Funcionamento

**POST** `/horarios-funcionamento`

Cria um novo horário de funcionamento.

**Body:**
```json
{
  "dia_semana": 1,
  "hora_abertura": "09:00",
  "hora_fechamento": "18:00"
}
```

**Resposta (201):**
```json
{
  "id": 1,
  "dia_semana": 1,
  "hora_abertura": "09:00",
  "hora_fechamento": "18:00"
}
```

---

### Remover Horário de Funcionamento

**DELETE** `/horarios-funcionamento/{id}`

Remove um horário de funcionamento.

**Resposta (200):**
```json
{
  "mensagem": "Horário removido com sucesso."
}
```

---

## 🔔 Notificações

### Listar Notificações

**GET** `/notificacoes`

Lista todas as notificações do usuário logado.

**Resposta (200):**
```json
[
  {
    "id": "uuid-aqui",
    "type": "App\\Notifications\\AgendamentoAtualizado",
    "notifiable_id": 1,
    "data": {
      "mensagem": "Você confirma sua presença em 1 dia?"
    },
    "read_at": null,
    "created_at": "2026-01-20T10:00:00.000000Z"
  }
]
```

---

### Marcar Notificação como Lida

**PATCH** `/notificacoes/{id}/ler`

Marca uma notificação específica como lida.

**Resposta (200):**
```json
{
  "mensagem": "Notificação lida"
}
```

---

## 📊 Códigos de Status HTTP

- `200` - Sucesso
- `201` - Criado com sucesso
- `401` - Não autenticado
- `403` - Não autorizado
- `404` - Não encontrado
- `422` - Erro de validação
- `500` - Erro interno do servidor

---

## ⚠️ Tratamento de Erros

Todas as respostas de erro seguem o formato:

```json
{
  "message": "Mensagem de erro descritiva"
}
```

Para erros de validação (422):

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

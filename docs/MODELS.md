# Modelos e Estrutura de Dados

Documentação completa sobre os modelos e estrutura de dados da API Agendo.

## 📊 Visão Geral

A API utiliza Eloquent ORM do Laravel para gerenciar os modelos. Todos os modelos estão localizados em `app/Models/`.

---

## 👤 User (Usuário)

Representa todos os tipos de usuários do sistema (clientes, profissionais e estabelecimentos).

### Campos

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | ID único do usuário |
| `name` | string | Nome completo |
| `email` | string | Email (único) |
| `telefone` | string | Telefone (opcional) |
| `password` | string | Senha hash (nullable para login Google) |
| `google_id` | string | ID do Google (nullable, único) |
| `tipo` | enum | Tipo: `cliente`, `profissional`, `admin` |
| `ativo` | boolean | Status de ativação |
| `estabelecimento_id` | integer | FK para estabelecimento (nullable) |
| `is_admin_estabelecimento` | boolean | Se é dono do estabelecimento |
| `email_verified_at` | datetime | Data de verificação do email |
| `created_at` | datetime | Data de criação |
| `updated_at` | datetime | Data de atualização |

### Relacionamentos

```php
// Um usuário pertence a um estabelecimento
$user->estabelecimento()

// Um usuário tem uma configuração de lembrete
$user->lembreteConfig()

// Agendamentos como cliente
$user->consultas()

// Agendamentos como profissional
$user->agenda()
```

### Métodos Úteis

```php
$user->isAdmin()              // Verifica se é admin
$user->isProfissional()       // Verifica se é profissional
$user->isCliente()            // Verifica se é cliente
$user->isAtivo()              // Verifica se está ativo
$user->isAdminEstabelecimento() // Verifica se é dono
```

---

## 🏢 Estabelecimento

Representa um estabelecimento comercial.

### Campos

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | ID único |
| `nome` | string | Nome do estabelecimento |
| `identificador` | string | Identificador único (slug) |
| `endereco` | string | Endereço completo |
| `ramo` | enum | Ramo: `beleza`, `saude`, `terapia`, `outros` |
| `fuso_horario` | string | Fuso horário (padrão: America/Sao_Paulo) |
| `created_at` | datetime | Data de criação |
| `updated_at` | datetime | Data de atualização |

### Relacionamentos

```php
// Um estabelecimento tem um dono
$estabelecimento->dono()

// Um estabelecimento tem vários profissionais
$estabelecimento->profissionais()

// Um estabelecimento oferece vários serviços
$estabelecimento->servicos()

// Um estabelecimento tem vários horários de funcionamento
$estabelecimento->horariosFuncionamento()
```

---

## 📅 Agendamento

Representa um agendamento de serviço.

### Campos

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | ID único |
| `estabelecimento_id` | integer | FK para estabelecimento |
| `cliente_id` | integer | FK para cliente (nullable) |
| `cliente_nome` | string | Nome do cliente (para agendamentos manuais) |
| `profissional_id` | integer | FK para profissional |
| `servico_id` | integer | FK para serviço |
| `inicio_horario` | datetime | Data/hora de início |
| `fim_horario` | datetime | Data/hora de fim |
| `status` | string | Status: `pendente`, `confirmado`, `cancelado`, `concluido`, `aguardando_confirmacao` |
| `observacoes` | text | Observações adicionais |
| `status_alterado_por` | integer | FK para usuário que alterou status |
| `status_autor_tipo` | string | Tipo de quem alterou: `cliente`, `profissional` |
| `created_at` | datetime | Data de criação |
| `updated_at` | datetime | Data de atualização |

### Relacionamentos

```php
// Agendamento pertence a um cliente
$agendamento->cliente()

// Agendamento pertence a um profissional
$agendamento->profissional()

// Agendamento pertence a um serviço
$agendamento->servico()

// Quem alterou o status
$agendamento->statusAlteradoPor()
```

### Status Disponíveis

- `pendente` - Aguardando confirmação
- `confirmado` - Confirmado pelo cliente/profissional
- `cancelado` - Cancelado
- `concluido` - Serviço realizado
- `aguardando_confirmacao` - Aguardando confirmação de presença

---

## 💼 Servico

Representa um serviço oferecido por um estabelecimento.

### Campos

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | ID único |
| `estabelecimento_id` | integer | FK para estabelecimento |
| `nome` | string | Nome do serviço |
| `duracao_minutos` | integer | Duração em minutos |
| `preco` | decimal | Preço do serviço |
| `observacao` | text | Observações sobre o serviço |
| `ativo` | boolean | Se o serviço está ativo |
| `created_at` | datetime | Data de criação |
| `updated_at` | datetime | Data de atualização |

### Relacionamentos

```php
// Serviço pertence a um estabelecimento
$servico->estabelecimento()
```

---

## ⏰ HorarioFuncionamento

Representa os horários de funcionamento de um estabelecimento.

### Campos

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | ID único |
| `estabelecimento_id` | integer | FK para estabelecimento |
| `dia_semana` | integer | Dia da semana (0=Domingo, 6=Sábado) |
| `hora_abertura` | time | Hora de abertura |
| `hora_fechamento` | time | Hora de fechamento |
| `created_at` | datetime | Data de criação |
| `updated_at` | datetime | Data de atualização |

### Dias da Semana

- `0` - Domingo
- `1` - Segunda-feira
- `2` - Terça-feira
- `3` - Quarta-feira
- `4` - Quinta-feira
- `5` - Sexta-feira
- `6` - Sábado

### Relacionamentos

```php
// Horário pertence a um estabelecimento
$horario->estabelecimento()
```

---

## 🚫 BloqueioAgenda

Representa um bloqueio de horário na agenda de um profissional.

### Campos

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | ID único |
| `profissional_id` | integer | FK para profissional |
| `data` | date | Data do bloqueio |
| `hora_inicio` | time | Hora de início do bloqueio |
| `hora_fim` | time | Hora de fim do bloqueio |
| `motivo` | string | Motivo do bloqueio |
| `created_at` | datetime | Data de criação |
| `updated_at` | datetime | Data de atualização |

---

## 🔔 UserLembreteConfig

Representa a configuração de lembretes de um usuário.

### Campos

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | integer | ID único |
| `user_id` | integer | FK para usuário (único) |
| `minutos_antes` | integer | Minutos antes do agendamento para enviar lembrete |
| `created_at` | datetime | Data de criação |
| `updated_at` | datetime | Data de atualização |

### Validações

- `minutos_antes`: mínimo 1, máximo 10080 (7 dias)
- Um usuário pode ter apenas uma configuração (unique `user_id`)

### Relacionamentos

```php
// Configuração pertence a um usuário
$config->user()
```

### Valor Padrão

Ao registrar um novo usuário, é criada automaticamente uma configuração com `minutos_antes = 1440` (1 dia).

---

## 📊 Diagrama de Relacionamentos

```
User
├── Estabelecimento (belongsTo - nullable)
├── UserLembreteConfig (hasOne)
├── Agendamentos como Cliente (hasMany - consultas)
└── Agendamentos como Profissional (hasMany - agenda)

Estabelecimento
├── Dono (hasOne - User com is_admin_estabelecimento)
├── Profissionais (hasMany - User tipo profissional)
├── Servicos (hasMany)
└── HorariosFuncionamento (hasMany)

Agendamento
├── Cliente (belongsTo - User)
├── Profissional (belongsTo - User)
├── Servico (belongsTo)
└── Estabelecimento (belongsTo)

Servico
└── Estabelecimento (belongsTo)

HorarioFuncionamento
└── Estabelecimento (belongsTo)

BloqueioAgenda
└── Profissional (belongsTo - User)

UserLembreteConfig
└── User (belongsTo)
```

---

## 🔍 Consultas Úteis

### Buscar Usuário com Estabelecimento

```php
$user = User::with('estabelecimento')->find($id);
```

### Buscar Estabelecimento com Relacionamentos

```php
$estabelecimento = Estabelecimento::with([
    'dono',
    'profissionais',
    'servicos',
    'horariosFuncionamento'
])->find($id);
```

### Buscar Agendamentos do Cliente

```php
$agendamentos = Agendamento::where('cliente_id', $userId)
    ->with(['profissional', 'servico'])
    ->get();
```

### Buscar Agendamentos do Profissional

```php
$agendamentos = Agendamento::where('profissional_id', $userId)
    ->with(['cliente', 'servico'])
    ->get();
```

### Buscar Serviços Ativos

```php
$servicos = Servico::where('estabelecimento_id', $estabelecimentoId)
    ->where('ativo', true)
    ->get();
```

---

## 📝 Observações Importantes

1. **Soft Deletes**: Nenhum modelo utiliza soft deletes. Serviços são desativados (`ativo = false`) em vez de deletados.

2. **Cascades**: 
   - Deletar um usuário remove sua configuração de lembrete (cascade)
   - Deletar um estabelecimento não remove usuários (nullOnDelete)

3. **Unicidade**:
   - `email` é único na tabela `users`
   - `google_id` é único na tabela `users`
   - `identificador` é único na tabela `estabelecimentos`
   - `user_id` é único na tabela `user_lembretes_config`

4. **Valores Padrão**:
   - `ativo` em User: `false` (exceto clientes que nascem `true`)
   - `fuso_horario` em Estabelecimento: `America/Sao_Paulo`
   - `minutos_antes` em UserLembreteConfig: `1440` (1 dia)

5. **Timestamps**: Todos os modelos utilizam `created_at` e `updated_at` automaticamente.

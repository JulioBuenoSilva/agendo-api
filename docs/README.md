# Documentação Agendo API

Bem-vindo à documentação completa da API Agendo!

## 📚 Índice da Documentação

### [📖 Documentação da API](API.md)
Documentação completa de todos os endpoints disponíveis na API, incluindo:
- Rotas públicas e protegidas
- Parâmetros de requisição
- Exemplos de resposta
- Códigos de status HTTP
- Tratamento de erros

### [🔐 Autenticação](AUTHENTICATION.md)
Guia completo sobre autenticação:
- Login e registro tradicional
- Autenticação via Google OAuth
- Uso de tokens
- Tipos de usuário e permissões
- Fluxo de aprovação
- Tratamento de erros de autenticação

### [🗄️ Modelos e Estrutura de Dados](MODELS.md)
Documentação dos modelos do sistema:
- Estrutura de cada modelo
- Relacionamentos entre modelos
- Campos e tipos de dados
- Consultas úteis
- Diagrama de relacionamentos

### [⚙️ Configuração e Instalação](SETUP.md)
Guia passo a passo para:
- Instalação do projeto
- Configuração do ambiente
- Configuração do banco de dados
- Configuração do Google OAuth
- Configuração de email
- Troubleshooting

## 🚀 Início Rápido

1. Comece pela [Configuração e Instalação](SETUP.md)
2. Configure a [Autenticação](AUTHENTICATION.md)
3. Explore os [Endpoints da API](API.md)
4. Entenda a [Estrutura de Dados](MODELS.md)

## 📝 Convenções

- Todas as rotas começam com `/api`
- Autenticação via Bearer Token
- Respostas em formato JSON
- Datas no formato ISO 8601

## 🔗 Links Úteis

- [Repositório GitHub](https://github.com/JulioBuenoSilva/agendo-api)
- [Laravel Documentation](https://laravel.com/docs)
- [Laravel Sanctum](https://laravel.com/docs/sanctum)

## 💡 Dicas

- Use Postman ou Insomnia para testar os endpoints
- Sempre inclua o header `Authorization: Bearer {token}` em rotas protegidas
- Verifique os códigos de status HTTP nas respostas
- Consulte a seção de tratamento de erros em cada documento

## 🤝 Contribuindo

Encontrou algum erro ou quer melhorar a documentação? Abra uma issue ou pull request no GitHub!

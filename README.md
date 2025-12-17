# Metamorphose Framework

<div align="center">

**Kernel de aplicação PHP modular, multi-tenant e preparado para microserviços**

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.1-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![PSR Standards](https://img.shields.io/badge/PSR-4%2C7%2C11%2C15-blue.svg)](https://www.php-fig.org/)

</div>

## 📖 Sobre

O **Metamorphose Framework** é um kernel de aplicação PHP moderno, baseado em Slim Framework, projetado para construir aplicações modulares, escaláveis e multi-tenant. Com arquitetura explícita e sem dependências ocultas, oferece flexibilidade total para evoluir de monólito para microserviços quando necessário.

## ✨ Principais Características

### 🧩 Arquitetura Modular
- **Módulos plugáveis**: Adicione ou remova funcionalidades sem afetar outros módulos
- **Baixo acoplamento**: Módulos independentes e autocontidos
- **Fácil manutenção**: Código organizado e bem estruturado

### 🏢 Multi-Tenant Híbrido
- **Três escopos de dados**: Core (global), Tenant e Unit
- **Isolamento completo**: Cada tenant/unit possui seus próprios dados
- **Contextos explícitos**: Gerenciamento claro de contexto sem estado global

### 🚀 Preparado para Microserviços
- **Migração gradual**: Comece como monólito, evolua para microserviços
- **Configuração simples**: Apenas altere `config/modules.php` para extrair módulos
- **Transparente**: Mesmo código funciona em ambos os modos

### 🛠️ Ferramentas Integradas
- **CLI próprio**: Comandos para criar módulos, migrações e gerar documentação
- **Swagger/OpenAPI**: Documentação automática de APIs
- **Sistema de logs**: Logging estruturado com contexto automático
- **Sistema de permissões**: Permissões multi-escopo (global, tenant, unit)

### ⚡ Performance e Compatibilidade
- **Runtime agnóstico**: Funciona com PHP-FPM, Swoole e FrankenPHP
- **Sem estado global mutável**: Compatível com runtimes persistentes
- **PSR compliant**: Segue padrões PSR-4, PSR-7, PSR-11 e PSR-15

## 🎯 Vantagens

### Para Desenvolvedores
- ✅ **Código explícito**: Sem mágica oculta, tudo é claro e documentado
- ✅ **Fácil aprendizado**: Arquitetura simples e intuitiva
- ✅ **Produtividade**: CLI acelera criação de módulos e migrações
- ✅ **Documentação automática**: Swagger gera docs a partir de anotações

### Para Arquitetos
- ✅ **Flexibilidade**: Escolha entre monólito ou microserviços
- ✅ **Escalabilidade**: Escale módulos independentemente
- ✅ **Manutenibilidade**: Módulos isolados facilitam manutenção
- ✅ **Extensibilidade**: Fácil adicionar novos módulos e funcionalidades

### Para Negócios
- ✅ **Multi-tenant nativo**: Suporte completo para SaaS
- ✅ **Redução de custos**: Escale apenas o necessário
- ✅ **Time to market**: Desenvolvimento mais rápido com CLI e estrutura pronta
- ✅ **Evolução gradual**: Migre para microserviços sem reescrever código

## 🚀 Início Rápido

### Instalação

```bash
# Clone o repositório
git clone https://github.com/seu-usuario/metamorphose-framework.git
cd metamorphose-framework

# Instale as dependências
composer install
```

### Configuração

Configure as variáveis de ambiente ou edite os arquivos em `/config`:

```bash
# Copie e configure as variáveis de ambiente
cp .env.example .env
```

### Criar seu Primeiro Módulo

```bash
# Criar um novo módulo
php bin/metamorphose module:make ProductCatalog

# Gerar documentação Swagger
php bin/metamorphose swagger:generate

# Executar migrações
php bin/metamorphose migrate --scope=core
```

### Executar a Aplicação

```bash
# Usando o comando serve (recomendado - gera Swagger automaticamente)
php bin/metamorphose serve

# Ou com porta customizada
php bin/metamorphose serve --port=8080

# Ou servidor PHP built-in manual
php -S localhost:8000 -t public

# Ou configure seu servidor web (Apache/Nginx)
# Aponte para: public/
```

Acesse:
- **API**: `http://localhost:8000`
- **Swagger UI**: `http://localhost:8000/swagger-ui`
- **Swagger JSON**: `http://localhost:8000/swagger.json`

## 📚 Documentação

📖 **Documentação completa disponível em:**
- [Português](docs/pt/README.md)
- [English](docs/en/README.md)

### Tópicos Principais

- [Instalação](docs/pt/installation.md) - Guia completo de instalação
- [Primeiros Passos](docs/pt/getting-started.md) - Seu primeiro módulo
- [Arquitetura](docs/pt/architecture.md) - Entendendo o framework
- [Módulos](docs/pt/modules.md) - Criando e gerenciando módulos
- [Microserviços](docs/pt/microservices.md) - Extraindo módulos para microserviços
- [Swagger](docs/pt/swagger.md) - Documentação automática de APIs
- [CLI](docs/pt/cli.md) - Comandos disponíveis
- [Contextos](docs/pt/contexts.md) - Gerenciamento de contexto
- [Banco de Dados](docs/pt/database.md) - Conexões e migrações
- [Logs](docs/pt/logging.md) - Sistema de logging
- [Permissões](docs/pt/permissions.md) - Sistema de permissões

## 🏗️ Estrutura do Projeto

```
metamorphose-framework/
├── app/
│   ├── Bootstrap/          # Inicialização da aplicação
│   ├── Kernel/             # Componentes principais
│   │   ├── Context/        # Contextos (Tenant, Unit, Request)
│   │   ├── Module/          # Sistema de módulos
│   │   ├── Database/        # Gerenciamento de conexões
│   │   ├── Log/             # Sistema de logs
│   │   ├── Permission/      # Sistema de permissões
│   │   ├── Migration/       # Executor de migrações
│   │   └── Swagger/         # Integração Swagger
│   ├── Modules/             # Seus módulos de aplicação
│   └── CLI/                 # Comandos CLI
├── config/                  # Arquivos de configuração
├── public/                  # Ponto de entrada HTTP
├── bin/                     # Executáveis CLI
└── docs/                    # Documentação completa
```

## 💻 Exemplo de Uso

### Criar um Módulo

```bash
php bin/metamorphose module:make Blog
```

### Documentar uma API

```php
<?php

namespace Metamorphose\Modules\Blog\Controller;

use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Tag(name: "Blog", description: "Gerenciamento de posts")]
class BlogController
{
    #[OA\Get(
        path: "/blog",
        summary: "Lista posts",
        tags: ["Blog"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de posts",
                content: new OA\JsonContent(type: "array")
            )
        ]
    )]
    public function index(...): ResponseInterface
    {
        // Sua implementação
    }
}
```

### Gerar Documentação

```bash
php bin/metamorphose swagger:generate
```

## 🛠️ Comandos CLI Disponíveis

```bash
# Iniciar servidor de desenvolvimento (com Swagger)
php bin/metamorphose serve

# Criar módulo
php bin/metamorphose module:make NomeDoModulo

# Remover módulo
php bin/metamorphose module:remove NomeDoModulo

# Executar migrações
php bin/metamorphose migrate --scope=core
php bin/metamorphose migrate --scope=tenant
php bin/metamorphose migrate --scope=unit

# Gerar documentação Swagger
php bin/metamorphose swagger:generate
```

## 🔧 Requisitos

- PHP >= 8.1
- Composer
- MySQL/MariaDB (ou banco compatível)
- Extensões PHP: PDO, JSON, MBString

## 📦 Tecnologias Utilizadas

- **Slim Framework** - Roteamento e middleware
- **PHP-DI** - Injeção de dependência
- **Monolog** - Sistema de logs
- **swagger-php** - Documentação OpenAPI
- **PSR Standards** - Padrões da comunidade PHP

## 🎓 Casos de Uso

### SaaS Multi-Tenant
Perfeito para aplicações SaaS que precisam isolar dados por cliente (tenant) e possivelmente por unidade/filial.

### Aplicações Modulares
Ideal para sistemas que precisam de funcionalidades plugáveis e facilmente extensíveis.

### Migração para Microserviços
Comece como monólito e migre gradualmente para microserviços conforme a necessidade de escala.

### APIs RESTful
Estrutura perfeita para construir APIs RESTful bem documentadas e escaláveis.

## 🤝 Contribuindo

Contribuições são bem-vindas! Por favor:

1. Fork o projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## 📄 Licença

Este projeto está licenciado sob a Licença MIT - veja o arquivo [LICENSE](LICENSE) para detalhes.

## 🙏 Agradecimentos

- [Slim Framework](https://www.slimframework.com/) - Framework base
- [PHP-DI](https://php-di.org/) - Container de injeção de dependência
- [swagger-php](https://zircote.com/swagger-php/) - Geração de documentação OpenAPI
- Comunidade PHP e padrões PSR

## 📞 Suporte

- 📖 [Documentação Completa](docs/pt/README.md)
- 🐛 [Reportar Bugs](https://github.com/seu-usuario/metamorphose-framework/issues)
- 💬 [Discutir Ideias](https://github.com/seu-usuario/metamorphose-framework/discussions)

---

<div align="center">

**Desenvolvido com ❤️ para a comunidade PHP**

[Documentação](docs/pt/README.md) • [Exemplos](docs/pt/getting-started.md) • [Contribuir](CONTRIBUTING.md)

</div>

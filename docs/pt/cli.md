# Comandos CLI

O Metamorphose Framework inclui uma interface de linha de comando (CLI) para tarefas comuns como criar módulos e executar migrações.

## Uso

```bash
php bin/metamorphose [comando] [opções]
```

Ou use o atalho:

```bash
php bin/migrate [opções]  # Atalho para o comando migrate
```

## Comandos Disponíveis

### module:make

Cria um novo módulo com a estrutura completa de diretórios.

**Uso:**
```bash
php bin/metamorphose module:make NomeDoModulo
```

**Exemplo:**
```bash
php bin/metamorphose module:make ProductCatalog
```

**O que cria:**
- `app/Modules/ProductCatalog/Module.php` - Classe principal do módulo
- `app/Modules/ProductCatalog/Routes.php` - Arquivo de rotas
- `app/Modules/ProductCatalog/config.php` - Configuração do módulo
- `app/Modules/ProductCatalog/Controller/ProductCatalogController.php` - Controller de exemplo
- `app/Modules/ProductCatalog/Service/` - Diretório de serviços
- `app/Modules/ProductCatalog/Repository/` - Diretório de repositories
- `app/Modules/ProductCatalog/Entity/` - Diretório de entidades
- `app/Modules/ProductCatalog/Migrations/core/` - Diretório de migrações core
- `app/Modules/ProductCatalog/Migrations/tenant/` - Diretório de migrações tenant
- `app/Modules/ProductCatalog/Migrations/unit/` - Diretório de migrações unit

**Após a criação:**
1. Registre o módulo em `config/modules.php`
2. Implemente sua lógica de negócio
3. Crie migrações se necessário
4. Teste seu módulo

### serve

Inicia um servidor PHP local de desenvolvimento com a aplicação e Swagger UI.

**Uso:**
```bash
php bin/metamorphose serve [opções]
```

**Opções:**
- `--host=HOST` - Host do servidor (padrão: localhost)
- `--port=PORT` - Porta do servidor (padrão: 8000)
- `--no-swagger` - Não gerar documentação Swagger antes de iniciar

**Exemplos:**
```bash
# Iniciar servidor na porta padrão (8000)
php bin/metamorphose serve

# Iniciar servidor em porta customizada
php bin/metamorphose serve --port=8080

# Iniciar servidor em host e porta customizados
php bin/metamorphose serve --host=0.0.0.0 --port=8080

# Iniciar sem gerar Swagger
php bin/metamorphose serve --no-swagger
```

**O que faz:**
1. Gera documentação Swagger (a menos que `--no-swagger` seja usado)
2. Inicia servidor PHP built-in
3. Serve aplicação do diretório `public/`
4. Mostra URLs para:
   - Aplicação: `http://localhost:8000`
   - Swagger UI: `http://localhost:8000/swagger-ui`
   - Swagger JSON: `http://localhost:8000/swagger.json`

**Nota:** Pressione `Ctrl+C` para parar o servidor.

### test

Executa testes unitários e de funcionalidade usando PHPUnit.

**Uso:**
```bash
php bin/metamorphose test [opções]
```

**Opções:**
- `--filter=FILTRO` - Filtrar testes por padrão de nome
- `--coverage` - Gerar relatório de cobertura de código
- `-v` ou `--verbose` - Saída verbosa
- `--stop-on-failure` - Parar execução no primeiro erro

**Exemplos:**
```bash
# Executar todos os testes
php bin/metamorphose test

# Executar testes que correspondem a um filtro
php bin/metamorphose test --filter=RequestContext

# Executar com cobertura de código
php bin/metamorphose test --coverage

# Executar com saída verbosa
php bin/metamorphose test -v

# Parar no primeiro erro
php bin/metamorphose test --stop-on-failure
```

**Alternativa:**
Você também pode usar o PHPUnit diretamente:
```bash
vendor/bin/phpunit
composer test
```

### log:clear

Remove arquivos de log antigos ou todos os logs do sistema.

**Uso:**
```bash
php bin/metamorphose log:clear [opções]
```

**Opções:**
- `--days=N` - Remove logs mais antigos que N dias (padrão: 7 dias)
- `--all` - Remove todos os arquivos de log
- `-y` ou `--yes` - Confirmação automática (não pergunta)

**Exemplos:**
```bash
# Remover logs mais antigos que 7 dias (padrão)
php bin/metamorphose log:clear

# Remover logs mais antigos que 30 dias
php bin/metamorphose log:clear --days=30

# Remover todos os logs (com confirmação)
php bin/metamorphose log:clear --all

# Remover todos os logs sem confirmação
php bin/metamorphose log:clear --all -y

# Remover logs antigos sem confirmação
php bin/metamorphose log:clear --days=14 -y
```

**O que faz:**
1. Escaneia o diretório de logs configurado em `config/log.php`
2. Lista arquivos de log encontrados
3. Remove logs conforme as opções especificadas:
   - Com `--days=N`: Remove apenas logs mais antigos que N dias
   - Com `--all`: Remove todos os arquivos de log
4. Mostra estatísticas de arquivos removidos (quantidade e tamanho)

**Exemplo de saída:**
```
Found 5 log file(s) older than 7 day(s) (2.5 MB)
  - 2025-12-10.log (2025-12-10, 512.5 KB)
  - 2025-12-11.log (2025-12-11, 1.2 MB)
  - 2025-12-12.log (2025-12-12, 786.3 KB)

Are you sure you want to delete these files? (yes/no): yes

✅ Successfully deleted 3 file(s) (2.5 MB)
```

**Nota:** O diretório de logs é configurado em `config/log.php` através da opção `path`.

### migrate

Executa migrações de banco de dados para um escopo específico.

**Uso:**
```bash
php bin/metamorphose migrate --scope=core
php bin/metamorphose migrate --scope=tenant
php bin/metamorphose migrate --scope=unit
```

**Atalho:**
```bash
php bin/migrate --scope=core
```

**Opções:**
- `--scope=core`: Executa migrações de escopo core
- `--scope=tenant`: Executa migrações de escopo tenant
- `--scope=unit`: Executa migrações de escopo unit

**Como funciona:**
1. Escaneia todos os módulos habilitados para migrações no escopo especificado
2. Verifica quais migrações já foram executadas
3. Executa migrações pendentes em ordem
4. Registra migrações executadas na tabela `migrations`

**Exemplo de saída:**
```
Migrações executadas com sucesso para o escopo: core
```

**Tratamento de erros:**
- Se uma migração falhar, ela faz rollback da transação
- Migrações anteriores permanecem executadas
- Verifique a mensagem de erro para detalhes

## Criando Comandos Personalizados

Você pode criar comandos CLI personalizados implementando `CommandInterface`:

### Passo 1: Criar Classe de Comando

Crie `app/CLI/Commands/SeuComando.php`:

```php
<?php

namespace Metamorphose\CLI\Commands;

use Metamorphose\CLI\CommandInterface;

class SeuComando implements CommandInterface
{
    public function name(): string
    {
        return 'seu:comando';
    }

    public function description(): string
    {
        return 'Descrição do seu comando';
    }

    public function handle(array $args): int
    {
        // Sua lógica de comando aqui
        echo "Comando executado!\n";
        return 0; // 0 = sucesso, 1+ = erro
    }
}
```

### Passo 2: Registrar Comando

Edite `app/CLI/KernelCLI.php`:

```php
use Metamorphose\CLI\Commands\SeuComando;

private function registerDefaultCommands(): void
{
    $this->register(new ModuleMakeCommand());
    $this->register(new MigrateCommand());
    $this->register(new SeuComando()); // Adicione seu comando
}
```

### Passo 3: Usar Seu Comando

```bash
php bin/metamorphose seu:comando
```

## Interface de Comando

Todos os comandos devem implementar `CommandInterface`:

```php
interface CommandInterface
{
    /**
     * Nome do comando (ex: 'module:make')
     */
    public function name(): string;
    
    /**
     * Descrição do comando
     */
    public function description(): string;
    
    /**
     * Executar o comando
     * 
     * @param array $args Argumentos do comando
     * @return int Código de saída (0 = sucesso, 1+ = erro)
     */
    public function handle(array $args): int;
}
```

## Argumentos de Comando

Comandos recebem argumentos como um array:

```php
public function handle(array $args): int
{
    // $args[0] = primeiro argumento
    // $args[1] = segundo argumento
    // etc.
    
    if (empty($args[0])) {
        echo "Erro: Argumento obrigatório\n";
        return 1;
    }
    
    $value = $args[0];
    // Processar...
    
    return 0;
}
```

## Analisando Opções

Para analisar opções como `--scope=core`:

```php
private function parseScope(array $args): ?string
{
    foreach ($args as $arg) {
        if (str_starts_with($arg, '--scope=')) {
            return substr($arg, 8);
        }
    }
    
    return null;
}
```

## Códigos de Saída

- `0`: Sucesso
- `1` ou superior: Erro

Use códigos de saída apropriados para indicar sucesso ou falha:

```php
public function handle(array $args): int
{
    try {
        // Fazer algo
        return 0; // Sucesso
    } catch (\Exception $e) {
        echo "Erro: " . $e->getMessage() . "\n";
        return 1; // Erro
    }
}
```

## Comando de Ajuda

Para mostrar comandos disponíveis:

```bash
php bin/metamorphose
```

Isso exibe:
```
Metamorphose Framework CLI
==========================

Comandos disponíveis:

  module:make          Cria um novo módulo
  migrate              Executa migrações de banco de dados (--scope=core|tenant|unit)
```

## Melhores Práticas

1. **Validar argumentos**: Verificar argumentos obrigatórios antes de processar
2. **Fornecer erros claros**: Usar mensagens de erro descritivas
3. **Retornar códigos apropriados**: 0 para sucesso, 1+ para erros
4. **Tratar exceções**: Capturar e exibir erros graciosamente
5. **Documentar seu comando**: Adicionar comentários explicando o que o comando faz

## Exemplos

### Exemplo: Comando de Limpar Cache

```php
<?php

namespace Metamorphose\CLI\Commands;

use Metamorphose\CLI\CommandInterface;

class CacheClearCommand implements CommandInterface
{
    public function name(): string
    {
        return 'cache:clear';
    }

    public function description(): string
    {
        return 'Limpar cache da aplicação';
    }

    public function handle(array $args): int
    {
        $cachePath = __DIR__ . '/../../storage/cache';
        
        if (!is_dir($cachePath)) {
            echo "Diretório de cache não encontrado\n";
            return 1;
        }
        
        $files = glob($cachePath . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        echo "Cache limpo com sucesso\n";
        return 0;
    }
}
```

### Exemplo: Comando de Listar Módulos

```php
<?php

namespace Metamorphose\CLI\Commands;

use Metamorphose\CLI\CommandInterface;

class ModuleListCommand implements CommandInterface
{
    public function name(): string
    {
        return 'module:list';
    }

    public function description(): string
    {
        return 'Listar todos os módulos habilitados';
    }

    public function handle(array $args): int
    {
        $config = require __DIR__ . '/../../../config/modules.php';
        $modules = $config['enabled'] ?? [];
        
        echo "Módulos habilitados:\n\n";
        foreach ($modules as $module) {
            echo "  - {$module}\n";
        }
        
        return 0;
    }
}
```


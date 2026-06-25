# Deploy Seguro

Este guia prepara a Lumora para produção sem versionar segredos. Não coloque
tokens, senhas, chaves ou credenciais em README, scripts, templates, Dockerfile,
prints, issues ou pull requests.

## Estratégia Recomendada

Use um deploy monolítico:

- Nginx ou Caddy na frente, apontando o document root para `public/`;
- PHP-FPM executando Laravel;
- MariaDB em instância isolada ou serviço gerenciado;
- assets React/Vite gerados no build e servidos pelo Laravel;
- queue worker supervisionado por systemd ou Supervisor;
- HTTPS obrigatório;
- segredos gerenciados fora do Git, no painel da plataforma ou no ambiente do
  servidor.

Evite separar o React em hospedagem estática neste projeto, porque a SPA já é
entregue pelo Laravel e consome as rotas da mesma aplicação.

## Checklist Antes Do Deploy

- `APP_ENV=production`;
- `APP_DEBUG=false`;
- `APP_URL` e `FRONTEND_URL` usando HTTPS;
- `APP_KEY` gerada no ambiente de produção;
- banco com usuário próprio e permissões mínimas;
- `MERCADO_PAGO_SANDBOX=false`;
- `MERCADO_PAGO_WEBHOOK_SECRET` configurado;
- `SESSION_SECURE_COOKIE=true`;
- `SESSION_ENCRYPT=true`;
- `SANCTUM_STATEFUL_DOMAINS` com o domínio de produção;
- logs sem dados sensíveis;
- backups e rotação de credenciais definidos;
- `.env`, `.env.*`, `*.save`, `*.bak` e `*~` fora do Git.

## Variáveis De Ambiente

Configure estas variáveis no servidor ou plataforma. Os valores abaixo não devem
ser preenchidos em arquivos versionados.

```env
APP_NAME=
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=
APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=pt_BR
APP_FAKER_LOCALE=pt_BR

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

CACHE_STORE=database
CACHE_PREFIX=lumora
QUEUE_CONNECTION=database
SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SESSION_DOMAIN=

SANCTUM_STATEFUL_DOMAINS=
SANCTUM_TOKEN_PREFIX=lumora_

VIACEP_URL=https://viacep.com.br
CA_BUNDLE_PATH=

MERCADO_PAGO_ACCESS_TOKEN=
MERCADO_PAGO_PUBLIC_KEY=
MERCADO_PAGO_WEBHOOK_SECRET=
MERCADO_PAGO_SANDBOX=false
FRONTEND_URL=

MAIL_MAILER=
MAIL_HOST=
MAIL_PORT=
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME="${APP_NAME}"
```

Se usar Redis, S3, Slack logs ou outro serviço externo, configure apenas no
ambiente seguro da infraestrutura.

## Build

Execute no release, nunca em diretório compartilhado com arquivos sensíveis:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

## Banco

Rode migrações em janela controlada e com backup recente:

```bash
php artisan migrate --force
```

Não rode `db:wipe`, `migrate:fresh`, `migrate:refresh` ou seeders em produção,
salvo em ambiente descartável e com confirmação explícita.

## Processos

Servidor web:

```bash
php-fpm
```

Fila:

```bash
php artisan queue:work database --sleep=3 --tries=3 --timeout=90
```

Depois de publicar novo código:

```bash
php artisan queue:restart
php artisan optimize
```

Para limpar caches durante troubleshooting:

```bash
php artisan optimize:clear
```

## Permissões

O usuário do PHP-FPM precisa escrever em:

```text
storage/
bootstrap/cache/
```

O diretório público da aplicação deve ser somente:

```text
public/
```

Nunca aponte o servidor web para a raiz do repositório.

## Mercado Pago

Em produção, `MERCADO_PAGO_WEBHOOK_SECRET` é obrigatório. Sem essa variável, o
código aceita webhooks sem validação de assinatura, comportamento útil apenas
para desenvolvimento local.

Configure a URL de webhook no Mercado Pago para:

```text
https://seu-dominio.example/api/webhooks/mercado-pago
```

## Rotação De Segredos

Rotacione imediatamente qualquer chave, token ou senha que tenha sido exposto em:

- terminal compartilhado;
- commits;
- issues ou pull requests;
- prints;
- arquivos de backup;
- `.env` copiado entre máquinas;
- histórico de shell.

Depois da rotação, invalide tokens antigos no provedor e atualize somente o
gerenciador de segredos do ambiente.

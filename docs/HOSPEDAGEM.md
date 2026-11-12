# Hospedagem para a apresentação

O Vue fica na Vercel e a API Laravel com MySQL fica na Railway. As requisições passam pelo domínio da Vercel para que os cookies de sessão funcionem no mesmo domínio. O Docker local continua funcionando de forma independente.

## 1. Criar o projeto na Railway

Entre na Railway com o GitHub. Confira se a conta possui créditos de teste disponíveis antes de criar serviços. Crie um projeto e adicione um banco MySQL. Mantenha o nome do serviço como `MySQL` para usar as referências abaixo.

No mesmo projeto, adicione o repositório do GitHub como outro serviço, chamado `api`. A raiz é a pasta principal do repositório. O arquivo `railway.json` seleciona o `Dockerfile` de produção, executa migrations antes de cada implantação e verifica `/up`.

## 2. Configurar as variáveis da API

Defina estas variáveis no serviço `api`, na Railway. As referências `${{MySQL...}}` apontam para as variáveis do serviço MySQL do mesmo projeto; não são os dados do banco local.

```dotenv
APP_NAME=Mangáverse
APP_ENV=production
APP_DEBUG=false
APP_URL=https://SEU-PROJETO.vercel.app
APP_KEY=CHAVE-GERADA-PARA-A-HOSPEDAGEM
LOG_CHANNEL=stderr
LOG_LEVEL=warning
DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
SANCTUM_STATEFUL_DOMAINS=SEU-PROJETO.vercel.app
TRUSTED_PROXIES=*
CACHE_STORE=database
QUEUE_CONNECTION=sync
ADMIN_SEED_ENABLED=true
SEED_ON_DEPLOY=true
ADMIN_NAME=Mangáverse Admin
ADMIN_EMAIL=SEU-EMAIL-DE-ADMIN
ADMIN_PASSWORD=UMA-SENHA-NOVA-PARA-A-HOSPEDAGEM
```

Substitua os textos em maiúsculas pelos valores reais. Nas caixas individuais de variáveis do painel, os valores não precisam de aspas. A `APP_KEY` da hospedagem deve ser estável entre implantações. Gere uma chave nova no seu computador e copie a saída diretamente para a variável na Railway:

```bash
docker compose exec php php artisan key:generate --show
```

Esse comando apenas mostra uma chave nova; não altera a chave do ambiente local. Não publique a chave, a senha do admin ou credenciais do banco no GitHub.

## 3. Configurar imagens e publicar a API

No serviço `api`, crie um volume com ponto de montagem `/app/storage/app/public`. O serviço MySQL usa seu próprio volume. O volume da API preserva as capas após novas implantações.

Gere um domínio público para a API, usando a porta configurada em `PORT` pela Railway. O FrankenPHP usa essa variável, com padrão 8000 quando ela não está definida. Confira `/up` e `/api/docs` no domínio gerado.

Na primeira implantação, `SEED_ON_DEPLOY=true` cria o catálogo. `ADMIN_SEED_ENABLED=true` permite criar o admin com as credenciais acima. As contas e pedidos de demonstração locais não são criados em produção.

Depois de confirmar que o catálogo e o admin existem, altere `SEED_ON_DEPLOY=false` e `ADMIN_SEED_ENABLED=false`. Isso evita executar os seeders ou redefinir a senha do admin nas próximas implantações.

## 4. Publicar o Vue na Vercel

Na Vercel, importe o mesmo repositório pelo GitHub. Escolha:

- Framework: Vite.
- Root Directory: `front`.
- Build Command: `npm run build`.
- Output Directory: `dist`.

O frontend em produção usa o próprio domínio como endereço da API. Deixe `VITE_API_BASE_URL` sem configurar na Vercel. Antes da publicação final, crie `front/vercel.json` com as regras abaixo, substituindo `SUA-API.up.railway.app` pelo domínio público real da Railway:

```json
{
  "$schema": "https://openapi.vercel.sh/vercel.json",
  "framework": "vite",
  "rewrites": [
    { "source": "/api/:path*", "destination": "https://SUA-API.up.railway.app/api/:path*" },
    { "source": "/sanctum/:path*", "destination": "https://SUA-API.up.railway.app/sanctum/:path*" },
    { "source": "/storage/:path*", "destination": "https://SUA-API.up.railway.app/storage/:path*" },
    { "source": "/up", "destination": "https://SUA-API.up.railway.app/up" },
    { "source": "/(.*)", "destination": "/index.html" }
  ]
}
```

Use o domínio definitivo da Vercel em `APP_URL` e `SANCTUM_STATEFUL_DOMAINS` na Railway. `APP_URL` inclui `https://`; `SANCTUM_STATEFUL_DOMAINS` contém apenas o host. Em `SESSION_DOMAIN`, mantenha o padrão vazio, sem definir domínio da Railway nem `.vercel.app`.

## 5. Conferir antes da apresentação

Abra uma janela anônima no domínio da Vercel. Navegue no catálogo, adicione um produto ao carrinho, registre uma conta e confira a fusão. Cadastre um endereço, finalize a compra e confirme o pagamento simulado. Depois confira os pedidos e faça login com o admin para atualizar o status e enviar uma capa.

Abra também `/api/docs` e `/api/docs/openapi.yaml` pelo domínio da Vercel. Recarregue uma URL de produto ou pedido para verificar o fallback do Vue Router.

Depois da apresentação, confira o consumo dos créditos e remova os serviços quando não forem mais necessários. A versão local e seus dados permanecem separados da hospedagem.

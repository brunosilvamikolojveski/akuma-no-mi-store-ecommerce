# Mangáverse

Projeto de e-commerce de mangás desenvolvido com Laravel 12, MySQL e Vue 3. O Laravel fornece a API; o frontend Vue fica separado em `front/` e consome os mesmos endpoints que poderão ser usados por um app Flutter no futuro.

## Pré-requisitos

- Git
- Docker Engine e Docker Compose v2
- Node.js e npm

## Instalação em um computador novo

Clone o repositório e entre na pasta:

```bash
git clone https://github.com/brunosilvamikolojveski/mangaverse-ecommerce.git
cd mangaverse-ecommerce
```

Crie o arquivo de configuração local:

```bash
cp .env.example .env
```

Antes de iniciar os serviços, abra `.env` e defina uma senha local para a conta administrativa:

```dotenv
ADMIN_NAME=Mangáverse Admin
ADMIN_EMAIL=admin@mangaverse.test
ADMIN_PASSWORD=escolha-uma-senha-local
```

O arquivo `.env` contém configurações locais e não deve ser enviado ao Git.

Construa a imagem e inicie a API e o MySQL:

```bash
docker compose up -d --build
```

A construção da imagem instala as dependências PHP pelo Composer. O volume `php_vendor` mantém essas dependências visíveis para o container mesmo com o código do projeto montado em `/app`.

Gere a chave da aplicação e crie as tabelas e os dados iniciais:

```bash
docker compose exec php php artisan key:generate
docker compose exec php php artisan migrate --seed
```

A API estará disponível em <http://localhost:8000>. O volume `mysql_data` mantém o banco mesmo quando os contêineres são parados.

## Iniciar o frontend Vue

Em outro terminal, instale as dependências e inicie o servidor de desenvolvimento:

```bash
cd front
npm install
npm run dev
```

Abra <http://localhost:5173>. O frontend usa `http://localhost:8000` como endereço padrão da API. Para trocar esse endereço localmente, crie `front/.env.local` com:

```dotenv
VITE_API_BASE_URL=http://localhost:8000
```

O arquivo `.env.local` é local e não deve ser enviado ao Git.

## Verificar a instalação

Confira se os contêineres estão ativos e se a API responde:

```bash
docker compose ps
curl http://localhost:8000/up
curl -H "Accept: application/json" http://localhost:8000/api/mangas
```

O primeiro endpoint verifica a saúde da aplicação. O segundo consulta o catálogo público.

## Conta administrativa

O `AdminUserSeeder` cria a conta usando `ADMIN_NAME`, `ADMIN_EMAIL` e `ADMIN_PASSWORD` do `.env`, somente nos ambientes `local` e `testing`.

Se você já executou `migrate --seed` antes de preencher essas variáveis, execute o seeder novamente:

```bash
docker compose exec php php artisan db:seed --class=AdminUserSeeder
```

## Autenticação da API no Insomnia

A autenticação usa sessão e cookies. Para iniciar uma sessão, faça primeiro `GET http://localhost:8000/sanctum/csrf-cookie` e mantenha o cookie jar ativo. Nas requisições seguintes, envie `Origin: http://localhost:8000`; nas operações que alteram dados, envie também `X-XSRF-TOKEN` com o valor do cookie `XSRF-TOKEN`.

## Comandos úteis

```bash
# Ver os logs dos serviços
docker compose logs -f

# Abrir um terminal no contêiner da API
docker compose exec php bash

# Aplicar novas migrations
docker compose exec php php artisan migrate

# Recriar o banco e os dados iniciais; isso apaga os dados atuais
docker compose exec php php artisan migrate:fresh --seed

# Parar os contêineres mantendo os dados do MySQL
docker compose down
```

Para apagar também o banco persistido, use `docker compose down -v`.

## Estrutura principal

- `app/Models`: entidades e relacionamentos Eloquent
- `app/Http/Controllers`: entrada e saída das requisições
- `app/Http/Requests`: validação dos dados recebidos
- `app/Http/Resources`: formato das respostas JSON
- `app/Services`: regras de negócio
- `database/migrations`: estrutura do banco de dados
- `database/seeders`: papéis e dados fictícios de catálogo
- `routes/api.php`: rotas da API

O seeder de catálogo cria uma editora, duas séries e quatro volumes fictícios. O contêiner de desenvolvimento usa `php artisan serve`; esta configuração é para desenvolvimento local, não para produção.

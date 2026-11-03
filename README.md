# Akuma no Mi Store

E-commerce de mangás desenvolvido como projeto final de curso. A aplicação está em desenvolvimento e usa Laravel no backend, MySQL para persistência e Vite para os assets do frontend.

## Tecnologias

- PHP 8.4 e Laravel 12
- MySQL 8.4
- Docker Compose
- Node.js, npm, Vite e Tailwind CSS

## Pré-requisitos

- Docker Engine com Docker Compose
- Node.js e npm para instalar e compilar os assets do frontend

## Como iniciar

Na raiz do projeto, crie o arquivo de ambiente local e suba os contêineres:

```bash
cp .env.example .env
docker compose up -d --build
```

> [!NOTE]
> O `.env.example` já aponta para o serviço MySQL do Compose (`DB_HOST=mysql`). O arquivo `.env` é local e não deve ser enviado ao Git.

Gere a chave da aplicação e crie as tabelas com os dados iniciais:

```bash
docker compose exec php php artisan key:generate
docker compose exec php php artisan migrate --seed
```

Instale e compile os assets do frontend no computador:

```bash
npm install
npm run build
```

A aplicação fica disponível em <http://localhost:8000>.

## Comandos úteis

```bash
# Acompanhar os logs dos serviços
docker compose logs -f

# Abrir um shell no contêiner PHP
docker compose exec php bash

# Rodar as migrations pendentes
docker compose exec php php artisan migrate

# Recriar todas as tabelas e executar os seeders (apaga os dados atuais)
docker compose exec php php artisan migrate:fresh --seed

# Executar os testes automatizados
docker compose exec php php artisan test

# Parar os contêineres sem apagar o banco
docker compose down
```

Para apagar também o volume persistente do MySQL, use `docker compose down -v`. Isso remove os dados do banco.

## Estrutura do domínio

O esquema atual contempla:

- Usuários e papéis (`users`, `roles`)
- Editoras, séries, autores e categorias
- Volumes de mangá como produtos, com preço, estoque e ISBN
- Endereços salvos dos usuários
- Carrinhos e itens do carrinho
- Pedidos, itens do pedido e pagamentos

Autores e categorias podem se relacionar com várias séries. Cada série pertence a uma editora e pode ter vários volumes. Os pedidos guardam uma cópia dos dados de envio e dos itens para preservar o histórico.

As migrations ficam em `database/migrations`, os modelos Eloquent em `app/Models` e os dados iniciais em `database/seeders`.

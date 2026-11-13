# Mangáverse

Projeto de e-commerce de mangás desenvolvido com Laravel 12, MySQL e Vue 3. O Laravel fornece a API; o frontend Vue fica separado em `front/` e consome os mesmos endpoints que poderão ser usados por um app Flutter no futuro.

## Demonstração online

- [Loja Mangáverse](https://mangaverse-ecommerce.vercel.app/)
- [Documentação interativa da API](https://mangaverse-ecommerce.vercel.app/api/docs)
- [Especificação OpenAPI](https://mangaverse-ecommerce.vercel.app/api/docs/openapi.yaml)

## Pré-requisitos

Para a publicação na Vercel e na Railway, siga [o guia de hospedagem](docs/HOSPEDAGEM.md).

- Git
- Docker Engine e Docker Compose v2
- Node.js e npm são opcionais, usados apenas para desenvolver o Vue fora do Docker.

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
ADMIN_NAME="Mangáverse Admin"
ADMIN_EMAIL=admin@mangaverse.test
ADMIN_PASSWORD=escolha-uma-senha-local
```

O arquivo `.env` contém configurações locais e não deve ser enviado ao Git.

Construa as imagens e inicie o frontend, a API, o proxy e o MySQL:

```bash
docker compose up -d --build
```

A construção da imagem instala as dependências PHP pelo Composer. O volume `php_vendor` mantém essas dependências visíveis para o container mesmo com o código do projeto montado em `/app`.

Gere a chave da aplicação e crie as tabelas e os dados iniciais:

```bash
docker compose exec php php artisan key:generate
docker compose exec php php artisan migrate --seed
```

A loja estará disponível em <http://localhost>, a documentação em <http://localhost/api/docs> e a especificação em <http://localhost/api/docs/openapi.yaml>. A API também está exposta em <http://localhost:8000> para testes diretos. O volume `mysql_data` mantém o banco mesmo quando os contêineres são parados.

O Nginx da pasta `proxy/` encaminha `/api/`, `/sanctum/`, `/storage/` e `/up` para o backend FrankenPHP. As outras URLs vão para o frontend Vue. Os uploads ficam em `storage/app/public`, montado no container, e sobrevivem à sua recriação. Não é necessário executar `artisan serve`.

Não use `docker compose down -v` para atualizar o projeto: esse comando apaga o volume do banco. Para aplicar uma atualização, execute `docker compose up -d --build` e depois `docker compose exec php php artisan migrate`.

## Desenvolver o frontend Vue fora do Docker (opcional)

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

No frontend servido pelo Docker, a API usa o mesmo host do navegador. Para alterar isso, defina `VITE_API_BASE_URL` no `.env` da raiz e reconstrua a imagem com `docker compose up -d --build frontend`. Essa variável é aplicada durante o build do Vue.

## Verificar a instalação

Confira se os contêineres estão ativos e se a API responde:

```bash
docker compose ps
curl http://localhost:8000/up
curl -H "Accept: application/json" http://localhost:8000/api/mangas
```

O primeiro endpoint verifica a saúde da aplicação. O segundo consulta o catálogo público.

## Conta administrativa

O `AdminUserSeeder` cria a conta usando `ADMIN_NAME`, `ADMIN_EMAIL` e `ADMIN_PASSWORD` do ambiente. Funciona em `local` e `testing`; em produção, exige a habilitação explícita de `ADMIN_SEED_ENABLED=true`, conforme o guia de hospedagem.

Se você já executou `migrate --seed` antes de preencher essas variáveis, execute o seeder novamente:

```bash
docker compose exec php php artisan db:seed --class=AdminUserSeeder
```

## Contas de demonstração

O `DemoCustomerSeeder` cria três contas de cliente em ambientes `local` e `testing`:

| E-mail | Senha |
| --- | --- |
| cliente1@mangaverse.test | cliente123 |
| cliente2@mangaverse.test | cliente123 |
| cliente3@mangaverse.test | cliente123 |

Essas credenciais são apenas para desenvolvimento local.

O `DemoShoppingSeeder` acrescenta um endereço, um carrinho preenchido para `cliente1@mangaverse.test`, um carrinho de visitante e cinco pedidos: aguardando pagamento, pago, enviado, entregue e cancelado. O seeder desconta o estoque dos pedidos que não estão cancelados. Os pedidos são identificados por referências de demonstração para não serem duplicados em novas execuções. Cada execução cria um carrinho de visitante de demonstração com identificador aleatório; ele serve para inspeção do banco. O navegador cria seu próprio carrinho de visitante ao acessar a loja.

## Regras de compra

- O carrinho usa o preço atual do catálogo. Adicionar o mesmo mangá soma a quantidade; atualizar a quantidade para zero remove a linha.
- No login ou registro, produtos exclusivos do carrinho visitante são copiados para o carrinho da conta. Para produtos presentes nos dois, as quantidades são somadas e limitadas ao estoque atual. Produtos inativos ou sem estoque não são transferidos. A interface avisa sobre ajustes. O carrinho visitante é descartado e a sessão é regenerada.
- O checkout exige um endereço da própria conta. O frete é gratuito em todos os pedidos. Preços, nomes e endereço são congelados no pedido; o total é calculado no servidor, sem aceitar valores do frontend.
- Pedido, itens, pagamento, baixa de estoque e limpeza do carrinho são gravados em uma transação. Falhas de disponibilidade impedem a compra inteira.
- Somente o dono confirma o pagamento simulado. Um pedido só pode ser pago enquanto aguarda pagamento; nova tentativa retorna `409`. Nenhum dado de cartão é solicitado.
- O cliente pode cancelar o próprio pedido enquanto aguarda pagamento; o estoque é devolvido. O admin pode cancelar pedidos pendentes ou avançar de pago para enviado e de enviado para entregue.
- Desativar um mangá preserva os pedidos antigos. Excluir uma categoria remove seus vínculos com séries, preservando séries e volumes. Uma série com volumes não pode ser excluída.

## Documentação interativa da API

Abra <http://localhost/api/docs>. Os arquivos ficam em `public/api/docs/` e são servidos estaticamente, sem Blade ou rotas Laravel para a documentação. Os assets do Swagger UI são locais.

Para testar uma operação da conta, expanda `POST /api/auth/login`, clique em **Try it out** e execute com uma das contas acima. Para o admin, use as credenciais do seu `.env`. Os cookies ficam no navegador; antes de cada operação de escrita, a UI obtém o cookie CSRF e envia `X-XSRF-TOKEN` automaticamente. Não há token Bearer para copiar.

Os exemplos de entrada usam IDs ilustrativos; consulte as listagens e substitua pelos IDs do seu banco. Os exemplos marcados como inválidos servem para observar erros `422`. As operações de escrita alteram os dados do ambiente em uso, incluindo estoque e pedidos.

## Testes

```bash
docker compose exec php php artisan test
```

Os testes usam SQLite em memória e não apagam o MySQL de desenvolvimento. Verificam remoção por quantidade zero, unicidade de carrinho, pagamento, estoque, fusão e dados dos seeders.

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
- `database/seeders`: papéis, 30 volumes de mangás e contas locais de demonstração
- `routes/api.php`: rotas da API

O seeder de catálogo cria 10 volumes de cada série: One Piece, Naruto e Death Note. Os nomes e autores são reais; preços e estoques são dados de demonstração. Há um volume sem estoque e outro inativo para demonstrar as regras do catálogo. O Compose usa `Dockerfile.dev`; o `Dockerfile` da raiz instala somente as dependências PHP de produção. Uma implantação pública ainda exige configurações de domínio, HTTPS, segredos e persistência adequadas ao servidor.

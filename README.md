# Controle de Estoque e Vendas - API ERP

## Descrição
API RESTful desenvolvida em Laravel para controle de estoque e vendas, preparada para alta escalabilidade, concorrência, cache, filas e arquitetura em camadas. Suporte a ambiente Docker/Sail, banco MySQL em produção/desenvolvimento e SQLite para testes automatizados.

---

## Instalação

1. Clone o repositório:
   ```bash
   git clone <url-do-repositorio>
   cd test-api
   ```
2. Instale as dependências:
   ```bash
   composer install
   ```
3. Configure o ambiente:
   - Copie `.env.example` para `.env` e ajuste as variáveis de banco e fila.
   - Para Docker/Sail:
     ```bash
     ./vendor/bin/sail up -d
     ```
4. Execute as migrations e seeders:
   ```bash
   ./vendor/bin/sail artisan migrate:fresh --seed
   ```

---

## Comandos Essenciais
- Rodar testes:
  ```bash
  ./vendor/bin/sail artisan test
  ```
- Rodar worker de fila:
  ```bash
  ./vendor/bin/sail artisan queue:work redis
  ```
- Popular banco com dados realistas:
  ```bash
  ./vendor/bin/sail artisan db:seed
  ```

---

## Como Usar a API

### Autenticação
A API utiliza Laravel Sanctum. Para obter um token:
```bash
curl -X POST http://localhost/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com", "password": "password"}'
```
Use o token nas requisições:
```bash
-H "Authorization: Bearer SEU_TOKEN_AQUI"
```

### Endpoints Principais
- `POST /api/v1/inventory` - Registrar entrada de produtos
- `GET /api/v1/inventory` - Consultar estoque
- `POST /api/v1/sales` - Registrar venda
- `GET /api/v1/sales/{id}` - Detalhes da venda
- `GET /api/v1/reports/sales` - Relatório de vendas

#### Exemplo de Requisição
```bash
curl -X POST http://localhost/api/v1/sales \
  -H "Authorization: Bearer SEU_TOKEN_AQUI" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "Cliente Teste",
    "items": [
      { "product_id": 1, "quantity": 2, "unit_price": 20.00 }
    ],
    "transaction_hash": "hash-unico-123"
  }'
```

---

## Regras de Negócio
- Validação automática via DTO
- Idempotência por `transaction_hash`
- Processamento assíncrono de vendas
- Controle de concorrência via transação
- Cache para consultas de estoque
- Logs detalhados
- Eventos de negócio (ex: SaleFinalized)

---

## Modelagem do Banco de Dados (ER)
- Tabelas principais: `products`, `inventory_levels`, `inventory_movements`, `sales`, `sale_items`, `users`
- Indices:
  - `products.sku` (único): Busca rápida por SKU
  - `inventory_levels.product_id`: Consulta eficiente de estoque
  - `sales.transaction_hash` (único): Idempotência
  - `sale_items.sale_id`, `sale_items.product_id`: Relacionamento e performance em relatórios
- **MySQL**: Usado em produção/desenvolvimento pela robustez, escalabilidade e suporte a índices avançados.
- **SQLite**: Usado em testes pela leveza e velocidade, sem afetar dados reais.

---

## Estrutura de Pastas
- `app/Http/Controllers`: Controllers das rotas
- `app/DTOs`: Validação e padronização de payloads
- `app/Services`: Lógica de negócio
- `app/Repositories`: Abstração do acesso a dados
- `app/Models`: Modelos Eloquent
- `app/Events`: Eventos de negócio
- `app/Jobs`: Processamento assíncrono
- `app/Utils`: Helpers e utilitários
- `database/factories`: Factories para geração de dados
- `database/seeders`: Seeders para popular o banco
- `tests/Feature`: Testes de integração
- `tests/Unit`: Testes de unidade

**Motivo da Estrutura:**
- Separação clara de responsabilidades
- Facilidade de manutenção e testes
- Escalabilidade para novos módulos

---

## Exemplo de Requisições
(Ver exemplos acima e na documentação dos endpoints)

---

## Arquivo Postman
Baixe o arquivo de coleção para testar todos os endpoints:
[Download TestInvetory.postman_collection.json](./TestInvetory.postman_collection.json)

---

## Observações Finais
- Testes automatizados garantem integridade das regras
- Projeto pronto para evoluir e integrar novos módulos
- Performance otimizada para grande volume de dados

---

## Endpoints Implementados
- `POST /api/v1/inventory` - Registrar entrada de produtos no estoque
- `GET /api/v1/inventory` - Obter situação atual do estoque (otimizado, cacheado, com valores totais e lucro projetado)
- `POST /api/v1/sales` - Registrar venda de produtos (idempotente, processamento assíncrono, validação automática via DTO)
- `GET /api/v1/sales/{id}` - Obter detalhes completos de uma venda específica (itens, status, valores, idempotência)
- `GET /api/v1/reports/sales` - Gerar relatório de vendas com filtros (data, status, cliente)

## Endpoint de Vendas

### `POST /api/v1/sales`
Registra uma venda de produtos. O payload é validado automaticamente via DTO (`SalesEntryDTO`), e o processamento é feito de forma assíncrona via fila Redis.

#### Payload
```json
{
  "customer_name": "Cliente Teste",
  "items": [
    { "product_id": 1, "quantity": 2, "unit_price": 20.00 }
  ],
  "transaction_hash": "hash-unico-123" // Opcional, recomendado para integrações
}
```
- `customer_name`: Nome do cliente (opcional)
- `items`: Array de itens, cada um com `product_id`, `quantity`, `unit_price`
- `transaction_hash`: Hash único para idempotência (opcional, recomendado)

#### Respostas
- **202 Accepted**: Venda recebida e será processada
- **200 OK**: Venda já registrada para o hash informado
- **422 Unprocessable Entity**: Payload inválido
- **500 Internal Server Error**: Erro inesperado

#### Regras de Negócio
- Validação automática do payload via DTO
- Idempotência garantida pelo campo `transaction_hash`
- Processamento assíncrono via Job na fila Redis
- Atualização de estoque, registro de itens e status da venda feitos pelo Job
- Logs detalhados para auditoria

#### Exemplo de uso
```bash
curl -X POST http://localhost/api/v1/sales \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "Cliente Teste",
    "items": [
      { "product_id": 1, "quantity": 2, "unit_price": 20.00 }
    ],
    "transaction_hash": "hash-unico-123"
  }'
```

### `GET /api/v1/sales/{id}`
Retorna os detalhes completos de uma venda específica, incluindo todos os itens, valores, status e informações do produto.

#### Resposta
```json
{
  "sale_id": 1,
  "customer_name": "Cliente Teste",
  "transaction_hash": "hash-unico-123",
  "total_amount": 40.00,
  "total_profit": 20.00,
  "status": "COMPLETED",
  "created_at": "2025-11-27T10:00:00Z",
  "items": [
    {
      "product_id": 1,
      "product_name": "Produto Teste",
      "quantity": 2,
      "unit_price": 20.00,
      "cost_price": 10.00,
      "profit": 20.00
    }
  ]
}
```

#### Regras de Negócio
- Retorna 404 se a venda não existir
- Eager loading dos itens e produtos
- Estrutura padronizada para integrações e relatórios

#### Testes automatizados
- Testa retorno completo dos dados da venda
- Testa resposta 404 para venda inexistente

### `GET /api/v1/reports/sales`
Gera relatório de vendas com filtros opcionais:
- `start_date` (YYYY-MM-DD)
- `end_date` (YYYY-MM-DD)
- `status` (COMPLETED, FAILED, PENDING)
- `customer_name` (busca parcial)

#### Exemplo de uso
```bash
curl -X GET "http://localhost/api/v1/reports/sales?status=COMPLETED&start_date=2025-11-01&end_date=2025-11-27"
```

#### Resposta
Array de vendas, cada uma com todos os dados, itens e produtos.

#### Testes automatizados
- Testa retorno de todas as vendas
- Testa filtro por status
- Testa filtro por cliente
- Testa filtro por data

## Funcionalidades e Regras de Negócio
- Registro de entrada de produtos no estoque
- Validação de quantidade positiva
- Atualização e histórico de movimentações
- Controle de concorrência via transação
- Cache invalidado após movimentação
- Consulta de estoque otimizada e cacheada
- Cálculo de valor total e lucro projetado do estoque
- Testes automatizados contemplam cenários de sucesso, falha por quantidade negativa, produto inexistente e consulta de estoque

## Arquitetura
- **Repository Pattern:** Abstração do acesso a dados
- **Service Layer:** Lógica de negócio centralizada
- **DTOs:** Padronização de entrada/saída
- **Transações:** Garantia de integridade
- **Factories/Seeders:** Simulação de ambiente realista
- **Eager Loading:** Evita N+1 queries nas consultas de estoque
- **Cache:** Otimiza consultas frequentes

## Testes
- Automatizados via PHPUnit
- Rodam em SQLite em memória (configuração em `phpunit.xml`)
- Não afetam o banco de produção/desenvolvimento
- Testes de unidade e integração para entrada e consulta de estoque

## Fila de processamento de vendas

O projeto utiliza o Redis como driver de fila para processamento assíncrono das vendas. O Redis já está configurado via Sail e também é utilizado para cache.

### Como funciona o fluxo de vendas
- Ao receber uma requisição de venda (`POST /api/v1/sales`), o sistema registra a venda com status `PENDING` e dispara um Job (`ProcessSaleJob`) para processamento assíncrono.
- O Job é colocado na fila Redis e processado por um worker, garantindo performance e escalabilidade.
- O processamento inclui validação, atualização de estoque com lock pessimista, registro dos itens, atualização do status da venda e disparo de eventos para auditoria.
- A resposta da API é imediata, informando o `sale_id` e o `transaction_hash`.

### Como rodar o worker de fila
Execute o comando abaixo para iniciar o processamento dos jobs:

```
php artisan queue:work redis
```

### Configuração
- No arquivo `.env`, garanta que está definido:
  ```
  QUEUE_CONNECTION=redis
  ```
- O Redis já está ativo via Sail.

### Vantagens
- Alta performance e escalabilidade.
- Processamento seguro e concorrente.
- Integração nativa com Laravel.

## Idempotência e controle de duplicidade de vendas

A API de vendas implementa idempotência utilizando o campo `transaction_hash`:
- Se o client enviar o `transaction_hash` no payload, a API verifica se já existe uma venda com esse hash. Se existir, retorna a venda existente (status 200), evitando duplicidade.
- Se não for enviado, o backend gera um hash único baseado nos dados da venda.
- Recomenda-se que integrações externas sempre enviem o hash para garantir idempotência.
- O hash é gerado e validado pela classe utilitária `TransactionHashGenerator`.

### Exemplo de payload com idempotência
```json
{
  "customer_name": "Cliente Teste",
  "items": [
    { "product_id": 1, "quantity": 2, "unit_price": 20.00 }
  ],
  "transaction_hash": "hash-unico-123"
}
```

### Testes automatizados
- Testes unitários garantem que o hash é consistente para os mesmos dados e diferente para dados distintos.
- Testes de integração garantem que requisições repetidas com o mesmo hash não criam vendas duplicadas.

### Recomendações
- Sempre envie o `transaction_hash` em integrações entre sistemas para evitar duplicidade.
- O backend garante idempotência mesmo se o hash não for enviado, mas o controle é mais robusto se o client gerar e enviar.

## Como rodar
1. Suba o ambiente com Sail:
   ```bash
   ./vendor/bin/sail up -d
   ```
2. Execute as migrations e seeders:
   ```bash
   ./vendor/bin/sail artisan migrate:fresh --seed
   ```
3. Rode os testes:
   ```bash
   ./vendor/bin/sail artisan test
   ```
4. Teste a API via Postman ou similar:
   - Endpoint: `POST /api/v1/inventory`
   - Endpoint: `GET /api/v1/inventory`

## Observações
- O código está compatível tanto com MySQL quanto SQLite.
- Os testes automatizados garantem integridade das principais regras de negócio.
- O projeto está pronto para evoluir para os demais endpoints e regras solicitadas.

## Eventos de Negócio

- **SaleFinalized**: Evento disparado automaticamente sempre que uma venda é finalizada (status COMPLETED). Pode ser utilizado para integrações, notificações ou auditoria. Implementado em `app/Events/SaleFinalized.php` e disparado pelo `SalesService`.

## Popular Banco de Dados com Dados Realistas

Para simular ambiente de produção/teste com grande volume de dados:

- **Seeders e Factories eficientes** geram:
  - 100 produtos diferentes
  - Estoque inicial para todos os produtos
  - 10.000 vendas históricas distribuídas nos últimos 12 meses
  - Média de 2-5 itens por venda (20.000 a 50.000 registros)

### Como popular o banco

1. Certifique-se que o banco está migrado:
   ```bash
   php artisan migrate
   ```
2. Execute o seeder principal:
   ```bash
   php artisan db:seed
   ```
   Isso irá rodar o `RealisticDataSeeder` automaticamente.

> **Dica:** O seeder foi projetado para ser eficiente, mas pode demorar alguns minutos dependendo do ambiente. Para testar com menos dados, ajuste os valores em `database/seeders/RealisticDataSeeder.php`.

## Factories e Seeders
- `ProductFactory`, `SaleFactory`, `SaleItemFactory` para geração rápida e variada de dados
- `RealisticDataSeeder` para simulação de ambiente realista

## Observações de Performance
- Queries e relatórios otimizados para grande volume
- Uso de cache, eager loading e paginação recomendados para produção
- Testes automatizados simulam cenários de concorrência e volume

## Como rodar testes
```bash
php artisan test
```

## Como rodar em ambiente Docker/Sail
```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate --seed
```

---

**Últimas implementações:**
- Evento `SaleFinalized` disparado ao finalizar venda
- Seeders/factories para dados realistas
- Helper de datas para filtros robustos
- Ajuste de logs e configuração de logging

## Autenticação via API

A API utiliza autenticação via token com Laravel Sanctum.

### Como habilitar autenticação
1. Instale o pacote Sanctum:
   ```bash
   composer require laravel/sanctum
   ```
2. Publique a configuração e rode as migrations:
   ```bash
   php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
   php artisan migrate
   ```
3. O trait `HasApiTokens` já está no model `User`.
4. As rotas principais estão protegidas por autenticação (`auth:sanctum`).

### Como obter um token
Faça login via:
```bash
curl -X POST http://localhost/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com", "password": "password"}'
```
A resposta será:
```json
{ "token": "..." }
```
Use esse token no header das requisições:
```bash
-H "Authorization: Bearer SEU_TOKEN_AQUI"
```

### Exemplo de chamada autenticada
```bash
curl -X GET http://localhost/api/v1/inventory \
  -H "Authorization: Bearer SEU_TOKEN_AQUI"
```

---
````

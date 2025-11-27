# Controle de Estoque e Vendas - API ERP

API RESTful desenvolvida em Laravel para controle de estoque e vendas, preparada para alta escalabilidade, concorrência, cache, filas e arquitetura em camadas. Suporte a ambiente Docker/Sail, banco MySQL em produção/desenvolvimento e SQLite para testes automatizados.

## Instalação

1. Clone o repositório:
   ```bash
   git clone https://github.com/apleduardo/inventory_sales_api
   cd inventory_sales_api
   ```
2. Instale as dependências:
   ```bash
   ./vendor/bin/sail composer install
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
- Executar tarefa agendada para arquivar estoques antigos:
  ```bash
  ./vendor/bin/sail artisan inventory:archive-old
  ```

---

## Como Usar a API

### Autenticação
A API utiliza Laravel Sanctum. Para acessar os endpoints protegidos, obtenha um token realizando login:
```bash
curl -X POST http://localhost/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com", "password": "password"}'
```
A resposta será:
```json
{ "token": "..." }
```
Utilize o token nas requisições:
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

## Modelagem do Banco de Dados (ER)

A modelagem é projetada para normalização e performance de escrita e leitura, especialmente para o controle de estoque e relatórios.

### Estrutura de tabelas: 

| Tabela | Indices  | Propósito e Justificativa|
|-------|-------|-------| 
| `products` | `id (PK), sku (Unique Index),` |`Informações de produtos. O índice em sku é crucial para buscas rápidas.` | 
| `inventory_movements` | `id (PK), product_id (Index), movement_type (Index), created_at (Index)` | `Histórico de Movimentação: Registra toda entrada/saída. movement_type ('IN'/'OUT') indexado para consultas .` |
| `inventory_levels` | `product_id (PK, Unique Index), updated_at (Index), archived (Index)` |`Situação Atual de Estoque: Ajuda na performance do (GET /api/inventory). Isola a atualização da escrita de histórico. O índice em archived garante consultas rápidas apenas para estoques ativos.` | 
| `sales` | `id (PK), status (Index), transaction_hash (Unique Index, para idempotência), created_at (Index)` |`Registro mestre de vendas. O campo status ('PENDING', 'COMPLETED', 'FAILED') é essencial para o fluxo assíncrono.` | 
| `sale_items` | `id (PK), sale_id (Index), product_id (Index)` | `Detalhes da venda. O índice composto (sale_id, product_id) e índices separados em sale_id e product_id são cruciais para relatórios e consultas rápidas de vendas.` |

- **MySQL**: RDBMS com propriedades ACID. A escolha é pela funcionalidade de relacionamento e alta consistência devido as transações. Suporte a índices avançados.
- **SQLite**: Usado em testes pela leveza e velocidade, sem afetar dados reais.

```
erDiagram
    products ||--o{ inventory_movements : has
    products ||--|| inventory_levels : has
    sales ||--o{ sale_items : has
    products ||--o{ sale_items : includes

    products {
        int id PK
        varchar sku UK "Chave p/ busca rápida"
        varchar name
        decimal cost_price
        decimal sale_price
    }

    inventory_movements {
        int id PK
        int product_id FK
        enum movement_type "IN, OUT"
        int quantity
        decimal cost_price
        datetime created_at
    }

    inventory_levels {
        int product_id PK FK "Chave p/ concorrência e leitura"
        int quantity "Valor atual"
        decimal total_cost
        datetime updated_at "Para tarefa agendada"
    }

    sales {
        int id PK
        varchar customer_name
        decimal total_amount
        decimal total_profit
        enum status "PENDING, COMPLETED, FAILED"
        varchar transaction_hash UK "Garantir idempotência"
        datetime created_at
    }

    sale_items {
        int id PK
        int sale_id FK
        int product_id FK
        int quantity
        decimal unit_price
        decimal cost_price
        decimal profit
    }
```
---

## Camadas e Responsabilidades
- `Controllers`: Receber requisição HTTP, validar (usando Request Objects ou DTOs), chamar o Service apropriado e retornar a resposta. Não contém lógica de negócio.
- `DTOs`: Tipagem forte e validação dos dados de entrada antes de passar para o Service. Garante a forma correta dos dados.
- `Services`: Lógica de Negócio Central: Orquestra a execução das regras de negócio, manipula transações, dispara eventos e utiliza os Repositories. Ex: SalesService calcula lucro, verifica estoque e chama o SaleRepository para persistir.
- `Repositories`: Abstrai o acesso a dados. Responsável por interagir com os modelos Eloquent, aplicar otimizações N+1, gerenciar cache e lidar com bloqueios (concorrência).
- `app/Models`: Modelos Eloquent
- `app/Events`: Eventos de negócio
- `app/Jobs`: Execução assíncrona de tarefas demoradas ou que podem falhar temporariamente (e serem re-tentadas), como o processamento completo de uma venda.

**Motivo da Estrutura:**
 Arquitetura Controller-Service-Repository (CSR), que é um padrão robusto para modularização, isolamento de lógica de negócio e facilidade de testes.

---

## Arquivo Postman
Baixe o arquivo de coleção para testar todos os endpoints:
[Download TestInvetory.postman_collection.json](./TestInvetory.postman_collection.json)

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
- Tarefa agendada: Estoques não atualizados há mais de 90 dias são sinalizados automaticamente para arquivamento, garantindo limpeza e performance do banco.

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
./vendor/bin/sail artisan queue:work redis
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

## Arquivamento Automático de Estoques Antigos

O sistema possui uma funcionalidade para arquivar ou sinalizar registros de estoque que não foram atualizados nos últimos 90 dias. Isso garante que relatórios e consultas considerem apenas estoques ativos e evita acúmulo de dados obsoletos.

### Como usar
- Para executar manualmente:
  ```bash
  ./vendor/bin/sail artisan inventory:archive-old
  ```
  O comando irá sinalizar como arquivados todos os registros de estoque com `updated_at` anterior a 90 dias.
- Para agendar automaticamente:
  O comando já está configurado para rodar diariamente pelo scheduler do Laravel. Basta garantir que o scheduler está ativo:
  ```bash
  ./vendor/bin/sail artisan schedule:work
  ```

### Consultas e relatórios
- Todas as consultas de estoque e vendas já desconsideram registros arquivados.
- O campo `archived` pode ser usado para auditoria ou reativação futura.

---

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
   ./vendor/bin/sail artisan migrate
   ```
2. Execute o seeder principal:
   ```bash
   ./vendor/bin/sail artisan db:seed
   ```
   Isso irá rodar o `RealisticDataSeeder` automaticamente.

> **Dica:** O seeder foi projetado para ser eficiente, mas pode demorar alguns minutos dependendo do ambiente. Para testar com menos dados, ajuste os valores em `database/seeders/RealisticDataSeeder.php`.

---

## Autenticação via API

A API utiliza autenticação via token com Laravel Sanctum. Veja o exemplo de login e uso do token na seção [Como Usar a API](#como-usar-a-api).

---

## Observações Finais
- Testes automatizados garantem integridade das regras e simulam cenários de concorrência 
- Projeto pronto para evoluir e integrar novos módulos
- Queries e relatórios otimizados para grande volume

## Melhorias Futuras
 - Desacoplamento do processamento da fila para uma nova aplicação, isso garantiria a escalabilidade de forma apartada.
 - Paginação do relatorio.
 - No projeto foi usado um modelo de autenticação basica, para o futuro seria interessante em pensar em um modelo mais robusto como protocolo OAUTH ou JWT.
 - Adicionar verificação de autorização para liberar rotas especificas de acordo com a permissão dos usuarios.
 - Ratelimit para evitar DDOS.
 - Alteração do sitema de gerenciamento de fila para o RabbitMQ, isso traz a vantagem de confiabilidade diminuindo o risco da perda de mensagens.
 - Para escalar o banco de dados adicionaria replicas de leitura e escrita e particionamento dos dados de vendas.
 - Autoscaling grouping para gerenciamento de escalabildade.
 - Loadbalance para balancear a carga entre as instancias.
 - Implementada tarefa agendada para arquivar/sinalizar estoques não atualizados há mais de 90 dias.
---



# Controle de Estoque e Vendas - API ERP

## Visão Geral
API REST desenvolvida em Laravel para controle de estoque e vendas, seguindo requisitos de escalabilidade, concorrência, cache, filas e arquitetura em camadas. O projeto está preparado para rodar em ambiente Docker/Sail, com banco MySQL em produção/desenvolvimento e SQLite em testes automatizados.

## Estrutura do Projeto
- **Laravel 10+ / PHP 8.1+**
- **Banco de dados:** MySQL (produção/dev via Sail), SQLite (testes)
- **Camadas:**
  - Controllers
  - DTOs
  - Services
  - Repositories
  - Models
- **Testes:** PHPUnit (Feature e Unit)
- **Factories/Seeders:** Para geração de dados realistas
- **Cache:** Implementado para consultas de estoque
- **Filas:** Preparado para jobs assíncronos
- **Logs:** Detalhados para auditoria e debugging

## Endpoints Implementados
- `POST /api/v1/inventory` - Registrar entrada de produtos no estoque
- `GET /api/v1/inventory` - Obter situação atual do estoque (otimizado, cacheado, com valores totais e lucro projetado)
- `POST /api/v1/sales` - Registrar venda de produtos (idempotente, processamento assíncrono, validação automática via DTO)

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

---

**Para dúvidas ou evolução do projeto, consulte este README ou os comentários nos arquivos principais.**

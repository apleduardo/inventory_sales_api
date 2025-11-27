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
- (Outros endpoints em desenvolvimento conforme escopo)

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

# Remoção do Campo daily_limit

## Resumo

O campo `daily_limit`, `daily_used` e `daily_reset_at` foram removidos do sistema, pois não são mais necessários. O controle de limites diários agora é feito apenas para operações de **cashout** através do campo `cashout_daily_limit`.

## Motivação

- Limite diário genérico não era mais usado no sistema
- Controle de limite passou a ser específico apenas para operações de saque (cashout)
- Simplificação da estrutura do banco de dados
- Remoção de código não utilizado

## Alterações Realizadas

### 1. Banco de Dados

**Tabela `sellers`**
- ❌ Removido: `daily_limit`
- ❌ Removido: `daily_used`
- ❌ Removido: `daily_reset_at`
- ✅ Mantido: `cashout_daily_limit` (específico para cashout)
- ✅ Mantido: `cashout_daily_used` (específico para cashout)
- ✅ Mantido: `cashout_daily_reset_at` (específico para cashout)

**Tabela `acquirers`**
- ❌ Removido: `daily_limit`
- ❌ Removido: `daily_used`
- ❌ Removido: `daily_reset_at`

### 2. Backend (PHP)

**Arquivo: `app/controllers/web/AdminController.php`**

Removido:
- Variável `$dailyLimit` na criação de acquirers
- Variável `$dailyLimit` na atualização de acquirers
- Campo `daily_limit` do array de dados de acquirers
- Campo `daily_used` do array de dados de acquirers
- Campo `daily_reset_at` do array de dados
- Função `resetAcquirerLimit()` (não mais necessária)
- Função `resetAcquirerAccountLimit()` (não mais necessária)
- Campo `daily_reset_at` na aprovação de sellers

### 3. Frontend (Views)

**Arquivo: `app/views/admin/seller-details.php`**

Removido:
- Card completo "Limites" que exibia:
  - Limite Diário
  - Progresso de uso (barra)
  - Valor utilizado

**Arquivo: `app/views/seller/profile.php`**

Removido:
- Card completo "Limites" que exibia:
  - Limite Diário
  - Progresso de uso (barra)
  - Valor usado

**Arquivo: `app/views/admin/acquirers.php`**

Removido:
- Seção "Limite Diário" na listagem de acquirers
- Barra de progresso de uso
- Campo "Limite Diário (R$)" no formulário de criação/edição
- Referência `acquirer_daily_limit` no JavaScript

## Arquivos Criados

### 1. Migration SQL
- **Arquivo:** `sql/remove_daily_limit.sql`
- **Descrição:** Script SQL para remover os campos do banco de dados
- **Uso:** Pode ser executado diretamente no MySQL ou via script PHP

### 2. Script de Aplicação
- **Arquivo:** `apply_remove_daily_limit_migration.php`
- **Descrição:** Script PHP interativo para aplicar a migration com segurança
- **Recursos:**
  - Verifica campos existentes antes de remover
  - Solicita confirmação antes de executar
  - Usa transação para segurança
  - Mostra resultado detalhado

## Como Aplicar a Migration

### Opção 1: Script PHP (Recomendado)

```bash
php apply_remove_daily_limit_migration.php
```

O script irá:
1. Verificar quais campos existem
2. Solicitar confirmação
3. Remover os campos com segurança
4. Mostrar resultado

### Opção 2: SQL Direto

```bash
mysql -u [usuario] -p [banco] < sql/remove_daily_limit.sql
```

## Impacto no Sistema

### Funcionalidades Removidas

1. **Limite diário genérico de sellers**
   - Antes: Sellers tinham um limite diário geral
   - Agora: Apenas cashout tem limite diário específico

2. **Limite diário de acquirers**
   - Antes: Acquirers tinham limite de volume diário
   - Agora: Sem limite de volume em acquirers

3. **Botão "Resetar Limite" de acquirers**
   - Removido da interface administrativa

### Funcionalidades Mantidas

1. **Limite diário de cashout**
   - Campo: `cashout_daily_limit`
   - Totalmente funcional e independente

2. **Limites por transação**
   - `min_cashin_amount` / `max_cashin_amount`
   - `min_cashout_amount` / `max_cashout_amount`
   - Todos mantidos e funcionando

3. **Controle de saldo**
   - Campo `balance` mantido em sellers
   - Continua funcionando normalmente

## Verificação Pós-Migration

Após aplicar a migration, execute os seguintes testes:

### 1. Verificar Estrutura do Banco

```sql
-- Verificar sellers (deve ter apenas cashout_daily_*)
SELECT COLUMN_NAME
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'sellers'
AND TABLE_SCHEMA = DATABASE()
AND COLUMN_NAME LIKE '%daily%';

-- Resultado esperado:
-- cashout_daily_limit
-- cashout_daily_used
-- cashout_daily_reset_at

-- Verificar acquirers (não deve ter nenhum campo daily)
SELECT COLUMN_NAME
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'acquirers'
AND TABLE_SCHEMA = DATABASE()
AND COLUMN_NAME LIKE '%daily%';

-- Resultado esperado: (vazio)
```

### 2. Testar Funcionalidades

1. **Criar transação de cash-in**
   - Deve funcionar normalmente
   - Não deve verificar limite diário

2. **Criar transação de cashout**
   - Deve verificar `cashout_daily_limit`
   - Deve atualizar `cashout_daily_used`

3. **Visualizar detalhes do seller**
   - Não deve mostrar "Limite Diário" genérico
   - Deve mostrar apenas limites de cashout (se configurados)

4. **Gerenciar acquirers**
   - Não deve mostrar limite diário
   - Formulário não deve ter campo de limite

## Rollback

Se precisar reverter a migration:

```sql
-- Adicionar campos de volta em sellers
ALTER TABLE sellers
  ADD COLUMN daily_limit DECIMAL(15,2) DEFAULT 50000.00,
  ADD COLUMN daily_used DECIMAL(15,2) DEFAULT 0.00,
  ADD COLUMN daily_reset_at DATE DEFAULT NULL;

-- Adicionar campos de volta em acquirers
ALTER TABLE acquirers
  ADD COLUMN daily_limit DECIMAL(15,2) DEFAULT 100000.00,
  ADD COLUMN daily_used DECIMAL(15,2) DEFAULT 0.00,
  ADD COLUMN daily_reset_at DATE NOT NULL;
```

**ATENÇÃO:** Você também precisará reverter as alterações no código PHP e views.

## Arquivos Modificados

### Backend
- `app/controllers/web/AdminController.php`

### Frontend
- `app/views/admin/seller-details.php`
- `app/views/seller/profile.php`
- `app/views/admin/acquirers.php`

### SQL
- `sql/remove_daily_limit.sql` (novo)

### Scripts
- `apply_remove_daily_limit_migration.php` (novo)

### Documentação
- `DAILY_LIMIT_REMOVAL.md` (este arquivo)

## Observações Importantes

1. **Backup Recomendado**
   - Faça backup do banco antes de aplicar a migration
   - Especialmente das tabelas `sellers` e `acquirers`

2. **Compatibilidade**
   - Esta migration é compatível com o sistema atual
   - Não afeta transações existentes
   - Não afeta o funcionamento de cashout

3. **Limites de Cashout**
   - Continue usando `cashout_daily_limit` normalmente
   - Sistema de limites de cashout não foi alterado

4. **Monitoramento**
   - Monitore logs após aplicar a migration
   - Verifique se não há erros relacionados a campos ausentes

## Suporte

Em caso de problemas:

1. Verifique os logs do sistema: `app/logs/app.log`
2. Verifique erros do PHP
3. Confirme que a migration foi aplicada corretamente
4. Execute os testes de verificação acima

## Conclusão

A remoção do campo `daily_limit` simplifica o sistema e remove funcionalidades não utilizadas. O controle de limites agora é feito exclusivamente através do sistema de cashout, que é mais específico e adequado às necessidades do negócio.

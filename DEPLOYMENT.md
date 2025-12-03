# Guia de Deploy - Gateway PIX + PodPay

## ✅ Sistema Completo Implementado

### Arquivos Criados: 36 arquivos

#### Backend PHP
- **8 Models** - BaseModel, Seller, Acquirer, PixCashin, PixCashout, User, Log, WebhookQueue
- **6 Services** - Auth, AntiFraude, PodPay, Split, Webhook, Acquirer
- **3 Controllers API** - PIX, Cashout, Webhook
- **4 Workers** - process_webhooks, retry_failed_callbacks, reconcile_transactions, process_payouts

#### Banco de Dados
- **10 tabelas completas** com 120+ campos
- **Suporte a external_id** em cash-in e cash-out
- **Campos customer** para dados do pagador
- **Status mapeados** para PodPay

#### Documentação
- README.md - Visão geral
- INSTALACAO.md - Guia passo a passo
- API_DOCUMENTATION.md - Referência da API
- INTEGRACAO_PODPAY.md - Integração PodPay
- EXEMPLOS_API.md - Exemplos práticos
- DEPLOYMENT.md - Este arquivo

## 🚀 Deploy Passo a Passo

### 1. Requisitos do Servidor

```bash
# Ubuntu 20.04+ / Debian 11+
sudo apt update
sudo apt install -y apache2 php8.1 php8.1-mysql php8.1-curl php8.1-mbstring php8.1-xml mysql-server
```

### 2. Fazer Upload dos Arquivos

```bash
# Via SCP
scp -r gateway-pix/ user@servidor:/var/www/html/

# Ou via FTP/SFTP usando FileZilla, WinSCP, etc.
```

### 3. Configurar Banco de Dados

```bash
mysql -u root -p
```

```sql
CREATE DATABASE gateway_pix CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'gateway_user'@'localhost' IDENTIFIED BY 'SUA_SENHA_FORTE_AQUI';
GRANT ALL PRIVILEGES ON gateway_pix.* TO 'gateway_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

```bash
mysql -u gateway_user -p gateway_pix < /var/www/html/gateway-pix/sql/schema.sql
```

### 4. Adicionar Adquirente PodPay

```sql
USE gateway_pix;

INSERT INTO acquirers (name, code, api_url, api_key, api_secret, priority_order, status, daily_limit, daily_reset_at, config)
VALUES (
    'PodPay',
    'podpay',
    'https://api.podpay.co',
    'SUA_API_KEY_PODPAY',
    'SEU_API_SECRET_PODPAY',
    1,
    'active',
    1000000.00,
    CURDATE(),
    '{"withdraw_key": "SEU_WITHDRAW_KEY_AQUI"}'
);
```

### 5. Configurar .env

```bash
cd /var/www/html/gateway-pix
cp .env.example .env
nano .env
```

```env
APP_ENV=production
APP_NAME="Gateway PIX"
BASE_URL=https://gateway.seudominio.com

DB_HOST=localhost
DB_NAME=gateway_pix
DB_USER=gateway_user
DB_PASS=SUA_SENHA_FORTE_AQUI

API_RATE_LIMIT=100
API_RATE_WINDOW=60

WEBHOOK_MAX_RETRIES=5
WEBHOOK_RETRY_DELAY=60

PIX_EXPIRATION_MINUTES=30

LOG_LEVEL=warning
MAINTENANCE_MODE=false
```

### 6. Configurar Permissões

```bash
sudo chown -R www-data:www-data /var/www/html/gateway-pix
sudo chmod -R 755 /var/www/html/gateway-pix

mkdir -p logs
sudo chmod -R 777 logs

sudo chmod 600 .env
```

### 7. Configurar Apache

```bash
sudo nano /etc/apache2/sites-available/gateway-pix.conf
```

```apache
<VirtualHost *:80>
    ServerName gateway.seudominio.com
    ServerAlias www.gateway.seudominio.com

    DocumentRoot /var/www/html/gateway-pix

    <Directory /var/www/html/gateway-pix>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/gateway-pix-error.log
    CustomLog ${APACHE_LOG_DIR}/gateway-pix-access.log combined
</VirtualHost>
```

```bash
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2ensite gateway-pix
sudo systemctl restart apache2
```

### 8. Configurar SSL (Let's Encrypt)

```bash
sudo apt install -y certbot python3-certbot-apache
sudo certbot --apache -d gateway.seudominio.com
```

### 9. Configurar Workers (Cron)

```bash
sudo crontab -e -u www-data
```

```cron
* * * * * /usr/bin/php /var/www/html/gateway-pix/app/workers/process_webhooks.php >> /var/www/html/gateway-pix/logs/webhooks.log 2>&1

*/2 * * * * /usr/bin/php /var/www/html/gateway-pix/app/workers/retry_failed_callbacks.php >> /var/www/html/gateway-pix/logs/retry.log 2>&1

*/5 * * * * /usr/bin/php /var/www/html/gateway-pix/app/workers/reconcile_transactions.php >> /var/www/html/gateway-pix/logs/reconciliation.log 2>&1

*/3 * * * * /usr/bin/php /var/www/html/gateway-pix/app/workers/process_payouts.php >> /var/www/html/gateway-pix/logs/payouts.log 2>&1
```

### 10. Testar Instalação

```bash
curl https://gateway.seudominio.com/
```

Resposta esperada:
```json
{
  "app": "Gateway PIX",
  "version": "1.0.0",
  "status": "online",
  "timestamp": "2025-11-28T12:00:00-03:00"
}
```

### 11. Criar Primeiro Seller

```sql
USE gateway_pix;

INSERT INTO sellers (name, email, document, api_key, api_secret, webhook_url, status, balance, daily_limit, daily_reset_at, fee_percentage)
VALUES (
    'Meu Ecommerce',
    'financeiro@meuecommerce.com',
    '12345678000190',
    'sk_live_abc123def456ghi789',
    SHA2('MEU_SECRET_SUPER_SEGURO', 256),
    'https://meuecommerce.com/webhook/gateway-pix',
    'active',
    0.00,
    100000.00,
    CURDATE(),
    0.0199
);

INSERT INTO users (seller_id, name, email, password, role, status)
VALUES (
    LAST_INSERT_ID(),
    'Admin Ecommerce',
    'admin@meuecommerce.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'seller',
    'active'
);
```

### 12. Testar Integração PodPay

```bash
curl -X POST https://gateway.seudominio.com/api/pix/create \
  -H "Content-Type: application/json" \
  -H "X-API-Key: sk_live_abc123def456ghi789" \
  -H "X-Signature: $(echo -n '{"external_id":"TEST001","amount":10.00,"customer":{"name":"Teste","document":"12345678900","email":"teste@test.com"}}' | openssl dgst -sha256 -hmac 'MEU_SECRET_SUPER_SEGURO' | sed 's/^.* //')" \
  -d '{
    "external_id": "TEST001",
    "amount": 10.00,
    "customer": {
      "name": "Teste",
      "document": "12345678900",
      "email": "teste@test.com"
    }
  }'
```

## 🔒 Segurança Pós-Deploy

### 1. Firewall

```bash
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 22/tcp
sudo ufw enable
```

### 2. Alterar Senhas Padrão

```sql
UPDATE users
SET password = '$2y$10$NOVO_HASH_AQUI'
WHERE email = 'admin@gateway.com';
```

Gerar hash:
```php
<?php echo password_hash('NOVA_SENHA_FORTE', PASSWORD_DEFAULT); ?>
```

### 3. Proteger Arquivos Sensíveis

```bash
sudo chmod 600 .env
sudo chmod 600 app/config/config.php
```

### 4. Configurar Fail2Ban

```bash
sudo apt install -y fail2ban
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

## 📊 Monitoramento

### 1. Verificar Logs

```bash
tail -f logs/webhooks.log
tail -f logs/retry.log
tail -f logs/reconciliation.log
tail -f logs/payouts.log
tail -f /var/log/apache2/gateway-pix-error.log
```

### 2. Verificar Workers

```bash
ps aux | grep process_webhooks.php
ps aux | grep retry_failed_callbacks.php
ps aux | grep reconcile_transactions.php
ps aux | grep process_payouts.php
```

### 3. Monitorar Banco de Dados

```sql
SELECT COUNT(*) FROM pix_cashin WHERE status = 'waiting_payment';
SELECT COUNT(*) FROM webhooks_queue WHERE status = 'pending';
SELECT * FROM logs WHERE level = 'error' ORDER BY created_at DESC LIMIT 10;
```

## 🔧 Otimizações de Produção

### PHP (php.ini)

```ini
memory_limit = 512M
max_execution_time = 60
upload_max_filesize = 10M
post_max_size = 10M
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log
date.timezone = America/Sao_Paulo
opcache.enable = 1
opcache.memory_consumption = 128
```

### MySQL (my.cnf)

```ini
[mysqld]
max_connections = 200
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
query_cache_size = 64M
query_cache_limit = 2M
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
```

### Apache (apache2.conf)

```apache
Timeout 60
KeepAlive On
MaxKeepAliveRequests 100
KeepAliveTimeout 5

<IfModule mpm_prefork_module>
    StartServers 5
    MinSpareServers 5
    MaxSpareServers 10
    MaxRequestWorkers 150
    MaxConnectionsPerChild 3000
</IfModule>
```

## 💾 Backup

### Script de Backup Automático

```bash
#!/bin/bash
# /root/backup-gateway.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/gateway-pix"

mkdir -p $BACKUP_DIR

mysqldump -u gateway_user -p'SENHA' gateway_pix > $BACKUP_DIR/db_$DATE.sql

tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/html/gateway-pix

find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete

echo "Backup completed: $DATE"
```

```bash
chmod +x /root/backup-gateway.sh

sudo crontab -e
# Adicionar:
0 3 * * * /root/backup-gateway.sh >> /var/log/backup-gateway.log 2>&1
```

## 🚨 Troubleshooting

### Erro 500
```bash
tail -f /var/log/apache2/gateway-pix-error.log
# Verificar permissões e sintaxe do código
```

### Workers não executam
```bash
crontab -l -u www-data
# Verificar se cron está rodando
sudo systemctl status cron
```

### Erro de conexão MySQL
```bash
mysql -u gateway_user -p gateway_pix
# Verificar credenciais no .env
```

### PodPay não responde
```bash
# Verificar logs
tail -f logs/webhooks.log | grep podpay
# Testar conectividade
curl -I https://api.podpay.co
```

## ✅ Checklist Final

- [ ] Banco de dados criado e schema importado
- [ ] Adquirente PodPay configurada
- [ ] .env configurado corretamente
- [ ] Apache e SSL configurados
- [ ] Workers configurados no cron
- [ ] Primeiro seller criado
- [ ] Teste de PIX realizado com sucesso
- [ ] Teste de cashout realizado
- [ ] Webhook PodPay testado
- [ ] Webhook para seller testado
- [ ] Backup configurado
- [ ] Monitoramento configurado
- [ ] Firewall ativo
- [ ] Senhas padrão alteradas
- [ ] Logs sendo gravados
- [ ] SSL válido e ativo

## 📞 Suporte

- Documentação: README.md, API_DOCUMENTATION.md, INTEGRACAO_PODPAY.md
- Exemplos: EXEMPLOS_API.md
- Logs: `/var/www/html/gateway-pix/logs/`
- PodPay: https://docs.podpay.co

---

**Sistema Pronto para Produção!** 🎉

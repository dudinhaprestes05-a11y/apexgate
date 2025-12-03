# Guia de Instalação - Gateway PIX

## Requisitos do Servidor

### Software Necessário
- PHP 8.0 ou superior
- MySQL 5.7+ ou MariaDB 10.3+
- Apache 2.4+ com mod_rewrite habilitado
- Composer (opcional, não utilizado neste projeto)

### Extensões PHP Requeridas
```bash
php -m | grep -E 'pdo|pdo_mysql|curl|json|openssl|mbstring'
```

Instalar extensões faltantes (Ubuntu/Debian):
```bash
sudo apt-get install php8.0-mysql php8.0-curl php8.0-mbstring php8.0-xml
```

## Passo a Passo

### 1. Preparar Ambiente

```bash
cd /var/www/html
sudo mkdir gateway-pix
sudo chown -R www-data:www-data gateway-pix
cd gateway-pix
```

### 2. Fazer Upload dos Arquivos

Copie todos os arquivos do projeto para `/var/www/html/gateway-pix/`

### 3. Criar Banco de Dados

```bash
mysql -u root -p
```

```sql
CREATE DATABASE gateway_pix CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'gateway_user'@'localhost' IDENTIFIED BY 'senha_segura_aqui';
GRANT ALL PRIVILEGES ON gateway_pix.* TO 'gateway_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 4. Importar Schema

```bash
mysql -u gateway_user -p gateway_pix < sql/schema.sql
```

Verifique se as tabelas foram criadas:
```bash
mysql -u gateway_user -p gateway_pix -e "SHOW TABLES;"
```

### 5. Configurar Variáveis de Ambiente

```bash
cp .env.example .env
nano .env
```

Edite com seus dados:
```env
APP_ENV=production
BASE_URL=https://gateway.seudominio.com

DB_HOST=localhost
DB_NAME=gateway_pix
DB_USER=gateway_user
DB_PASS=senha_segura_aqui

API_RATE_LIMIT=100
WEBHOOK_MAX_RETRIES=5
LOG_LEVEL=warning
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

Criar VirtualHost:
```bash
sudo nano /etc/apache2/sites-available/gateway-pix.conf
```

Adicione:
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

Habilitar site e módulos:
```bash
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2ensite gateway-pix
sudo systemctl restart apache2
```

### 8. Configurar SSL (Let's Encrypt)

```bash
sudo apt-get install certbot python3-certbot-apache
sudo certbot --apache -d gateway.seudominio.com
```

### 9. Configurar Workers (Cron Jobs)

```bash
sudo crontab -e -u www-data
```

Adicione:
```cron
* * * * * /usr/bin/php /var/www/html/gateway-pix/app/workers/process_webhooks.php >> /var/www/html/gateway-pix/logs/webhooks.log 2>&1

*/5 * * * * /usr/bin/php /var/www/html/gateway-pix/app/workers/reconcile_transactions.php >> /var/www/html/gateway-pix/logs/reconciliation.log 2>&1

*/2 * * * * /usr/bin/php /var/www/html/gateway-pix/app/workers/process_payouts.php >> /var/www/html/gateway-pix/logs/payouts.log 2>&1
```

### 10. Testar Instalação

```bash
curl http://gateway.seudominio.com/
```

Resposta esperada:
```json
{
  "app": "Gateway PIX",
  "version": "1.0.0",
  "status": "online",
  "timestamp": "2023-12-01T12:00:00-03:00"
}
```

### 11. Verificar Workers

```bash
tail -f logs/webhooks.log
tail -f logs/reconciliation.log
tail -f logs/payouts.log
```

## Configuração de Produção

### PHP Settings (php.ini)

```ini
memory_limit = 256M
max_execution_time = 60
upload_max_filesize = 10M
post_max_size = 10M
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log
date.timezone = America/Sao_Paulo
```

### MySQL Otimizações (my.cnf)

```ini
[mysqld]
max_connections = 200
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
query_cache_size = 64M
query_cache_limit = 2M
```

### Firewall

```bash
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 22/tcp
sudo ufw enable
```

## Credenciais Iniciais

### Admin
- **Email:** admin@gateway.com
- **Senha:** password (MUDE IMEDIATAMENTE!)

### Seller Demo
- **Email:** seller@demo.com
- **Senha:** password
- **API Key:** sk_test_demo_key_123456789

## Alterar Senha Admin

```sql
UPDATE users
SET password = '$2y$10$NOVO_HASH_AQUI'
WHERE email = 'admin@gateway.com';
```

Gerar hash:
```php
<?php echo password_hash('nova_senha_segura', PASSWORD_DEFAULT); ?>
```

## Monitoramento

### Logs do Sistema
```bash
tail -f logs/*.log
tail -f /var/log/apache2/gateway-pix-error.log
```

### Logs do MySQL
```bash
sudo tail -f /var/log/mysql/error.log
```

### Status dos Workers
```bash
ps aux | grep process_webhooks.php
ps aux | grep reconcile_transactions.php
ps aux | grep process_payouts.php
```

## Backup

### Banco de Dados
```bash
mysqldump -u gateway_user -p gateway_pix > backup_$(date +%Y%m%d).sql
```

### Arquivos
```bash
tar -czf backup_files_$(date +%Y%m%d).tar.gz /var/www/html/gateway-pix
```

## Solução de Problemas

### Erro "500 Internal Server Error"
- Verificar logs do Apache
- Verificar permissões dos arquivos
- Verificar sintaxe do .htaccess

### Workers não executam
- Verificar crontab: `crontab -l -u www-data`
- Verificar permissões de execução
- Verificar logs dos workers

### Erro de conexão com banco
- Verificar credenciais no .env
- Verificar se MySQL está rodando: `systemctl status mysql`
- Verificar permissões do usuário do banco

### API retorna erro 401
- Verificar API Key
- Verificar assinatura HMAC
- Verificar status do seller no banco

## Contato Suporte

Para problemas técnicos, consulte os logs e a documentação completa no README.md

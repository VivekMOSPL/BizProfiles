# BizProfile Deployment Guide

## Overview
This guide covers deployment of the BizProfile framework on Linux shared hosting environments. BizProfile is a comprehensive PHP business management framework based on MVC architecture.

## Prerequisites

### System Requirements
- **Operating System**: Linux (Ubuntu, CentOS, Debian)
- **PHP Version**: 8.1 or higher
- **Web Server**: Apache 2.4, Nginx 1.18+, or Lighttpd
- **Database**: MySQL 5.7+, MariaDB 10.3+, or PostgreSQL
- **Extensions**: PDO, OpenSSL, Mbstring, Tokenizer, XML, Ctype, JSON, GD, cURL

### Directory Structure
```
/usr/local/lampp/htdocs/bizprofile/  (or your web root)
├── bizprofile/                    # Application root
│   ├── includes/                 # Core files
│   ├── storage/                 # Runtime files (cache, logs)
│   ├── themes/                  # Theme files
│   ├── vendor/                  # Composer dependencies
│   └── ...
└── ...
```

## Step 1: Install Dependencies

### 1.1 Composer Installation
```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# Navigate to project root
cd /path/to/your/project

# Create project structure and install dependencies
mkdir -p bizprofile
cd bizprofile

# Initialize Composer project
cat > composer.json << 'EOF'
{
    "name": "bizprofile/framework",
    "description": "BizProfile - A comprehensive business management framework",
    "type": "project",
    "license": "MIT",
    "require": {
        "php": "^8.1"
    },
    "autoload": {
        "psr-4": {
            "BizProfile\\": "includes/"
        }
    }
}
EOF

# Install dependencies
composer install --no-dev --optimize-autoloader
```

### 1.2 Server Configuration

#### Apache (.htaccess)
```apache
# d:\DataChron\CompanyWebsite\BizProfile\.htaccess
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^$ index.php [L]
    RewriteRule ^([^.]+)$ index.php?path=$1 [L]
    RewriteRule ^([^.]+)/?$ index.php?path=$1 [L]
    RewriteRule . index.php [L]
</IfModule>

# Security headers
<IfModule mod_headers.c>
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set X-Content-Type-Options "nosniff"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>
```

#### Nginx
```nginx
# d:\DataChron\CompanyWebsite\BizProfile\nginx.conf
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/your/project/bizprofile;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

## Step 2: Configure Environment

### 2.1 Database Setup
```sql
-- Create database
CREATE DATABASE bizprofile CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Import schema
mysql -u username -p bizprofile < /path/to/your/project/bizprofile/database/bizprofile.sql

-- Import enhanced tables (if needed)
mysql -u username -p bizprofile < /path/to/your/project/bizprofile/database/enhanced_requirements_tables.sql
```

### 2.2 Environment Configuration (.env)
```bash
# d:\DataChron\CompanyWebsite\BizProfile\.env
# Application Environment
APP_ENV=production
APP_DEBUG=false

# Database Configuration
DB_HOST=localhost
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
DB_DATABASE=bizprofile

# SMTP Email Configuration
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@yourdomain.com
MAIL_FROM_NAME="BizProfile"

# Security
SECRET_KEY=generate-a-secure-random-key-minimum-32-chars
JWT_SECRET=generate-a-secure-random-key-minimum-32-chars

# API Configuration
API_RATE_LIMIT=100
API_RATE_WINDOW=3600

# File Upload
MAX_UPLOAD_SIZE=10485760
ALLOWED_IMAGE_TYPES=image/jpeg,image/png,image/gif,image/webp
ALLOWED_FILE_TYPES=application/pdf,application/msword,text/plain

# Cache
CACHE_DRIVER=file
CACHE_TTL=3600

# Session
SESSION_LIFETIME=3600
SESSION_PATH=/

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=info

# Performance
OPCACHE_ENABLE=1
CACHE_LIMIT=1024

# Google Analytics
GA4_MEASUREMENT_ID=your_ga4_id

# Social Media
FACEBOOK_PAGE=https://facebook.com/yourpage
TWITTER_HANDLE=@yourhandle
LINKEDIN_PAGE=https://linkedin.com/company/yourcompany
```

### 2.3 File Permissions
```bash
# Set appropriate permissions
chmod -R 755 bizprofile
chmod -R 664 bizprofile/storage/
chmod -R 775 bizprofile/storage/logs/
chmod 664 bizprofile/.env
chmod 644 bizprofile/index.php
chmod 644 bizprofile/bootstrap.php
chmod 644 bizprofile/composer.json

# Create log files
mkdir -p bizprofile/storage/logs
touch bizprofile/storage/logs/access.log
touch bizprofile/storage/logs/error.log
chmod 664 bizprofile/storage/logs/*.log
```

## Step 3: Database Optimization

### 3.1 MySQL Configuration
```bash
# Add to /etc/mysql/my.cnf or /etc/my.cnf.d/mariadb-server.cnf
[mysqld]
# Performance settings
sort_buffer_size = 64M
read_buffer_size = 64M
join_buffer_size = 128M
# Key buffer for InnoDB
innodb_buffer_pool_size = 512M
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
max_connections = 100

# Security
secure_file_priv = /tmp

# Backup
# Run daily backup script
0 2 * * * /usr/bin/mysqldump -u username --password=password --all-databases --single-transaction | gzip > /backups/bizprofile-$(date +\%Y\%m\%d).sql.gz
```

### 3.2 Indexes
```sql
-- Create indexes for better performance
USE bizprofile;

-- Products table
CREATE INDEX idx_products_slug ON products(slug);
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_products_status ON products(status);

-- Services table
CREATE INDEX idx_services_slug ON services(slug);
CREATE INDEX idx_services_status ON services(status);

-- Visitors table
CREATE INDEX idx_visitors_ip ON visitors(ip_address);
CREATE INDEX idx_visitors_date ON visitors(visited_at);

-- Enquiries table
CREATE INDEX idx_enquiries_email ON enquiries(email);
CREATE INDEX idx_enquiries_status ON enquiries(status);
```

## Step 4: Cache Configuration

### 4.1 Redis (Optional)
```bash
# Install Redis
sudo apt-get install redis-server

# Configure Redis
sudo systemctl enable redis-server
sudo systemctl start redis-server

# Update .env for Redis cache
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=
```

### 4.2 File-based Cache Setup
```bash
# Create cache directory
mkdir -p bizprofile/storage/cache
chmod -R 775 bizprofile/storage/cache

# Create .htaccess for cache (Apache)
cat > bizprofile/storage/cache/.htaccess << 'EOF'
# Deny direct access to cache files
<FilesMatch "^\." >
    Require all denied
</FilesMatch>

# Allow access to files with cache extension
<FilesMatch "\\.cache$">
    Require all granted
</FilesMatch>
EOF
```

## Step 5: SSL Configuration

### 5.1 Let's Encrypt (Apache)
```bash
# Install certbot
sudo apt-get install certbot python3-certbot-apache

# Obtain SSL certificate
sudo certbot --apache

# Configure automatic renewal
sudo systemctl enable certbot.timer
sudo systemctl start certbot.timer
```

### 5.2 SSL Configuration for Nginx
```nginx
# Add to nginx.conf
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com;

    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;
    ssl_trusted_certificate /etc/letsencrypt/live/your-domain.com/chain.pem;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    # Security settings
    ssl_prefer_server_ciphers on;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;

    # HSTS
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
}
```

## Step 6: Production Optimizations

### 6.1 PHP Configuration
```ini
# /etc/php8.1/apache2/php.ini or php-fpm.conf

; Performance settings
memory_limit = 256M
max_execution_time = 300
max_input_vars = 3000
post_max_size = 10M
upload_max_filesize = 10M

; Security settings
enable_dl = off
allow_url_fopen = off
allow_url_include = off
; disabled for security
open_basedir = /path/to/your/project/bizprofile:/tmp

; OPcache
opcache.enable = 1
opcache.memory_consumption = 256
opcache.max_accelerated_files = 20000
opcache.interned_strings_buffer = 16
opcache.fast_shutdown = 0
opcache.enable_cli = 1

; Logging
log_errors = on
error_log = /var/log/php_errors.log

; Date
date.timezone = UTC
```

### 6.2 Apache Optimizations
```apache
# /etc/apache2/mods-available/optimization.conf
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresDefault "access plus 1 hour"
    ExpiresByType image/jpeg "access plus 1 week"
    ExpiresByType image/png "access plus 1 week"
    ExpiresByType image/gif "access plus 1 week"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/pdf "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>

<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml application/json application/javascript
</IfModule>

<IfModule mod_headers.c>
    # Enable gzip compression
    Header set X-Content-Type-Options "nosniff"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
    Header always append X-Frame-Options SAMEORIGIN

    # HSTS
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
</IfModule>
```

## Step 7: Maintenance Scripts

### 7.1 Database Cleanup Script
```bash
#!/bin/bash
# d:\DataChron\CompanyWebsite\BizProfile\scripts\cleanup.sh

# Clean up old logs
mysql -u username -p database -e "DELETE FROM logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 365 DAY)"

# Clean up old sessions
mysql -u username -p database -e "DELETE FROM user_sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL 30 DAY)"

# Clean up old notifications
mysql -u username -p database -e "DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 180 DAY) AND read = 1"

# Clean up old activity logs
mysql -u username -p database -e "DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"

# Clean up old newsletter subscriptions
mysql -u username -p database -e "UPDATE newsletter SET status = 'inactive' WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"

echo "Cleanup completed"

chmod +x scripts/cleanup.sh
```

### 7.2 Security Audit Script
```bash
#!/bin/bash
# d:\DataChron\CompanyWebsite\BizProfile\scripts\security_audit.sh

# Database security
mysql -u username -p database -e "SHOW TABLES"

# Check for outdated PHP versions
php --version

# Check file permissions
find . -type d -exec chmod 755 {} \;
find . -type f -name "*.php" -exec chmod 644 {} \;
find . -type f -name "*.html" -exec chmod 644 {} \;
find . -type f -name "*.css" -exec chmod 644 {} \;
find . -type f -name "*.js" -exec chmod 644 {} \;
find . -type f -name "*.env" -exec chmod 600 {} \;

# Check for exposed sensitive files
ls -la .htaccess
ls -la .env
ls -la config.php

# Check for debug mode
if [ -f ".env" ]; then
    if grep -q "APP_DEBUG=true" ".env"; then
        echo "WARNING: Debug mode is enabled in production!"
    fi
fi

echo "Security audit completed"
chmod +x scripts/security_audit.sh
```

## Step 8: Monitoring and Logging

### 8.1 Log Rotation
```bash
# Add to /etc/logrotate.d/bizprofile
cat >> /etc/logrotate.d/bizprofile << 'EOF'
/path/to/your/project/bizprofile/storage/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 664 www-data www-data
    sharedscripts
    postrotate
        /usr/bin/killall -HUP php-fpm 2>/dev/null || true
    endscript
}
EOF
```

### 8.2 Performance Monitoring
```bash
# Create monitoring script
cat > scripts/monitor.php << 'EOF'
<?php
namespace BizProfile\Core;

class SystemMonitor
{
    public static function checkSystem(): array
    {
        $status = [
            'php_version' => phpversion(),
            'memory_usage' => memory_get_usage(true),
            'disk_space' => disk_free_space('/path/to/your/project'),
            'database_connection' => self::checkDatabase(),
            'api_status' => self::checkApi(),
            'cache_status' => self::checkCache()
        ];
        
        return $status;
    }
    
    private static function checkDatabase(): bool
    {
        try {
            $db = Database::getInstance();
            $db->query("SELECT 1");
            return true;
        } catch (Exception $e) {
            error_log("Database check failed: " . $e->getMessage());
            return false;
        }
    }
    
    private static function checkApi(): bool
    {
        // Check API endpoints status
        return true;
    }
    
    private static function checkCache(): bool
    {
        try {
            $cache = new Cache();
            $cache->set('test', 'value', 60);
            $result = $cache->get('test');
            return $result === 'value';
        } catch (Exception $e) {
            error_log("Cache check failed: " . $e->getMessage());
            return false;
        }
    }
}
?>
EOF
```

## Step 9: Backup Strategy

### 9.1 Automated Backups
```bash
#!/bin/bash
# d:\DataChron\CompanyWebsite\BizProfile\scripts\backup.sh

TIMESTAMP=$(date +\%Y\%m\%d_\%H\%M\%S)
BACKUP_DIR="/backups"

# Database backup
mysqldump -u username --password=password --all-databases --single-transaction > $BACKUP_DIR/bizprofile_db_$TIMESTAMP.sql

# Application files backup
zip -r $BACKUP_DIR/bizprofile_files_$TIMESTAMP.zip /path/to/your/project/bizprofile --exclude "*.log" --exclude "storage/cache/*"

# Clean up old backups
find $BACKUP_DIR -name "bizprofile_*.zip" -mtime +7 -delete
find $BACKUP_DIR -name "bizprofile_db_*.sql" -mtime +7 -delete

echo "Backup completed: $TIMESTAMP"
chmod +x scripts/backup.sh

# Add to crontab for automatic backup (daily at 2 AM)
0 2 * * * /path/to/your/project/bizprofile/scripts/backup.sh
```

## Step 10: Update and Maintenance

### 10.1 Update Procedure
```bash
#!/bin/bash
# d:\DataChron\CompanyWebsite\BizProfile\scripts\update.sh

# Pull latest changes from git
composer install --no-dev --optimize-autoloader
php artisan optimize 2>/dev/null || true

# Clear caches
rm -rf storage/cache/*
rm -rf storage/logs/*.log

# Run migrations (if any)
php artisan migrate --force 2>/dev/null || true

# Restart web server
sudo systemctl restart apache2 2>/dev/null || sudo systemctl restart nginx 2>/dev/null || true

echo "Update completed successfully"
chmod +x scripts/update.sh
```

## Common Issues and Solutions

### Issue: 500 Internal Server Error
```bash
# Check error logs
tail -f /var/log/apache2/error.log

tail -f /var/log/php_errors.log

# Check file permissions
find /path/to/your/project -type f -name "*.php" -exec ls -la {} \;
```

### Issue: Database Connection Failed
```bash
# Check MySQL service
sudo systemctl status mysql

# Check database credentials
sudo mysql -u username -p -e "SELECT 1;"

# Update .env file
DB_HOST=localhost
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Issue: Images Not Loading
```bash
# Check file permissions
find /path/to/your/project/themes -type d -exec chmod 755 {} \;
find /path/to/your/project/themes -type f -exec chmod 644 {} \;

# Check upload directory
mkdir -p /path/to/your/project/storage/uploads
chmod -R 775 /path/to/your/project/storage/uploads
```

## Security Checklist

- [ ] Use HTTPS with valid SSL certificate
- [ ] Set appropriate file permissions
- [ ] Update PHP and dependencies regularly
- [ ] Use strong passwords for database and admin accounts
- [ ] Enable logging and monitor for suspicious activities
- [ ] Regularly back up the database and application files
- [ ] Keep system updated with latest security patches
- [ ] Validate all user inputs and use prepared statements
- [ ] Implement proper session management
- [ ] Use secure password hashing algorithms

## Support

For deployment issues, check the following:

1. Application error logs: `storage/logs/error.log`
2. Web server logs: `var/log/apache2/error.log` or `var/log/nginx/error.log`
3. Database logs: `/var/log/mysqld.log` (MySQL)

To get more detailed help:
- Check the application console: `storage/logs/access.log`
- Review database queries and performance: Use database optimizer script
- Check system resources: `scripts/monitor.php`

Visit our documentation for more detailed deployment information.

---

*This deployment guide is continuously updated. Always test changes in a development environment before applying to production.*

Generated: $(date)
Version: $(grep '"version"' composer.json || echo "1.0.0")
```
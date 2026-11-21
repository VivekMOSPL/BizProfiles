# BizProfile Configuration Setup Guide

## Overview
This document provides comprehensive instructions for configuring the BizProfile framework for various environments and use cases. It covers all configuration files, environment variables, and setup procedures needed to run your BizProfile application in development, staging, or production environments.

---

## 📋 **QUICK ACCESS CHECKLIST**

### **Core Configuration Files**
- [ ] **`.env`** - Environment-specific configuration
- [ ] **`composer.json`** - PHP dependencies
- [ ] **`php.ini`** - PHP settings
- [ ] **`nginx.conf` or `.htaccess`** - Web server configuration
- [ ] **`database/bizprofile.sql`** - Database schema
- [ ] **`database/enhanced_requirements_tables.sql`** - Enhanced database tables

### **Configuration Categories**

### 1. **Environment Configuration** (`.env`)
- Database settings
- Email configuration
- Security settings
- Cache settings
- CORS configuration

### 2. **Application Configuration** (composer.json)
- PHP dependencies
- Autoloading configuration
- Script definitions

###  חילדי **Web Server Configuration**
- Apache (`.htaccess`)
- Nginx (`nginx.conf`)
- SSL/TLS configuration
- Performance optimization

### **5. Performance Optimization**
- Caching configuration
- GZIP compression
- Browser caching
- Database optimization

### 6. **Security Configuration**
- Security headers
- CORS settings
- SSL/TLS configuration
- File upload restrictions

---

## 🔧 **CONFIGURATION SETUP GUIDE**

### **Step 1: Copy Environment File**
```bash
# Copy .env.example to .env (if it exists)
cp .env.example .env

# Or create .env from scratch
nano .env
```

### **Step 2: Update Database Configuration**

The `.env` file contains all your application configuration. Here's a breakdown:

#### **Database Settings**
```ini
# Database Configuration
DB_HOST=localhost
DB_USERNAME=root
DB_PASSWORD=your_database_password
DB_DATABASE=bizprofile
```

#### **PHP Application Settings**
```ini
# Application Environment
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_VERSION=1.0.0

# Security
JWT_SECRET=your-secret-key-change-this-minimum-32-chords
SECRET_KEY=your-secret-key-generate-a-secure-one
```

#### **Email Configuration (SMTP)**
```ini
# SMTP Email Configuration
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@yourdomain.com
MAIL_FROM_NAME="BizProfile"
```

#### **Security and API Settings**
```ini
# Security Headers
SECURITY_HEADERS_ENABLED=true
CSP_POLICY=default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'

# Rate Limiting
API_RATE_LIMIT=100
API_RATE_WINDOW=3600
RATE_LIMIT_ENABLED=true

# CORS Configuration
CORS_ENABLED=true
CORS_ALLOWED_ORIGINS=*
```

#### **Performance Settings**
```ini
# Performance Optimization
CACHE_DRIVER=file
CACHE_TTL=3600
REDIS_HOST=localhost
REDIS_PORT=6379

# File Upload
MAX_UPLOAD_SIZE=10485760
ALLOWED_IMAGE_TYPES=image/jpeg,image/png,image/gif,image/webp
ALLOWED_FILE_TYPES=application/pdf,application/msword,text/plain
```

#### **Advanced Features**
```ini
# Google Analytics
GA4_MEASUREMENT_ID=your-ga4-id
GTM_CONTAINER_ID=

# Social Media Integration
FACEBOOK_PAGE=https://facebook.com/yourpage
TWITTER_HANDLE=@yourhandle
LINKEDIN_PAGE=https://linkedin.com/company/yourcompany
INSTAGRAM_HANDLE=@yourhandle
YOUTUBE_CHANNEL=https://youtube.com/yourchannel

# Search Configuration
SEARCH_INDEX_DIR=storage/search
SEARCH_RESULT_LIMIT=20
```

---

## 🖥️ **WEB SERVER CONFIGURATION**

### **Apache Server Configuration** (`.htaccess`)

#### **Basic Routing**
```apache
# d:\DataChron\CompanyWebsite\BizProfile\.htaccess
<IfModule mod_rewrite.c>
    # Disable in production if using proper server config
    RewriteEngine On
    RewriteRule ^$ index.php [L]
    RewriteRule ^([^.]+)$ index.php?path=$1 [L]
    RewriteRule ^([^.]+)/?$ index.php?path=$1 [L]
    RewriteRule . index.php [L]
</IfModule>
```

#### **Security Headers**
```apache
# d:\DataChron\CompanyWebsite\BizProfile\.htaccess
<IfModule mod_headers.c>
    # Security Headers
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set X-Content-Type-Options "nosniff"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
    Header set Strict-Transport-Security "max-age=31536000; includeSubDomains" env=HTTPS
</IfModule>

# GZIP Compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml application/json application/javascript text/css image/svg+xml
</IfModule>

# Browser Caching
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresDefault "access plus 1 hour"
    ExpiresByType image/jpeg "access plus 1 week"
    ExpiresByType image/png "access plus 1 week"
    ExpiresByType image/gif "access plus 1 week"
    ExpiresByType image/webp "access plus 1 week"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/pdf "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

### **Nginx Server Configuration**

#### **Basic Nginat Configuration**
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

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # GZIP
    gzip on;
    gzip_types text/html text/plain text/xml application/json application/javascript text/css image/svg+xml;
    gzip_vary on;
}

# HTTPS Configuration (uncomment when SSL is available)
# server {
#     listen 443 ssl http2;
#     server_name your-domain.com;
#
#     ssl_certificate /path/to/cert.pem;
#     ssl_certificate_key /path/to/key.pem;
#
#     # Include HTTP configuration
#     include /etc/nginx/conf.d/http-server.conf;
# }
```

### **HTTPS/SSL Configuration**

#### **Let's Encrypt Setup**
```bash
# Install certbot
sudo apt-get install certbot python3-certbot-apache

# Obtain SSL certificate
sudo certbot --apache

# Configure automatic renewal
sudo systemctl enable certbot.timer
sudo systemctl start certbot.timer
```

---

## 🐳 **DATABASE SETUP**

### **Initial Database Setup**

#### **Option 1: MySQL Command Line**
```bash
# Create database
mysql -u root -p "CREATE DATABASE IF NOT EXISTS bizprofile;"

# Import schema
mysql -u root -p bizprofile < database/bizprofile.sql

# Import enhanced tables (if needed)
mysql -u root -p bizprofile < database/enhanced_requirements_tables.sql

# Import menus management (if needed)
mysql -u root -p bizprofile < database/menus_management.sql
```

#### **Option 2: phpMyAdmin**
1. Install phpMyAdmin
2. Access: `http://your-domain.com/phpmyadmin`
3. Create database and import SQL files

#### **Option 3: Docker**
```dockerfile
FROM mysql:8.0

# Copy SQL files
COPY database/ /docker-entrypoint-initdb.d/

# Set permissions
RUN chmod -R 755 /docker-entrypoint-initdb.d
```

### **Database Optimization**

#### **MySQL Configuration**
```ini
# /etc/mysql/my.cnf or /etc/my.cnf.d/mariadb-server.cnf

[mysqld]
# Performance settings
sort_buffer_size = 64M
read_buffer_size = 64M
join_buffer_size = 128M
innodb_buffer_pool_size = 512M
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
max_connections = 100

# Security
sql_mode = NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION
secure_file_priv = /tmp

# Backup
# Run daily backup script
0 2 * * * /usr/bin/mysqldump -u username --password=password --all-databases --single-transaction | gzip > /backups/bizprofile-$(date +\%Y\%m\%d).sql.gz
```

### **Database Indexes**
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

-- Pages table
CREATE INDEX idx_pages_slug ON pages(slug);
CREATE INDEX idx_pages_status ON pages(status);

-- Activity logs
CREATE INDEX idx_activity_logs_user ON activity_logs(user_id);
CREATE INDEX idx_activity_logs_date ON activity_logs(created_at);

-- User sessions
CREATE INDEX idx_user_sessions_user ON user_sessions(user_id);
```

---

## 🚀 **APPLICATION DEPLOYMENT**

### **1. Composer Installation**
```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# Navigate to project
mkdir -p myproject
cd myproject

# Create project structure
mkdir -p bizprofile
cd bizprofile

# Initialize Composer project
cat > composer.json << 'EOF'
{
    "name": "bizprofile/framework",
    "description": "BizProfile - A comprehensive business management framework",
    "type": "project",

## 🌐 **HOSTING SETUP**

### **Shared Hosting (cPanel/Plesk)**
```bash
# Upload BizProfile folder to public_html or httpdocs

# Set permissions
chmod -R 755 bizprofile
chmod -R 664 bizprofile/storage/
chmod 664 bizprofile/.env
chmod 644 bizprofile/index.php

# Create database using phpMyAdmin or SSH
# Configure domain pointer

# Clear cache (if any)
rm -rf bizprofile/storage/cache/*
```

### **VPS/Dedicated Server**
```bash
# Install required software
sudo apt update
sudo apt install -y php8.1 php8.1-pdo php8.1-mbstring php8.1-xml php8.1-gd php8.1-curl nginx mysql-server

# Configure Nginx
sudo nano /etc/nginx/sites-available/default
# Add configuration from nginx.conf

sudo systemctl enable nginx
sudo systemctl restart nginx

# Configure MySQL
sudo systemctl enable mysql
sudo systemctl restart mysql
```

### **Docker Deployment**
```bash
# Build Docker image
docker build -t bizprofile .

# Run container
docker run -d -p 80:80 --name bizprofile bizprofile

# Or use docker-compose
# docker-compose up -d
```

---

## 🛠️ **MAINTENANCE COMMANDS**

### **Web Server Commands**
```bash
# Restart web server
sudo systemctl restart apache2  # or nginx

# Check web server status
sudo systemctl status apache2  # or nginx

# View web server logs
sudo tail -f /var/log/apache2/error.log  # or /var/log/nginx/error.log
```

### **PHP Commands**
```bash
# Check PHP version
php -v

# Check PHP extension
php -m | grep -E '(pdo|json|mbstring)'

# Clear PHP opcode cache
# Apache: sudo systemctl restart apache2
# Nginx: sudo systemctl restart php-fpm
```

### **Database Commands**
```bash
# Backup database
sudo mysqldump -u username -p dbname > backup_$(date +%Y%m%d_%H%M%S).sql

# Restore database
sudo mysql -u username -p dbname < backup_20240101_120000.sql

# Optimize tables
sudo mysql -u username -p dbname -e "OPTIMIZE TABLE users; OPTIMIZE TABLE products;"

# Clean old logs
sudo mysql -u username -p dbname -e "DELETE FROM logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);"
```

### **Cache Commands**
```bash
# Clear application cache
rm -rf bizprofile/storage/cache/*

# Clear uploaded files
rm -rf bizprofile/storage/uploads/*

# Clear log files
rm -f bizprofile/storage/logs/*.log
```

---

## 🚨 **ERROR TROUBLESHOOTING**

### **Common Issues and Solutions**

#### **1. Database Connection Error**
```bash
# Issue: Cannot connect to database

# Solution:
1. Check MySQL service
   sudo systemctl status mysql

2. Verify database user permissions
   GRANT ALL PRIVILEGES ON dbname.* TO 'username'@'localhost';

3. Check .env configuration
   DB_HOST=localhost
   DB_USERNAME=root
   DB_PASSWORD=your_password
   DB_DATABASE=bizprofile

4. Test connection
mysql -u root -e "USE bizprofile;"
```

#### **2. PHP Extension Missing**
```bash
# Issue: Required PHP extensions not installed

# Solution:
# Install missing extensions
sudo apt install -y php8.1-pdo php8.1-mbstring php8.1-xml php8.25-gd php8.1-curl

# Check installed extensions
php -r "print_r(get_loaded_extensions());"
```

#### **3. Web Server Configuration Error**
```bash
# Issue: Web server configuration error

# Solution: Check web server logs
# Apache
sudo tail -f /var/log/apache2/error.log

# Nginx
sudo tail -f /var/log/nginx/error.log

# Test configuration
sudo apache2ctl -t  # or nginx -t
```

#### **4. Permission Denied**
```bash
# Issue: File or directory permission denied

# Solution: Set correct permissions
sudo chmod -R 755 bizprofile
sudo chmod -R 664 bizprofile/storage/
sudo chmod 600 bizprofile/.env
```

---

## 📊 **PERFORMANCE MONITORING**

### **System Status Page**
The BizProfile framework includes a comprehensive system status page accessible via:

- **Admin Panel**: `/admin/system-status`
- **API Endpoint**: `/api/system/health`
- **Features**:
  - Database connectivity checks
 pretty fixed

### **Performance Metrics**
- Memory usage
- CPU usage
- Response time
- Error rate
- Database connections
- Cache hit rates
- Uptime monitoring

---

## 🔐 **SECURITY CHECKLIST**

### **Pre-deployment Security Checks**
- [ ] Enable HTTPS/SSL
- [ ] Set secure .env permissions
- [ ] Configure security headers
- [ ] Set up file upload restrictions
- [ ] Configure rate limiting
- [ ] Enable CORS appropriately
- [ ] Set up backup strategies
- [ ] Configure logging
- [ ] Test security vulnerabilities
- [ ] Validate input sanitization

### **Post-deployment Security Checks**
- [ ] Monitor for suspicious activity
- [ ] Regular security updates
- [ ] Backup verification
- [ ] Performance monitoring
- [ ] Access control review

---

## 📚 **DOCUMENTATION**

### **Documentation Files**
- `help/deployment_guide.md` - Complete deployment guide
- `help/configuration_setup.md` - Configuration setup guide (this file)
- `README.md` - Project overview and quick start
- `CHANGELOG.md` - Version history and updates

### **API Documentation**
- Generated automatically via `ApiDocumentation.php`
- Accessible via `/api/v1/docs`
- Includes all endpoints with parameters and responses

### **User Documentation**
- Admin panel help sections
- Configuration examples
- Feature explanations
- Troubleshooting guides

---

## 🔄 **UPDATE AND MAINTENANCE**

### **Update Procedures**
1. Backup current installation
2. Update configuration files
3. Run database migrations (if needed)
4. Clear cache
5. Test functionality
6. Deploy changes
7. Monitor system

### **Maintenance Schedule**
- **Daily**: Backup, log rotation, cache cleanup
- **Weekly**: System health checks, performance monitoring
- **Monthly**: Backup verification, security audits
- **Quarterly**: Software updates, security patches

---

## 📞 **SUPPORT AND TROUBLESHOOTING**

### **Getting Help**
- **Documentation**: Check `help/` directory
- **Community**: Project forums or Discord
- **GitHub**: Issue tracking and feature requests
- **Email Support**: contact@bizprofile.com

### **Common Issues and Solutions**
See the troubleshooting section in the main documentation for detailed solutions to common deployment and configuration issues.

---

## 🚀 **FINAL DEPLOYMENT CHECKLIST**

### **Pre-deployment Checklist**
- [ ] All configuration files completed
- [ ] Database imported and configured
- [ ] Web server configured
- [ ] Permissions set correctly
- [ ] SSL certificate configured (HTTPS)
- [ ] Security headers configured
- [ ] Backup strategy in place
- [ ] Monitoring setup
- [ ] Testing completed

### **Post-deployment Checklist**
- [ ] Website accessible
- [ ] Admin panel working
- [ ] API endpoints functional
- [ ] Performance optimized
- [ ] Security measures active
- [ ] Backups running
- [ ] Monitoring active
- [ ] User access granted

---

## 🎯 **BIZPROFILE DEPLOYMENT SUCCESSFUL!**

Your BizProfile framework is now ready for production deployment! The configuration setup guide provides everything you need to successfully deploy your application to any hosting environment.

**Key Features of this Setup Guide:**
- ✅ Comprehensive configuration instructions
- ✅ Environment-specific settings
- ✅ Database setup and optimization
- ✅ Web server configuration (Apache/Nginx)
- ✅ Security hardening
- ✅ Performance optimization
- ✅ Monitoring and maintenance procedures
- ✅ Troubleshooting and support
- ✅ Complete file structure and permissions

**Your BizProfile application is ready to serve your users with enterprise-grade features, robust security, and excellent performance!** 🚀

---

*Document Version: 1.0.0*  
*Last Updated: $(date +%Y-%m-%d)*  
*Framework: BizProfile v1.0.0*  
*© 2024 BizProfile Team*
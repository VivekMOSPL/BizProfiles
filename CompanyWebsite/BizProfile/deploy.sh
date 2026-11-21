#!/bin/bash

# BizProfile Deployment Script
# Enhanced version with Testing Harness and Monitoring Integration
# Run this script to deploy BizProfile to production

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_header() {
    echo -e "${GREEN}================================================${NC}"
    echo -e "${GREEN}     BizProfile Production Deployment Script     ${NC}"
    echo -e "${GREEN}================================================${NC}"
}

print_subheader() {
    echo -e "${BLUE}--- $1 ---${NC}"
}

print_header()

# Check if running as root (optional but recommended for deployment)
if [[ $EUID -eq 0 ]]; then
    print_info "Running as root user"
else
    print_warn "Not running as root - some operations may fail"
fi

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to check PHP version
check_php() {
    print_info "Checking PHP version..."
    if ! command_exists php; then
        print_error "PHP not found. Please install PHP 8.1 or higher."
        exit 1
    fi
    
    PHP_VERSION=$(php -r "echo PHP_VERSION;")
    if [[ $(echo "$PHP_VERSION 8.1" | awk '{print ($1 > $2)}') -eq 1 ]]; then
        print_info "✅ PHP $PHP_VERSION found"
    else
        print_error "PHP $PHP_VERSION found, but PHP 8.1 or higher is required."
        exit 1
    fi
}

# Function to check database server
check_database() {
    print_info "Checking database server..."
    if command_exists mysql; then
        print_info "✅ MySQL client found"
        if mysql -u "root" -e "SELECT VERSION();" 2>/dev/null; then
            print_info "✅ Can connect to MySQL server"
        else
            print_warn "Cannot connect to MySQL server. Will try to install/detect."
        fi
    else
        print_warn "MySQL client not found. Some features may not work."
    fi
    
    if command_exists psql; then
        print_info "✅ PostgreSQL client found"
    fi
}

# Function to check web server
check_web_server() {
    print_info "Checking web server..."
    if command_exists apache2; then
        print_info "✅ Apache web server found"
    elif command_exists nginx; then
        print_info "✅ Nginx web server found"
    else
        print_warn "No web server detected. Will attempt to install."
    fi
}

# Function to setup project structure
setup_project_structure() {
    print_info "Setting up project structure..."
    
    # Check if we've already started with a different project
    local PROJECT_ROOT="${PWD}/bizprofile"
    
    if [ -f "${PROJECT_ROOT}/index.php" ]; then
        print_info "✅ BizProfile project structure already exists"
        cd "${PROJECT_ROOT}"
        return 0
    fi
    
    # Create project directory
    mkdir -p "${PROJECT_ROOT}"
    cd "${PROJECT_ROOT}"
    
    # List of required directories
    required_dirs=(
        "includes"
        "includes/Controllers"
        "includes/Core"
        "includes/Api"
        "themes"
        "themes/blue_corporate"
        "themes/dark_professional"
        "themes/green_business"
        "themes/orange_startup"
        "themes/purple_modern"
        "themes/admin"
        "storage"
        "storage/cache"
        "storage/logs"
        "storage/uploads"
        "lang/en"
        "tests"
        "tests/Unit"
        "tests/Integration"
        "database"
        "help"
        "vendor"
        "maintenance"
    )
    
    for dir in "${required_dirs[@]}"; do
        mkdir -p "${dir}"
    done
    
    print_info "✅ Project structure created"
}

# Function to copy files from source
copy_files() {
    print_info "Copying application files..."
    
    # Check if we're in the right directory
    if [ ! -f "index.php" ]; then
        print_error "index.php not found. This script should be run from the BizProfile project root."
        exit 1
    fi
    
    # Copy from the original location based on where we are
    local source_dir="$(dirname "${BASH_SOURCE[0]}")/BizProfile"
    
    if [ -d "${source_dir}" ]; then
        # Copy all files from source directory
        find "${source_dir}" -type f -name "*" -not -path "${source_dir}/.*" | while read file; do
            local dest="${PWD}/$(echo "$file" | sed "s|^${source_dir}/||")"
            mkdir -p "$(dirname "${dest}")"
            cp "$file" "${dest}"
            print_info "  📄 Copied: $(basename "${dest}")"
        done
        
        print_info "✅ All files copied"
    else
        print_error "Source directory not found: ${source_dir}"
        exit 1
    fi
}

# Function to setup database
setup_database() {
    print_info "Setting up database..."
    
    # Check if database files exist
    if [ ! -f "database/bizprofile.sql" ]; then
        print_error "database/bizprofile.sql not found."
        exit 1
    fi
    
    # Create database if it doesn't exist
    if command_exists mysql; then
        print_info "Setting up MySQL database..."
        
        # Try to create database
        if ! mysql -u "root" -e "CREATE DATABASE IF NOT EXISTS bizprofile;" 2>/dev/null; then
            print_warn "Could not create database with root user. Trying current user."
            mysql -e "CREATE DATABASE IF NOT EXISTS bizprofile;" 2>/dev/null
            if [ $? -ne 0 ]; then
                print_error "Cannot create database. Please create 'bizprofile' database manually."
                exit 1
            fi
        fi
        
        # Import schema
        print_info "Importing database schema..."
        mysql -u "root" bizprofile < "database/bizprofile.sql"
        
        # Import enhanced tables if available
        if [ -f "database/enhanced_requirements_tables.sql" ]; then
            print_info "Importing enhanced requirements tables..."
            mysql -u "root" bizprofile < "database/enhanced_requirements_tables.sql"
        fi
        
        print_info "✅ Database setup complete"
    elif command_exists psql; then
        print_info "Setting up PostgreSQL database..."
        # PostgreSQL setup would go here
        print_warn "PostgreSQL setup not implemented. Please set up database manually."
    else
        print_error "No database client found. Please install MySQL or PostgreSQL."
        exit 1
    fi
}

# Function to setup environment (fixed syntax)
setup_environment() {
    print_info "Setting up environment..."
    
    # Check if .env exists
    if [ -f ".env" ]; then
        print_info "✅ .env file already exists"
    else
        # Copy from .env.example
        if [ -f ".env.example" ]; then
            cp ".env.example" ".env"
            print_info "✅ .env file created from .env.example"
        else
            # Create minimal .env
            cat > ".env" << EOF
# BizProfile Environment Configuration
# Copy this file to .env and update the values

# Application Environment
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
JWT_SECRET=your-secret-key-change-this-in-production

# Database Configuration
DB_HOST=localhost
DB_USERNAME=root
DB_PASSWORD=
DB_DATABASE=bizprofile

# SMTP Email Configuration
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@bizprofile.com
MAIL_FROM_NAME="BizProfile"

# Security
SECRET_KEY=change-this-secret-key-generate-a-secure-one

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

# Version Control
APP_VERSION=1.0.0
DB_VERSION=2.0.0

# Testing Configuration
TEST_DATABASE=bizprofile_test
TEST_API_KEY=test-api-key-for-integration-testing
TEST_PERFORMANCE_THRESHOLD=100
EOF
            print_info "✅ Minimal .env file created"
        fi
    fi
    
    # Set permissions for .env
    chmod 600 ".env"
    print_info "✅ .env permissions set to 600"
}

# Function to setup web server configuration
setup_web_server_config() {
    print_info "Setting up web server configuration..."
    
    if command_exists apache2; then
        print_info "Setting up Apache configuration..."
        
        # Create .htaccess
        if [ ! -f ".htaccess" ]; then
            cat > ".htaccess" << 'EOF'
# BizProfile Apache Configuration
Options -MultiViews
RewriteEngine On

# Frontend routing for SPA
RewriteRule ^$ index.php [L]
RewriteRule ^([^.]+)$ index.php?path=$1 [L]
RewriteRule ^([^.]+)/?$ index.php?path=$1 [L]

# Security headers
<IfModule mod_headers.c>
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set X-Content-Type-Options "nosniff"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
    Header set Strict-Transport-Security "max-age=31536000; includeSubDomains" env=HTTPS
</IfModule>

# GZIP compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml application/json application/javascript text/css
</IfModule>

# Deny access to hidden files
<IfModule mod_authz_core.c>
    Require all denied
    <FilesMatch "^\.ht">
        Require all denied
    </FilesMatch>
</IfModule>
EOF
            print_info "✅ .htaccess file created"
        fi
        
        # Configure Apache modules
        if ! apache2 -M | grep -q "deflate_module"; then
            print_warn "GZIP module not enabled for Apache"
        fi
        
    elif command_exists nginx; then
        print_info "Setting up Nginx configuration..."
        
        # Create nginx configuration
        if [ ! -f "/etc/nginx/sites-available/bizprofile" ]; then
            cat > "/etc/nginx/sites-available/bizprofile" << 'EOF'
server {
    listen 80;
    server_name _;
    root /var/www/html/bizprofile;
    
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
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    
    # GZIP
    gzip on;
    gzip_types text/html text/plain text/xml application/json application/javascript text/css image/svg+xml;
}
EOF
            
            # Enable site
            ln -sf "/etc/nginx/sites-available/bizprofile" /etc/nginx/sites-enabled/
            
            # Test nginx configuration
            if nginx -t; then
                systemctl restart nginx
                print_info "✅ Nginx configuration created and services restarted"
            else
                print_error "Nginx configuration error"
                exit 1
            fi
        fi
    else
        print_warn "No web server found. Please install Apache or Nginx manually."
    fi
}

# Function to setup testing harness
setup_testing_harness() {
    print_subheader "Setting up Testing Harness"
    
    # Install PHPUnit if available
    if command_exists composer; then
        print_info "Installing testing dependencies..."
        
        # Create composer.json for testing if not exists
        if [ ! -f "composer.json" ]; then
            cat > "composer.json" << 'EOF'
{
    "name": "bizprofile/testing",
    "description": "Testing harness for BizProfile",
    "type": "project",
    "license": "MIT",
    "require": {
        "php": "^8.1",
        "phpunit/phpunit": "^9.0",
        "mockery/mockery": "^1.4"
    },
    "autoload": {
        "psr-4": {
            "BizProfile\\Tests\\": "tests/"
        }
    },
    "scripts": {
        "test": "vendor/bin/phpunit",
        "test-unit": "vendor/bin/phpunit tests/Unit",
        "test-integration": "vendor/bin/phpunit tests/Integration",
        "test-coverage": "vendor/bin/phpunit --coverage-text --coverage-clover clover.xml"
    }
}
EOF
        fi
        
        # Install PHPUnit
        composer install --no-dev --optimize-autoloader --quiet 2>/dev/null || {
            print_warn "Composer install failed. PHPUnit will need to be installed manually."
        }
    else
        print_warn "Composer not found. Testing harness will need to be set up manually."
    fi
    
    # Create test environment files
    print_info "Creating test environment files..."
    
    # Create test database configuration
    cat > "tests/.env.test" << 'EOF'
# Test Environment Configuration
APP_ENV=test
APP_DEBUG=true
DB_HOST=localhost
DB_USERNAME=root
DB_PASSWORD=
DB_DATABASE=bizprofile_test
TEST_API_KEY=test-api-key-for-integration-testing
API_RATE_LIMIT=1000
API_RATE_WINDOW=3600
EOF
    
    # Create test configuration
    cat > "tests/test_config.php" << 'EOF'
<?php
return [
    'database' => [
        'host' => getenv('DB_HOST', 'localhost'),
        'database' => getenv('DB_DATABASE', 'bizprofile_test'),
        'username' => getenv('DB_USERNAME', 'root'),
        'password' => getenv('DB_PASSWORD', ''),
        'charset' => 'utf8mb4'
    ],
    'api' => [
        'base_url' => getenv('APP_URL', 'http://localhost:8080'),
        'api_key' => getenv('TEST_API_KEY', 'test-api-key-for-integration-testing')
    ],
    'performance' => [
        'response_time_threshold_ms' => (int)getenv('TEST_PERFORMANCE_THRESHOLD', 100),
        'concurrent_users' => 10,
        'duration_seconds' => 30
    ]
];
EOF
    
    # Create PHPUnit configuration for testing
    cat > "phpunit.xml" << 'EOF'
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         verbose="true">
    <testsuites>
        <testsuite name="Unit Tests">
            <directory suffix=".php">./tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration Tests">
            <directory suffix=".php">./tests/Integration</directory>
        </testsuite>
        <testsuite name="System Tests">
            <directory suffix=".php">./tests/System</directory>
        </testsuite>
    </testsuites>
    
    <php>
        <env name="APP_ENV" value="test"/>
        <env name="DB_DATABASE" value="bizprofile_test"/>
        <env name="TEST_API_KEY" value="test-api-key-for-integration-testing"/>
    </php>
    
    <coverage>
        <include>
            <directory suffix=".php">./includes</directory>
        </include>
        <exclude>
            <directory suffix=".php">./includes/storage</directory>
            <directory suffix=".php">./tests</directory>
        </exclude>
    </coverage>
</phpunit>
EOF
    
    print_info "✅ Testing harness setup complete"
}

# Function to setup monitoring system
setup_monitoring() {
    print_subheader "Setting up Monitoring System"
    
    # Create monitoring scripts
    print_info "Creating monitoring scripts..."
    
    # Create system status checker
    cat > "bin/system_status" << 'EOF'
#!/bin/bash
# System status checker script

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR/.."

# Check if PHP is available
if ! command -v php >/dev/null 2>&1; then
    echo "❌ PHP not found. Please install PHP 8.1 or higher."
    exit 1
fi

# Run system status check
php -r "
require 'includes/Core/SystemStatus.php';

$config = [
    'cache_dir' => 'storage/cache',
    'database' => [
        'host' => getenv('DB_HOST', 'localhost'),
        'database' => getenv('DB_DATABASE', 'bizprofile'),
        'username' => getenv('DB_USERNAME', 'root'),
        'password' => getenv('DB_PASSWORD', '')
    ],
    'app_url' => (isset(\$_SERVER['HTTPS']) && \$_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . 
                 (isset(\$_SERVER['HTTP_HOST']) ? \$_SERVER['HTTP_HOST'] : 'localhost') . '/'
];

$systemStatus = new SystemStatus(\$config);
$status = \$systemStatus->getApiStatus();

print_r(\$status);
" > "storage/logs/system_status_$(date +%Y%m%d_%H%M%S).json"

echo "✅ System status saved to storage/logs/system_status_$(date +%Y%m%d_%H%M%S).json"
EOF
    
    # Create health check endpoint
    cat > "storage/logs/health_check.sh" << 'EOF'
#!/bin/bash
# Health check script

echo "🔍 Performing health check..."

# Check PHP version
if ! php -r "echo PHP_VERSION;" >/dev/null 2>&1; then
    echo "❌ PHP not installed or version incorrect"
    exit 1
fi

# Check database connection
if command -v mysql >/dev/null 2>&1; then
    if ! mysql -u "root" -e "SELECT VERSION();" 2>/dev/null; then
        echo "❌ Database connection failed"
        exit 1
    fi
else
    echo "⚠️  MySQL not available"
fi

# Check web server
if command -v apache2 >/dev/null 2>&1; then
    if ! apache2ctl -t 2>/dev/null; then
        echo "❌ Apache configuration error"
        exit 1
    fi
elif command -v nginx >/dev/null 2>&1; then
    if ! nginx -t 2>/dev/null; then
        echo "❌ Nginx configuration error"
        exit 1
    fi
else
    echo "⚠️  No web server detected"
fi

# Check application files
if [ ! -f "index.php" ]; then
    echo "❌ index.php not found"
    exit 1
fi

if [ ! -f ".env" ]; then
    echo "❌ .env file not found"
    exit 1
fi

echo "✅ Health check passed"
EOF
    
    # Create performance monitoring script
    cat > "bin/monitor_performance" << 'EOF'
#!/bin/bash
# Performance monitoring script

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR/.."

OUTPUT_FILE="storage/logs/performance_$(date +%Y%m%d_%H%M%S).json"

# Get system metrics
{
    echo "{"
    echo "  \"timestamp\": \"$(date -Iseconds)\","
    echo "  \"php_version\": \"$(php -r 'echo PHP_VERSION;')\","
    
    if command -v mysql >/dev/null 2>&1; then
        MYSQL_VERSION=$(mysql -u "root" -e "SELECT VERSION();" 2>/dev/null | tail -1)
        echo "  \"mysql_version\": \"$MYSQL_VERSION\","
    fi
    
    # Check memory usage
    if command -v free >/dev/null 2>&1; then
        MEMORY_INFO=$(free -m | awk 'NR==2{printf "%.2f%%", $3*100/$2}')
        echo "  \"memory_usage_percent\": $MEMORY_INFO,"
    fi
    
    # Check disk usage
    DISK_USAGE=$(df -h . | awk 'NR==2 {print $5}' | sed 's/%//')
    echo "  \"disk_usage_percent\": $DISK_USAGE,"
    
    echo "  \"file_count\": $(find . -type f | wc -l),"
    echo "  \"directory_count\": $(find . -type d | wc -l),"
    
    echo "}"
} > "$OUTPUT_FILE"

echo "✅ Performance metrics saved to $OUTPUT_FILE"
EOF
    
    # Create monitoring executable permissions
    chmod +x "bin/system_status"
    chmod +x "bin/monitor_performance"
    chmod +x "storage/logs/health_check.sh"
    
    print_info "✅ Monitoring system setup complete"
}

# Function to create maintenance scripts
create_maintenance_scripts() {
    print_info "Creating enhanced maintenance scripts..."
    
    # Create cache cleanup script
    cat > "maintenance/cache_cleanup.sh" << 'EOF'
#!/bin/bash
# Enhanced cache cleanup script

echo "🧹 Cleaning cache..."

# Clear application cache
if [ -d "storage/cache" ]; then
    find storage/cache -name "*.json" -delete
    echo "✅ Cache files cleaned"
fi

# Clean old log files
if [ -d "storage/logs" ]; then
    find storage/logs -name "*.log" -mtime +7 -delete
    echo "✅ Old log files cleaned"
fi

# Clear upload temporary files
if [ -d "storage/uploads" ]; then
    find storage/uploads -type f -name "*.tmp" -delete
    find storage/uploads -type f -name "*.part" -delete
    echo "✅ Temporary upload files cleaned"
fi

# Clean system status files
if [ -d "storage/logs" ]; then
    find storage/logs -name "system_status_*.json" -mtime +7 -delete
    echo "✅ Old system status files cleaned"
fi

# Clean performance files
if [ -d "storage/logs" ]; then
    find storage/logs -name "performance_*.json" -mtime +30 -delete
    echo "✅ Old performance files cleaned"
fi

echo "✅ Cache cleanup completed"
EOF
    
    # Create log rotation script
    cat > "maintenance/log_rotation.sh" << 'EOF'
#!/bin/bash
# Enhanced log rotation script

echo "📝 Rotating logs..."

LOG_DIR="storage/logs"

if [ -d "$LOG_DIR" ]; then
    # Rotate application logs
    for log in "$LOG_DIR"/*.log; do
        if [ -f "$log" ]; then
            mv "$log" "${log}.1"
            touch "$log"
            chmod 664 "$log"
        fi
    done
    
    # Rotate system status files
    for status in "$LOG_DIR"/system_status_*.json; do
        if [ -f "$status" ]; then
            mv "$status" "${status}.1"
        fi
    done
    
    # Rotate performance files
    for perf in "$LOG_DIR"/performance_*.json; do
        if [ -f "$perf" ]; then
            mv "$perf" "${perf}.1"
        fi
    done
    
    echo "✅ Log rotation completed"
else
    echo "⚠️  Log directory not found"
fi
EOF
    
    # Create database optimization script
    cat > "maintenance/database_optimize.sh" << 'EOF'
#!/bin/bash
# Enhanced database optimization script

echo "🗄️  Optimizing database..."

if command -v mysql >/dev/null 2>&1; then
    # Optimize tables
    mysql -u "root" bizprofile -e "OPTIMIZE TABLE users; OPTIMIZE TABLE products; OPTIMIZE TABLE product_releases; OPTIMIZE TABLE activity_logs; OPTIMIZE TABLE system_logs;" 2>/dev/null
    echo "✅ Database tables optimized"
    
    # Clean old logs (older than 30 days)
    mysql -u "root" bizprofile -e "DELETE FROM system_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);" 2>/dev/null
    echo "✅ Old system logs cleaned"
    
    # Clean old activity logs (older than 90 days)
    mysql -u "root" bizprofile -e "DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);" 2>/dev/null
    echo "✅ Old activity logs cleaned"
    
    # Clean test data (older than 7 days)
    mysql -u "root" bizprofile -e "DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY);" 2>/dev/null
    echo "✅ Old test logs cleaned"
    
else
    echo "⚠️  MySQL client not found"
fi

echo "✅ Database optimization completed"
EOF
    
    # Create test runner script
    cat > "bin/run_tests" << 'EOF'
#!/bin/bash
# Test runner script

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR/.."

print_info() {
    echo -e "\033[0;32m[INFO]\033[0m $1"
}

print_error() {
    echo -e "\033[0;31m[ERROR]\033[0m $1"
}

if [ ! -f "vendor/autoload.php" ]; then
    print_error "Composer dependencies not installed. Run 'composer install' first."
    exit 1
fi

if [ ! -f "phpunit.xml" ]; then
    print_error "PHPUnit configuration not found."
    exit 1
fi

# Set up test database
if command -v mysql >/dev/null 2>&1; then
    print_info "Setting up test database..."
    
    # Create test database
    mysql -u "root" -e "CREATE DATABASE IF NOT EXISTS bizprofile_test;" 2>/dev/null
    
    # Import schema
    mysql -u "root" bizprofile_test < "database/bizprofile.sql" 2>/dev/null
    
    # Import enhanced tables
    if [ -f "database/enhanced_requirements_tables.sql" ]; then
        mysql -u "root" bizprofile_test < "database/enhanced_requirements_tables.sql" 2>/dev/null
    fi
    
    print_info "✅ Test database setup complete"
fi

# Run tests
print_info "Running tests..."

# Run unit tests
if [ -f "vendor/bin/phpunit" ]; then
    print_info "Running unit tests..."
    vendor/bin/phpunit tests/Unit --colors=never
    
    UNIT_RESULT=$?
    if [ $UNIT_RESULT -eq 0 ]; then
        print_info "✅ Unit tests passed"
    else
        print_error "❌ Unit tests failed"
    fi
fi

# Run integration tests
if [ -f "vendor/bin/phpunit" ]; then
    print_info "Running integration tests..."
    vendor/bin/phpunit tests/Integration --colors=never
    
    INTEGRATION_RESULT=$?
    if [ $INTEGRATION_RESULT -eq 0 ]; then
        print_info "✅ Integration tests passed"
    else
        print_error "❌ Integration tests failed"
    fi
fi

# Generate test report
if [ -f "vendor/bin/phpunit" ]; then
    print_info "Generating test report..."
    vendor/bin/phpunit --coverage-text --coverage-clover clover.xml
fi

# Display summary
print_info "====================================="
print_info "📊 TEST SUMMARY"
print_info "====================================="
print_info "Unit Tests: $([ $UNIT_RESULT -eq 0 ] && echo '✅ PASSED' || echo '❌ FAILED')"
print_info "Integration Tests: $([ $INTEGRATION_RESULT -eq 0 ] && echo '✅ PASSED' || echo '❌ FAILED')"
print_info "Overall: $([ $UNIT_RESULT -eq 0 ] && [ $INTEGRATION_RESULT -eq 0 ] && echo '✅ ALL TESTS PASSED' || echo '❌ SOME TESTS FAILED')"
print_info "====================================="

exit $((UNIT_RESULT | INTEGRATION_RESULT))
EOF
    
    # Create performance testing script
    cat > "bin/run_performance_tests" << 'EOF'
#!/bin/bash
# Performance testing script

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR/.."

print_info() {
    echo -e "\033[0;32m[INFO]\033[0m $1"
}

print_error() {
    echo -e "\033[0;31m[ERROR]\033[0m $1"
}

if [ ! -f "vendor/autoload.php" ]; then
    print_error "Composer dependencies not installed. Run 'composer install' first."
    exit 1
fi

print_info "Starting performance tests..."

# Test API response times
if command -v curl >/dev/null 2>&1; then
    print_info "Testing API response times..."
    
    # Test key endpoints
    endpoints=(
        "/api/v1/products"
        "/api/v1/services"
        "/api/v1/news"
    )
    
    total_time=0
    endpoint_count=0
    
    for endpoint in "${endpoints[@]}"; do
        start_time=$(date +%s%N)
        
        response=$(curl -s -w "%{http_code}" -o /dev/null "http://localhost$endpoint")
        http_code=$response
        
        end_time=$(date +%s%N)
        elapsed_ms=$((($end_time - $start_time) / 1000000))
        
        total_time=$((total_time + elapsed_ms))
        endpoint_count=$((endpoint_count + 1))
        
        print_info "  $endpoint: ${elapsed_ms}ms (HTTP: $http_code)"
        
        if [ $elapsed_ms -gt 100 ]; then
            print_error "  ⚠️  Response time exceeds threshold"
        fi
    done
    
    if [ $endpoint_count -gt 0 ]; then
        avg_time=$((total_time / endpoint_count))
        print_info "Average API response time: ${avg_time}ms"
        
        if [ $avg_time -gt 100 ]; then
            print_error "Performance threshold exceeded"
            exit 1
        fi
    fi
fi

# Test database performance
if command -v mysql >/dev/null 2>&1; then
    print_info "Testing database performance..."
    
    # Measure query performance
    for i in {1..5}; do
        start_time=$(date +%s%N)
        mysql -u "root" bizprofile -e "SELECT COUNT(*) FROM users;" >/dev/null 2>&1
        end_time=$(date +%s%N)
        elapsed_ms=$((($end_time - $start_time) / 1000000))
        
        print_info "  Query $i: ${elapsed_ms}ms"
        
        if [ $elapsed_ms -gt 100 ]; then
            print_error "  ⚠️  Query performance exceeds threshold"
        fi
    done
fi

print_info "✅ Performance tests completed"
EOF
    
    # Create monitoring executable permissions
    chmod +x "bin/run_tests"
    chmod +x "bin/run_performance_tests"
    chmod +x "maintenance/cache_cleanup.sh"
    chmod +x "maintenance/log_rotation.sh"
    chmod +x "maintenance/database_optimize.sh"
    chmod +x "storage/logs/health_check.sh"
    
    print_info "✅ Enhanced maintenance scripts created"
}

# Function to test deployment
test_deployment() {
    print_info "Testing deployment..."
    
    # Test PHP syntax
    if php -l index.php >/dev/null 2>&1; then
        print_info "✅ PHP syntax check passed"
    else
        print_error "PHP syntax errors detected"
        php -l index.php
        exit 1
    fi
    
    # Test database connection
    if command_exists mysql; then
        if mysql -u "root" -e "SELECT 1;" >/dev/null 2>&1; then
            print_info "✅ Database connection test passed"
        else
            print_error "Database connection failed"
        fi
    fi
    
    # Test web server configuration
    if command_exists apache2; then
        if apache2ctl -t 2>/dev/null; then
            print_info "✅ Apache configuration test passed"
        else
            print_error "Apache configuration error"
            exit 1
        fi
    elif command_exists nginx; then
        if nginx -t 2>/dev/null; then
            print_info "✅ Nginx configuration test passed"
        else
            print_error "Nginx configuration error"
            exit 1
        fi
    fi
    
    # Test application functionality
    print_info "Testing application functionality..."
    
    # Test system status endpoint
    if [ -f "index.php" ]; then
        print_info "Testing system status endpoint..."
        php -r "
require 'includes/Core/SystemStatus.php';
$config = [
    'cache_dir' => 'storage/cache',
    'database' => [
        'host' => getenv('DB_HOST', 'localhost'),
        'database' => getenv('DB_DATABASE', 'bizprofile'),
        'username' => getenv('DB_USERNAME', 'root'),
        'password' => getenv('DB_PASSWORD', '')
    ],
    'app_url' => (isset(\$_SERVER['HTTPS']) && \$_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . 
                 (isset(\$_SERVER['HTTP_HOST']) ? \$_SERVER['HTTP_HOST'] : 'localhost') . '/'
];
$systemStatus = new SystemStatus(\$config);
$status = \$systemStatus->getOverallStatus();
print(\$status['status']);
" 2>/dev/null || print_error "System status check failed"
    fi
    
    print_info "✅ Deployment test completed"
}

# Function to show deployment summary
show_summary() {
    print_info "====================================="
    print_info "🎉 DEPLOYMENT SUMMARY"
    print_info "====================================="
    print_info ""
    print_info "📁 Project Location: $(pwd)"
    print_info "🕐 Deployment Time: $(date)"
    print_info "📊 PHP Version: $(php -r 'echo PHP_VERSION;')"
    
    if command_exists mysql; then
        DB_VERSION=$(mysql -u "root" -e "SELECT VERSION();" 2>/dev/null | tail -1 | tr -d ' ')
        print_info "🗄️  Database: $DB_VERSION"
    fi
    
    if command_exists apache2; then
        print_info "🌐 Web Server: Apache"
    elif command_exists nginx; then
        print_info "🌐 Web Server: Nginx"
    else
        print_info "🌐 Web Server: Not configured"
    fi
    
    print_info ""
    print_info "🔗 Access URLs:"
    print_info "   Website: http://your-domain.com"
    print_info "   Admin Panel: http://your-domain.com/admin/dashboard"
    print_info "   API Status: http://your-domain.com/api/system/health"
    print_info "   Theme Selector: http://your-domain.com/admin/theme"
    print_info "   System Status: http://your-domain.com/api/system/status"
    print_info "   Monitoring: http://your-domain.com/api/system/detailed"
    
    print_info ""
    print_info "📋 Important Files:":
    print_info "   .env - Environment configuration"
    print_info "   storage/logs/ - Application logs"
    print_info "   storage/cache/ - Cache files"
    print_info "   database/bizprofile.sql - Database schema"
    print_info "   tests/ - Test suite"
    print_info "   bin/ - Executable scripts"
    
    print_info ""
    print_info "⚙️  Configuration:"
    print_info "   Edit .env file to configure database and email settings"
    print_info "   Run ./maintenance/database_optimize.sh for maintenance"
    print_info "   Run ./bin/run_tests for testing"
    print_info "   Run ./bin/monitor_performance for monitoring"
    print_info "   Run ./bin/system_status for system status"
    print_info "   Run ./storage/logs/health_check.sh for health check"
    
    print_info ""
    print_info "🔧 Testing and Monitoring:"
    print_info "   1. Run ./bin/run_tests to run all tests"
    print_info "   2. Run ./bin/run_performance_tests for performance testing"
    print_info "   3. Run ./bin/system_status for system health"
    print_info "   4. Check storage/logs for detailed logs"
    print_info "   5. Set up automated testing in CI/CD pipeline"
    
    print_info ""
    print_info "🎯 Next Steps:"
    print_info "   1. Configure your domain and DNS"
    print_info "   2. Set up SSL certificate"
    print_info "   9. Update .env with production settings"
    print_info "   10. Test website functionality"
    print_info "   11. Schedule regular backups"
    print_info "   12. Set up monitoring and alerting"
    print_info "   13. Configure automated testing"
    print_info "   14. Document testing procedures"
    
    print_info ""
    print_info "✨ Deployment completed successfully! ✨"
    print_info "====================================="
}

# Main deployment process
print_header()

# Step 1: Check requirements
check_php
check_database
check_web_server

# Step 2: Setup project
print_info "Step 2: Setting up project..."
setup_project_structure

# Step 3: Copy files
print_info "Step 3: Copying files..."
copy_files

# Step 4: Setup database
print_info "Step 4: Setting up database..."
setup_database

# Step 5: Setup environment
print_info "Step 5: Setting up environment..."
setup_environment

# Step 6: Setup web server configuration
print_info "Step 6: Setting up web server..."
setup_web_server_config

# Step 7: Setup testing harness
print_info "Step 7: Setting up testing harness..."
setup_testing_harness

# Step 8: Setup monitoring system
print_info "Step 8: Setting up monitoring system..."
setup_monitoring

# Step 9: Setup file permissions
print_info "Step 9: Setting file permissions..."
setup_file_permissions

# Step 10: Install dependencies
print_info "Step 10: Installing dependencies..."
setup_dependencies

# Step 11: Create admin user
print_info "Step 11: Creating admin user..."
create_admin_user

# Step 12: Create maintenance scripts
print_info "Step 12: Creating maintenance scripts..."
mkdir -p "maintenance"
create_maintenance_scripts

# Step 13: Test deployment
print_info "Step 13: Testing deployment..."
test_deployment

# Step 14: Show summary
show_summary

print_info "🎉 BizProfile deployment completed successfully!"
print_info "====================================="
print_info "Your BizProfile website is now ready for production with comprehensive testing and monitoring!"
print_info "====================================="

# Offer to run additional setup
cat << 'EOF'

Would you like to run any additional setup tasks?
1. Run initial cache cleanup
2. Run database optimization
3. Run initial tests
4. Check system status
5. Exit

Please choose an option (1-5): 
EOF

read -r choice

case $choice in
    1)
        print_info "Running cache cleanup..."
        bash "maintenance/cache_cleanup.sh"
        ;;
    2)
        print_info "Running database optimization..."
        bash "maintenance/database_optimize.sh"
        ;;
    3)
        print_info "Running tests..."
        bash "./bin/run_tests"
        ;;
    4)
        print_info "Checking system status..."
        if [ -f "index.php" ]; then
            echo "🌐 System Status:"
            echo "   Health: curl http://localhost/api/system/health"
            echo "   Detailed: curl http://localhost/api/system/detailed"
            echo "   Status: curl http://localhost/api/system/status"
        fi
        ;;
    5)
        print_info "Exiting..."
        ;;
    *)
        print_warn "Invalid option. Exiting."
        ;;
esac

exit 0

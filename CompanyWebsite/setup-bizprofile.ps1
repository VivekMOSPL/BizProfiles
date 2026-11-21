#!/usr/bin/env pwsh
# Setup script for BizProfile project
# This script initializes the BizProfile development environment

# Parameters
param(
    [string]$Environment = "development",
    [string]$Path = "$(pwd)\..",
    [switch]$Force,
    [switch]$Help
)

function Write-Info {
    param([string]$Message)
    Write-Host "[INFO] $Message" -ForegroundColor Green
}

function Write-Warning {
    param([string]$Message)
    Write-Host "[WARNING] $Message" -ForegroundColor Yellow
}

function Write-Error {
    param([string]$Message)
    Write-Host "[ERROR] $Message" -ForegroundColor Red
}

function Show-Help {
    Write-Host "Usage: ./setup-bizprofile.ps1 [parameters]"
    Write-Host ""
    Write-Host "Parameters:"
    Write-Host "  -Environment <string>    Environment to setup (development|production|testing). Default: development"
    Write-Host "  -Path <string>           Project path. Default: parent directory"
    Write-Host "  -Force                   Overwrite existing files"
    Write-Host "  -Help                    Show this help"
    Write-Host ""
    Write-Host "Examples:"
    Write-Host "  ./setup-bizprofile.ps1 -Environment development"
    Write-Host "  ./setup-bizprofile.ps1 -Path C:\Projects\MyApp -Force"
    exit 0
}

# Show help if requested
if ($Help) {
    Show-Help
}

Write-Info "Starting BizProfile setup in $Environment environment..."
Write-Info "Project path: $Path"

# Check if PHP is installed
if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
    Write-Error "PHP is not installed or not in PATH. Please install PHP 8.1 or higher."
    exit 1
}

Write-Info "PHP found: $(php --version)"

# Check if Composer is installed
if (-not (Get-Command composer -ErrorAction SilentlyContinue)) {
    Write-Warning "Composer is not installed. Installing Composer..."
    
    # Download Composer
    $composerUrl = "https://getcomposer.org/installer"
    $composerFile = "C:\Users\${env:USERNAME}\Downloads\composer-setup.php"
    
    Invoke-WebRequest -Uri $composerUrl -OutFile $composerFile
    
    # Install Composer
    php $composerFile --install-dir="C:\Users\${env:USERNAME}\AppData\Roaming\Composer" --filename="composer.phar"
    
    # Add to PATH
    $path = [Environment]::GetEnvironmentVariable("PATH", "User")
    if ($path -notlike "*C:\Users\${env:USERNAME}\AppData\Roaming\Composer*") {
        $path = $path + ";C:\Users\${env:USERNAME}\AppData\Roaming\Composer"
        [Environment]::SetEnvironmentVariable("PATH", $path, "User")
    }
    
    Write-Info "Composer installed successfully"
}

Write-Info "Composer found: $(composer --version)"

# Create project directory structure
$directories = @(
    "$Path\vendor",
    "$Path\storage\logs",
    "$Path\storage\cache", 
    "$Path\tests",
    "$Path\migrations",
    "$Path\seeds",
    "$Path\templates",
    "$Path\lang",
    "$Path\docs"
)

foreach ($dir in $directories) {
    if (-not (Test-Path $dir)) {
        New-Item -Path $dir -ItemType Directory -Force | Out-Null
        Write-Info "Created directory: $dir"
    } else {
        Write-Warning "Directory already exists: $dir"
    }
}

# Create basic .env file if it doesn't exist
$envFile = "$Path\.env"
if (-not (Test-Path $envFile)) {
    Write-Info "Creating .env file..."
    
    $envContent = @"
# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bizprofile
DB_USERNAME=root
DB_PASSWORD=

# Application Configuration
APP_NAME=BizProfile
APP_ENV=$Environment
APP_DEBUG=true
APP_URL=http://localhost

# Security
APP_KEY=base64:generate-your-own-app-key

# Cache
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=debug
LOG_TO=file
"@
    
    $envContent | Out-File -FilePath $envFile -Encoding UTF8
    Write-Warning "Please edit the .env file and update the database credentials and APP_KEY"
} else {
    Write-Info "Environment file already exists: $envFile"
}

# Create basic phpunit.xml for testing if it doesn't exist
$phpunitFile = "$Path\phpunit.xml"
if (-not (Test-Path $phpunitFile)) {
    Write-Info "Creating phpunit.xml file..."
    
    $phpunitContent = @"
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/phpunit/9.1/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         beStrictAboutTestMethods="true"
         beStrictAboutOutputDuringTests="true"
         beStrictAboutInternalTypeHints="true"
         beStrictAboutMissingCoversAnnotation="true"
         beStrictAboutOverridingMethods="true"
         beStrictAboutUnhandledExceptions="true"
         beStrictAboutUnhandledTestExceptions="true">
    
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    
    <coverage>
        <include>
            <directory>src</directory>
        </include>
        <exclude>
            <directory>tests</directory>
        </exclude>
    </coverage>
    
    <php>
        <env name="APP_ENV" value="$Environment" />
        <env name="APP_DEBUG" value="true" />
        <env name="APP_KEY" value="$(openssl rand -hex 32)" />
    </php>
</phpunit>
"@
    
    $phpunitContent | Out-File -FilePath $phpunitFile -Encoding UTF8
    Write-Info "Created phpunit.xml file"
} else {
    Write-Info "phpunit.xml file already exists"
}

# Create basic composer.json if it doesn't exist
$composerFile = "$Path\composer.json"
if (-not (Test-Path $composerFile)) {
    Write-Info "Creating composer.json file..."
    
    $composerContent = @"
{
    "name": "datachron/company-website",
    "description": "Enhanced Company Website with BizProfile functionality",
    "type": "project",
    "license": "MIT",
    "authors": [
        {
            "name": "DataChron Team",
            "email": "team@datachron.com"
        }
    ],
    "require": {
        "php": "^8.1",
        "ext-pdo": "*",
        "ext-openssl": "*",
        "ext-json": "*",
        "laravel/framework": "^9.0",
        "guzzlehttp/guzzle": "^7.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^9.0",
        "mockery/mockery": "^1.4",
        "fakerphp/faker": "^1.9",
        "squizlabs/php_codesniffer": "^3.6",
        "phpstan/phpstan": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    },
    "scripts": {
        "post-install-cmd": [
            "@php artisan vendor:publish --tag=\"public\" --force"
        ],
        "post-update-cmd": [
            "@php artisan vendor:publish --tag=\"public\" --force"
        ],
        "test": [
            "@php artisan test"
        ]
    },
    "config": {
        "optimize-autoloader": true,
        "preferred-install": "dist",
        "sort-packages": true
    }
}
"@
    
    $composerContent | Out-File -FilePath $composerFile -Encoding UTF8
    Write-Info "Created composer.json file"
    
    # Install dependencies if in development mode
    if ($Environment -eq "development") {
        Write-Info "Installing dependencies..."
        
        try {
            $currentPath = pwd.Path
            Push-Location $Path
            
            composer install --no-interaction --prefer-dist --no-dev
            
            Pop-Location
            
            Write-Info "Dependencies installed successfully"
        } catch {
            Write-Warning "Failed to install dependencies. Please run 'composer install' manually."
        }
    }
} else {
    Write-Info "composer.json file already exists"
}

# Create basic .htaccess file if it doesn't exist
$htaccessFile = "$Path\.htaccess"
if (-not (Test-Path $htaccessFile)) {
    Write-Info "Creating .htaccess file..."
    
    $htaccessContent = @"
# Enable Apache Rewrite engine
RewriteEngine on

# Rewrite all requests to index.php for clean URLs
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ index.php [L]

# Set proper MIME types
<IfModule mime_module>
    AddType application/json .json
    AddType application/xml .xml
    AddType text/css .css
    AddType text/javascript .js
    AddType image/svg+xml .svg
</IfModule>

# Security headers
<IfModule headers_module>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# Disable directory listing
Options -Indexes
"@
    
    $htaccessContent | Out-File -FilePath $htaccessFile -Encoding UTF8
    Write-Info "Created .htaccess file"
} else {
    Write-Info ".htaccess file already exists"
}

# Create basic robots.txt if it doesn't exist
$robotsFile = "$Path\robots.txt"
if (-not (Test-Path $robotsFile)) {
    Write-Info "Creating robots.txt file..."
    
    $robotsContent = @"
User-agent: *
Allow: /
Sitemap: https://$(hostname == "localhost" ? "localhost" : hostname) /sitemap.xml
"@
    
    $robotsContent | Out-File -FilePath $robotsFile -Encoding UTF8
    Write-Info "Created robots.txt file"
} else {
    Write-Info "robots.txt file already exists"
}

Write-Info "BizProfile setup completed successfully!"
Write-Info "Next steps:"
Write-Host "  1. Edit the .env file and update your database credentials and APP_KEY" -ForegroundColor Yellow
Write-Host "  2. Run 'php artisan migrate' to setup the database" -ForegroundColor Yellow
Write-Host "  3. Run 'php artisan db:seed' to populate sample data" -ForegroundColor Yellow
Write-Host "  4. Run 'php artisan serve' to start the development server" -ForegroundColor Yellow
Write-Host "  5. Visit http://localhost:8000 to access your application" -ForegroundColor Yellow

Write-Info "Setup completed in $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"

# Make the script executable (Unix-like systems)
if ($PSVersionTable.PSVersion.Major -ge 6) {
    try {
        $currentFile = [System.IO.Path]::GetFullPath($MyInvocation.MyCommand.Definition)
        $currentFile | Unblock-File
        Write-Info "Script is now ready to run"
    } catch {
        # Ignore errors on Windows
    }
}
exit 0

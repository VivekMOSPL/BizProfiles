# BizProfile Testing Harness Documentation

## Overview

The BizProfile testing harness provides a comprehensive testing infrastructure for the BizProfile framework, including unit tests, integration tests, performance testing, security testing, and system monitoring. This documentation covers the available testing capabilities, configuration, and usage.

## Components

### 1. Test Suite Structure

The testing harness is organized into three main categories:

#### Unit Tests (`tests/Unit/`)
- **Purpose**: Test individual components and functions
- **Examples**:
  - Core database operations
  - Helper functions
  - Individual model methods
  - Utility functions

#### Integration Tests (`tests/Integration/`)
- **Purpose**: Test interactions between different components
- **Examples**:
  - API endpoint testing
  - Database integration
  - External service integration
  - System workflows

#### System Tests (`tests/System/`)
- **Purpose**: Test complete system functionality
- **Examples**:
  - End-to-end workflows
  - Performance testing
  - Security testing
  - Load testing

### 2. Core Testing Infrastructure

#### Base Test Class
**File**: `tests/TestSuite.php`

The `TestSuite` class provides:

- **Database Management**: Automatic database setup and cleanup
- **Environment Configuration**: Load environment variables
- **Test Utilities**: Helper methods for common testing operations
- **Assertions**: Custom assertions for database and system state validation

#### Configuration
**File**: `tests/test_config.php`

```php
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
```

#### Environment Configuration
**File**: `tests/.env.test`

```env
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
```

### 3. System Status and Monitoring

#### SystemStatus Class
**File**: `includes/Core/SystemStatus.php`

The `SystemStatus` class provides comprehensive system monitoring capabilities:

- **Health Checks**: Database, file system, cache, uploads, API, theme, PHP, and security checks
- **API Endpoints**: REST API for system status, health, and monitoring
- **Performance Metrics**: Response times, resource usage, and system performance
- **Alerting**: Critical and warning notifications for system issues

#### API Endpoints
The SystemStatusController provides the following endpoints:

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/system/status` | GET | Overall system status |
| `/api/system/health` | GET | Detailed health check |
| `/api/system/detailed` | GET | Comprehensive system information |
| `/api/system/database` | GET | Database status and metrics |
| `/api/system/filesystem` | GET | File system information |
| `/api/system/api` | GET | API endpoint information |
| `/api/system/logs` | GET | System logs |
| `/api/system/cache` | GET | Cache information |
| `/api/system/cleanup` | POST | Manual cleanup operations |

#### Example API Usage

```bash
# Get overall system status
curl http://your-domain.com/api/system/status

# Get detailed system information
curl http://your-domain.com/api/system/detailed

# Perform health check
curl http://your-domain.com/api/system/health

# Get system logs
curl http://your-domain.com/api/system/logs

# Clean up temporary files
curl -X POST http://your-domain.com/api/system/cleanup
```

### 4. Test Automation Scripts

#### Test Runner
**File**: `bin/run_tests`

The test runner automates the testing process:

**Features**:
- Setup test database
- Run unit and integration tests
- Generate test reports
- Display test summaries

**Usage**:
```bash
# Run all tests
./bin/run_tests

# Run only unit tests
vendor/bin/phpunit tests/Unit

# Run only integration tests
vendor/bin/phpunit tests/Integration
```

#### Performance Testing
**File**: `bin/run_performance_tests`

The performance testing script evaluates system performance:

**Features**:
- API response time testing
- Database performance testing
- Performance threshold validation
- Detailed performance reporting

**Usage**:
```bash
./bin/run_performance_tests
```

#### System Status
**File**: `bin/system_status`

The system status script provides real-time system monitoring:

**Features**:
- Comprehensive health checks
- System information collection
- JSON output format
- Log file generation

**Usage**:
```bash
./bin/system_status
```

#### Health Check
**File**: `storage/logs/health_check.sh`

The health check script validates system integrity:

**Features**:
- PHP version verification
- Database connection testing
- Web server configuration validation
- Application file verification

**Usage**:
```bash
bash storage/logs/health_check.sh
```

### 5. Maintenance Scripts

#### Cache Cleanup
**File**: `maintenance/cache_cleanup.sh`

Cleans up temporary cache and log files.

#### Log Rotation
**File**: `maintenance/log_rotation.sh`

Rotates application logs and removes old files.

#### Database Optimization
**File**: `maintenance/database_optimize.sh`

Optimizes database tables and cleans old records.

### 6. Deployment Script

#### Enhanced Deployment
**File**: `deploy.sh`

The enhanced deployment script includes:

- **Testing Harness Setup**: Automatic test environment configuration
- **Monitoring System Setup**: Complete monitoring infrastructure
- **Database Setup**: Test database creation and schema setup
- **Dependency Installation**: PHPUnit and testing dependencies
- **Enhanced Testing**: Integration with testing infrastructure

## Test Configuration

### PHPUnit Configuration
**File**: `phpunit.xml`

```xml
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
```

### Composer Configuration
**File**: `composer.json`

```json
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
```

## Test Categories

### Unit Tests
**Coverage**:
- Core database operations
- Helper functions
- Model methods
- Security functions
- Theme system

**Test Files**:
- `tests/Unit/CoreDatabaseTest.php`
- `tests/Unit/SecurityTest.php`
- `tests/Unit/ThemeTest.php`

### Integration Tests
**Coverage**:
- API endpoints
- Database integration
- System workflows
- External service integration

**Test Files**:
- `tests/Integration/ApiIntegrationTest.php`
- `tests/Integration/DatabaseIntegrationTest.php`
- `tests/Integration/SystemIntegrationTest.php`

### System Tests
**Coverage**:
- End-to-end workflows
- Performance testing
- Security testing
- Load testing

**Test Files**:
- `tests/System/SystemHealthTest.php`
- `tests/System/PerformanceTest.php`
- `tests/System/SecurityTest.php`

## Test Execution

### Local Testing

1. **Install Dependencies**:
```bash
git clone <repository-url>
cd BizProfile
./deploy.sh
```

2. **Run Tests**:
```bash
./bin/run_tests
```

3. **Run Performance Tests**:
```bash
./bin/run_performance_tests
```

4. **Check System Status**:
```bash
./bin/system_status
```

### Automated Testing (CI/CD)

#### GitHub Actions Example

Create `.github/workflows/test.yml`:

```yaml
name: Testing

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
    
    - name: Install dependencies
      run: composer install --no-interaction --prefer-dist
    
    - name: Run unit tests
      run: vendor/bin/phpunit tests/Unit
    
    - name: Run integration tests
      run: vendor/bin/phpunit tests/Integration
    
    - name: Run performance tests
      run: ./bin/run_performance_tests
    
    - name: Check system status
      run: ./bin/system_status
```

### Docker Testing

Create `docker-compose.test.yml`:

```yaml
version: '3.8'

services:
  app:
    build: .
    ports:
      - "80:80"
      - "3306:3306"
    environment:
      - APP_ENV=test
      - DB_HOST=db
      - DB_DATABASE=bizprofile_test
    depends_on:
      - db
    volumes:
      - .:/var/www/html/bizprofile
      - ./logs:/var/log/apache2
    command: [
      "bash", "-c",
      "composer install --no-interaction --prefer-dist && php -r \"\"
    ]

  db:
    image: mariadb:10.7
    environment:
      - MYSQL_ROOT_PASSWORD=${DB_PASSWORD}
      - MYSQL_DATABASE=${DB_DATABASE}
    volumes:
      - db_data:/var/lib/mysql
    command: --default-authentication=mysql_native_password

volumes:
  db_data:
```

## Test Reports and Monitoring

### Test Reports

#### JUnit XML Reports
Generated by PHPUnit:
- `reports/junit.xml`
- `reports/coverage/clover.xml`

#### HTML Reports
- Use PHPUnit's HTML reporter: `--coverage-html=./reports/coverage/html`

### System Monitoring

#### System Status Logs
Generated by `./bin/system_status`:
- `storage/logs/system_status_YYYYMMDD_HHMMSS.json`

#### Performance Logs
Generated by `./bin/monitor_performance`:
- `storage/logs/performance_YYYYMMDD_HHMMSS.json`

#### Health Check Logs
Generated by `./storage/logs/health_check.sh`:
- `storage/logs/health_check.log`

## Configuration

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_ENV` | Application environment | `production` |
| `APP_DEBUG` | Debug mode | `false` |
| `APP_URL` | Application base URL | `http://localhost` |
| `DB_HOST` | Database host | `localhost` |
| `DB_DATABASE` | Database name | `bizprofile` |
| `DB_USERNAME` | Database username | `root` |
| `DB_PASSWORD` | Database password | `` |
| `TEST_DATABASE` | Test database name | `bizprofile_test` |
| `TEST_API_KEY` | Test API key | `test-api-key-for-integration-testing` |
| `API_RATE_LIMIT` | API rate limit | `100` |
| `API_RATE_WINDOW` | API rate window | `3600` |

### Test Configuration

#### Test Environment
```bash
export APP_ENV=test
export APP_DEBUG=true
export DB_DATABASE=bizprofile_test
export TEST_API_KEY=your-test-api-key
export TEST_PERFORMANCE_THRESHOLD=100
```

#### Production Environment
```bash
export APP_ENV=production
export APP_DEBUG=false
export DB_DATABASE=bizprofile
```

## Best Practices

### Writing Tests

1. **Test Naming**: Use descriptive test names that indicate the purpose
2. **Test Isolation**: Ensure tests don't interfere with each other
3. **Test Data**: Use fixtures for consistent test data
4. **Assertions**: Use appropriate assertions for different data types
5. **Error Handling**: Test error conditions and edge cases

### Test Maintenance

1. **Regular Runs**: Run tests regularly to catch regressions
2. **Test Coverage**: Maintain high test coverage
3. **Update Tests**: Update tests when code changes
4. **Clean Up**: Remove obsolete tests

### Performance Considerations

1. **Database Optimization**: Optimize database queries
2. **Caching**: Use caching for expensive operations
3. **Resource Management**: Monitor resource usage
4. **Load Testing**: Test with realistic load

## Troubleshooting

### Common Issues

#### Test Database Not Found
**Solution**: Ensure the test database exists and is accessible.

#### API Endpoints Not Responding
**Solution**: Check that the application is running and the API endpoints are correctly configured.

#### Performance Tests Failing
**Solution**: Check the performance thresholds and adjust if necessary.

#### System Status Not Available
**Solution**: Check that the SystemStatus class is properly configured and the database is accessible.

### Debugging

#### Enable Debug Mode
```bash
export APP_DEBUG=true
```

#### Check Logs
```bash
tail -f storage/logs/*.log
```

#### Manual Testing
```bash
# Test database connection
mysql -u root -e "SELECT VERSION();"

# Test PHP syntax
php -l index.php

# Test API endpoint
curl http://localhost/api/v1/products
```

## Conclusion

The BizProfile testing harness provides a comprehensive testing infrastructure that ensures the reliability, performance, and security of the BizProfile framework. By implementing automated testing, monitoring, and performance validation, you can maintain high code quality and deliver a robust product to your users.

The testing harness is designed to be:
- **Comprehensive**: Covers all aspects of the application
- **Automated**: Minimizes manual testing effort
- **Integratable**: Works with existing CI/CD pipelines
- **Maintainable**: Easy to extend and modify
- **Documented**: Provides clear guidance for testing

Use this harness to ensure the quality and reliability of your BizProfile implementation.
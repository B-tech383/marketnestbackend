# MySQL Setup Instructions for Market Nest

Since MySQL is not available as a system package in Replit, you **must** use an external MySQL service to run this application in the Replit environment.

## ⚠️ External MySQL Required for Replit

This application is configured for MySQL-only and requires a MySQL database to function. In Replit, you must use an external MySQL service.

## Option 1: External MySQL Service (Required for Replit)

Recommended cloud MySQL services:
- **PlanetScale** (Free tier available, requires TLS but no CA file)
- **Railway** (Free tier available, auto-creates database)
- **AWS RDS MySQL** (Managed MySQL with full features)
- **Google Cloud SQL** (Enterprise-grade MySQL)
- **Azure Database for MySQL** (Microsoft cloud MySQL)

### Basic Configuration Steps:
1. Create a MySQL database on your chosen service
2. Set the following **required** environment variables in Replit:
   ```bash
   MYSQL_HOST=your-mysql-host
   MYSQL_DATABASE=ecommerce_db
   MYSQL_USER=your-username
   MYSQL_PASSWORD=your-password
   ```

3. Set optional environment variables as needed:
   ```bash
   MYSQL_PORT=3306                    # Default: 3306
   MYSQL_ALLOW_DB_CREATE=false       # Default: true (set false for external providers)
   MYSQL_SSL_VERIFY=true             # Default: true
   ```

### SSL Configuration (Provider-Specific):

**For PlanetScale:**
```bash
MYSQL_SSL_VERIFY=true
# No CA file needed - PlanetScale handles TLS automatically
```

**For providers requiring SSL certificates:**
```bash
MYSQL_SSL_CA=/path/to/ca-cert.pem
MYSQL_SSL_CERT=/path/to/client-cert.pem    # If client certs required
MYSQL_SSL_KEY=/path/to/client-key.pem      # If client certs required
MYSQL_SSL_VERIFY=true
```

**For local development (disable SSL):**
```bash
MYSQL_SSL_VERIFY=false
```

### Initialize Database:
```bash
php database/init_mysql.php
```

## Option 2: Local Development with XAMPP

For local development outside Replit:
1. Install XAMPP or similar MySQL server
2. Start MySQL service
3. No environment variables needed (uses localhost defaults)
4. Run: `php database/init_mysql.php`

## Environment Variable Validation

The application enforces strict validation:
- **If ANY MySQL environment variable is set**, ALL required variables must be provided
- **Required for external**: `MYSQL_HOST`, `MYSQL_DATABASE`, `MYSQL_USER`
- **Optional**: `MYSQL_PASSWORD`, `MYSQL_PORT`, SSL options
- **Auto-detection**: External providers (non-localhost) have CREATE DATABASE disabled by default

## Security Features

✅ **Secure credential handling**: Admin credentials displayed in console only (not saved to files)
✅ **SSL/TLS support**: Full SSL configuration for secure external connections
✅ **Environment validation**: Prevents misconfiguration errors
✅ **External provider compatibility**: No database creation attempts on managed services

## Database Schema

Complete e-commerce platform with:
- ✅ User management and role-based access control
- ✅ Product catalog with categories and vendors
- ✅ Shopping cart and order management
- ✅ Advanced shipment tracking system
- ✅ Commission and payment tracking
- ✅ Coupon and discount management
- ✅ Advertisement system

## Provider-Specific Setup Guides

### PlanetScale Setup:
1. Create database at planetscale.com
2. Get connection details from dashboard
3. Set environment variables (SSL handled automatically)
4. Run initialization

### Railway Setup:
1. Create MySQL service at railway.app
2. Database created automatically
3. Copy connection details to environment variables
4. Run initialization

### AWS RDS Setup:
1. Create RDS MySQL instance
2. Configure security groups for access
3. Set SSL options if required
4. Run initialization

## Troubleshooting

### Connection Errors
- ✅ **Environment validation**: Clear error messages if variables incomplete
- ✅ **SSL detection**: Automatic SSL handling for external providers
- ✅ **Database creation**: Disabled for external providers to prevent errors

### Initialization Errors
- ✅ **Per-statement validation**: Individual SQL statement error reporting
- ✅ **Schema compatibility**: MySQL-optimized schema with proper syntax
- ✅ **Sample data**: Proper role insertion using user_roles table

### Provider-Specific Issues
- **PlanetScale**: Requires TLS but no CA file needed
- **Railway**: Database auto-created, just set MYSQL_ALLOW_DB_CREATE=false
- **AWS RDS**: May require VPC configuration for external access

## Current Status

- ✅ **Database configuration**: MySQL-only with full SSL support
- ✅ **Security**: No file-based credential storage
- ✅ **Provider compatibility**: External service optimized
- ✅ **Environment validation**: Complete validation with clear errors
- ✅ **Initialization**: Robust error handling and validation

## Quick Start Checklist

1. ☐ **Choose MySQL provider** (PlanetScale recommended for free tier)
2. ☐ **Create database** on your chosen provider
3. ☐ **Set environment variables** in Replit (all required variables)
4. ☐ **Test connection**: `php database/init_mysql.php`
5. ☐ **Save admin credentials** shown during initialization
6. ☐ **Verify application** works in web preview

The application is now fully secured and ready for MySQL deployment! 🚀
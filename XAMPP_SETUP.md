# XAMPP Setup Instructions for Market Nest

This guide will help you set up Market Nest on your local XAMPP environment.

## Prerequisites

1. **XAMPP** installed on your machine
2. **Git** (to clone/pull from GitHub)

## Step 1: Setup XAMPP

1. Start XAMPP Control Panel
2. Start **Apache** and **MySQL** services
3. Click **Admin** next to MySQL to open phpMyAdmin

## Step 2: Database Setup

### Method 1: Using PHP Script (Recommended)
1. Place your Market Nest project in `xampp/htdocs/` directory
2. Open your browser and navigate to: `http://localhost/[your-project-folder]/database/init_mysql.php`
3. This will automatically:
   - Create the `ecommerce_db` database
   - Create all required tables
   - Insert sample data including admin user

### Method 2: Manual Setup via phpMyAdmin
1. In phpMyAdmin, create a new database called `ecommerce_db`
2. Select the database and go to "Import" tab
3. Choose the file: `database/schema.sql`
4. Click "Go" to execute

## Step 3: Database Configuration

The database is already configured for XAMPP in `config/database.php`:

```php
private $host = 'localhost';
private $db_name = 'ecommerce_db';
private $username = 'root';
private $password = '';
```

**Note:** If your XAMPP MySQL has a password, update the `$password` variable in `config/database.php`.

## Step 4: Test Your Setup

1. Navigate to: `http://localhost/[your-project-folder]/`
2. You should see the Market Nest homepage
3. Login with admin credentials:
   - **Username:** admin
   - **Password:** admin123

## Step 5: File Permissions

Ensure the following directories are writable:
- `uploads/products/`
- `uploads/vendor_logos/`

## Troubleshooting

### Common Issues:

1. **Database Connection Error:**
   - Ensure MySQL service is running in XAMPP
   - Check database credentials in `config/database.php`

2. **Missing Tables:**
   - Run the database initialization: `http://localhost/[your-project-folder]/database/init_mysql.php`

3. **Image Upload Issues:**
   - Check folder permissions for `uploads/` directory
   - Ensure folders exist and are writable

### Admin Access:
- Admin Panel: `http://localhost/[your-project-folder]/admin/`
- Default admin login: `admin` / `admin123`

## Features Available:

✅ **User Management:** Customer registration and login  
✅ **Vendor System:** Vendor applications and product management  
✅ **Product Catalog:** Categories, products, and inventory  
✅ **Shopping Cart:** Session-based cart management  
✅ **Order Processing:** Complete order lifecycle  
✅ **Tracking System:** Advanced shipment tracking  
✅ **Admin Panel:** Complete administrative interface  
✅ **Modern UI:** Professional Amazon-style design  

## Support

If you encounter any issues, check the error logs in:
- XAMPP Control Panel → Apache → Logs
- Browser Developer Tools → Console

The application is fully configured for MySQL and ready for development!
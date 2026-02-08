# Installation Guide - JemAll Marketplace

## Quick Start (5 Minutes)

### Step 1: Start XAMPP
1. Open XAMPP Control Panel
2. Start **Apache** service
3. Start **MySQL** service

### Step 2: Import Database
1. Open your PostgreSQL administration tool (e.g., pgAdmin or psql)
2. Create a database named `jemall_db`
3. Import the `database_pg.sql` file into this database
4. Ensure your PostgreSQL service is running on port 5432

### Step 3: Access Application
1. Open browser: http://localhost/PFE/JemALL/
2. You should see the homepage

### Step 4: Test Login
Use these demo accounts:

**Admin:**
- Username: `admin`
- Password: `admin123`
- URL: http://localhost/PFE/JemALL/admin/dashboard.php

**Seller:**
- Username: `seller1`
- Password: `seller123`
- URL: http://localhost/PFE/JemALL/seller/dashboard.php

**Buyer:**
- Username: `buyer1`
- Password: `buyer123`
- URL: http://localhost/PFE/JemALL/

## Troubleshooting

### "Database connection failed"
- Check if MySQL is running in XAMPP
- Verify database name is `jemall_db`
- Check `config/database.php` settings

### "Page not found" or 404 Error
- Verify Apache is running
- Check URL: http://localhost/PFE/JemALL/
- Ensure files are in correct directory

### Images not displaying
- Check `uploads/products/` folder exists
- Verify folder has write permissions
- Sample products may not have images (this is normal)

### Can't upload images
- Check `uploads/products/` folder permissions
- Verify PHP upload settings in php.ini
- Max upload size should be at least 5MB

## File Structure Verification

Ensure these folders exist:
```
JemALL/
├── admin/
├── seller/
├── buyer/
├── config/
├── includes/
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
└── uploads/
    └── products/
```

## Next Steps After Installation

1. **Test Admin Features:**
   - Login as admin
   - Approve pending sellers
   - Approve pending products
   - Add/edit categories

2. **Test Seller Features:**
   - Login as seller
   - Add new products
   - Upload product images
   - View orders

3. **Test Buyer Features:**
   - Login as buyer
   - Browse products
   - Add to cart
   - Place order

## Default Database Settings

If you need to change database settings, edit `config/database.php`:

```php
define('DB_HOST', 'localhost');  // Usually 'localhost'
define('DB_PORT', '5432');       // PostgreSQL default port
define('DB_NAME', 'jemall_db');  // Database name
define('DB_USER', 'postgres');   // PostgreSQL user
define('DB_PASS', 'yahya12345'); // Your password
```

## Support

If you encounter issues:
1. Check XAMPP error logs
2. Check PHP error logs
3. Verify all services are running
4. Clear browser cache and cookies

---

**Ready to use!** The application is now fully functional.

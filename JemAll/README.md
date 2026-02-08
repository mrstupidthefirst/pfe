# JemAll Marketplace

A complete marketplace web application built with PHP 8, MySQL, HTML, CSS, and JavaScript for a PFE (Final Year Project).

## Features

### User Roles

1. **Admin**
   - Manage sellers (approve/reject accounts)
   - Approve/reject products
   - Manage product categories
   - View system statistics

2. **Seller**
   - Register and wait for admin approval
   - Add, edit, and delete products
   - Upload product images
   - View orders for their products
   - Track product status (pending/approved/rejected)

3. **Buyer**
   - Browse and search products
   - Filter by categories
   - Add products to cart
   - Checkout and place orders
   - View order history

## Technology Stack

- **Backend**: PHP 8 (No framework, pure PHP)
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript
- **Server**: XAMPP (Apache + MySQL)

## Installation & Setup

### Prerequisites

- XAMPP installed and running
- PHP 8.x
- MySQL 5.7+ or MariaDB 10.3+

### Step 1: Clone/Download Project

Place the project in your XAMPP htdocs directory:
```
C:\xampp\htdocs\PFE\JemALL
```

### Step 2: Database Setup

1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Import the `database.sql` file:
   - Click on "Import" tab
   - Choose file: `database.sql`
   - Click "Go"
3. The database `jemall_db` will be created with all tables and sample data

### Step 3: Configure Database Connection

Edit `config/database.php` if needed (default XAMPP settings should work):
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'jemall_db');
define('DB_USER', 'root');
define('DB_PASS', '');  // Default XAMPP password is empty
```

### Step 4: Set Permissions

Ensure the `uploads/products/` directory is writable:
- On Windows: Usually writable by default
- On Linux/Mac: `chmod 777 uploads/products/`

### Step 5: Access the Application

1. Start XAMPP (Apache and MySQL)
2. Open browser and navigate to:
   ```
   http://localhost/PFE/JemALL/
   ```

## Demo Accounts

The database includes sample accounts for testing:

### Admin
- **Username**: `admin`
- **Password**: `admin123`
- **URL**: http://localhost/PFE/JemALL/admin/dashboard.php

### Seller
- **Username**: `seller1`
- **Password**: `seller123`
- **URL**: http://localhost/PFE/JemALL/seller/dashboard.php

### Buyer
- **Username**: `buyer1`
- **Password**: `buyer123`
- **URL**: http://localhost/PFE/JemALL/

## Project Structure

```
JemALL/
├── admin/              # Admin dashboard pages
│   ├── dashboard.php
│   ├── sellers.php
│   ├── products.php
│   └── categories.php
├── seller/             # Seller dashboard pages
│   ├── dashboard.php
│   ├── products.php
│   ├── add_product.php
│   ├── edit_product.php
│   ├── orders.php
│   └── order_details.php
├── buyer/              # Buyer interface pages
│   ├── product.php
│   ├── cart.php
│   ├── checkout.php
│   ├── orders.php
│   └── order_details.php
├── config/             # Configuration files
│   ├── config.php
│   └── database.php
├── includes/           # Reusable components
│   ├── header.php
│   └── footer.php
├── assets/             # Static assets
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── main.js
│   └── images/
│       └── placeholder.jpg
├── uploads/            # User uploaded files
│   └── products/        # Product images
├── database.sql        # Database schema and sample data
├── index.php           # Home page
├── login.php           # Login page
├── register.php        # Registration page
├── logout.php          # Logout handler
└── README.md           # This file
```

## Security Features

- **Password Hashing**: Uses PHP `password_hash()` with bcrypt
- **PDO Prepared Statements**: Prevents SQL injection
- **Session Management**: Secure session handling
- **Input Validation**: Server-side validation for all inputs
- **File Upload Security**: Type and size validation for images
- **Role-Based Access Control**: Pages protected by role requirements
- **XSS Protection**: Output escaping with `htmlspecialchars()`

## Database Schema

### Tables

- **users**: User accounts (admin, seller, buyer)
- **categories**: Product categories
- **products**: Product listings
- **cart**: Shopping cart items
- **orders**: Order records
- **order_items**: Order line items

## Features in Detail

### Authentication System
- User registration with role selection
- Secure login with password verification
- Session-based authentication
- Role-based redirects after login

### Admin Features
- Dashboard with statistics
- Approve/reject seller accounts
- Approve/reject products
- CRUD operations for categories

### Seller Features
- Product management (CRUD)
- Image upload for products
- View orders for their products
- Product status tracking

### Buyer Features
- Product browsing and search
- Category filtering
- Shopping cart management
- Order placement and tracking

## Development Notes

- All code includes comments for clarity
- Follows PSR-12 coding standards where applicable
- Responsive design for mobile devices
- Clean separation of concerns
- Reusable components (header, footer)

## Troubleshooting

### Database Connection Error
- Check if MySQL is running in XAMPP
- Verify database credentials in `config/database.php`
- Ensure database `jemall_db` exists

### Image Upload Not Working
- Check `uploads/products/` directory permissions
- Verify directory exists
- Check PHP `upload_max_filesize` and `post_max_size` settings

### Session Issues
- Ensure `session_start()` is called before any output
- Check PHP session configuration
- Clear browser cookies if needed

### Page Not Found (404)
- Verify XAMPP Apache is running
- Check file paths match the URL structure
- Ensure `.htaccess` is not blocking access (if used)

## Future Enhancements

Potential improvements for the project:
- Payment gateway integration
- Email notifications
- Product reviews and ratings
- Advanced search with filters
- Wishlist functionality
- Seller analytics dashboard
- Order status updates
- Product image gallery
- Admin order management

## License

This project is created for educational purposes as part of a PFE (Final Year Project).

## Author

Created for PFE Project - JemAll Marketplace

## Support

For issues or questions, please check:
1. Database connection settings
2. File permissions
3. XAMPP service status
4. PHP error logs in XAMPP

---

**Note**: This is a demonstration project. For production use, additional security measures and optimizations would be required.

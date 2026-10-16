SARI-SARI STORE MANAGEMENT SYSTEM
=================================

TECHNOLOGY
- PHP 8+
- MySQL / MariaDB
- MySQLi
- HTML, CSS, JavaScript
- Bootstrap is not required; this project uses custom responsive CSS.

INSTALLATION ON XAMPP
1. Copy the whole SariSariStoreManagementSystem folder into:
   C:\xampp\htdocs\
2. Start Apache and MySQL in XAMPP Control Panel.
3. Open phpMyAdmin: http://localhost/phpmyadmin
4. Import database/sari_sari_store.sql.
5. Check config/database.php:
   DB_HOST = localhost
   DB_USER = root
   DB_PASS = (usually blank in a default local XAMPP install)
   DB_NAME = sari_sari_store
   BASE_URL = http://localhost/SariSariStoreManagementSystem
6. Open:
   http://localhost/SariSariStoreManagementSystem/
7. Register an account. Public registration assigns the cashier role only.
8. Configure email delivery before demonstrating actual email verification.
9. After verifying the first account, assign the admin role through phpMyAdmin:
   UPDATE users SET role='admin' WHERE email='YOUR_REGISTERED_EMAIL';
   Do not use this as a public production procedure. It is only for local first-admin setup.
10. Log in again. From Admin > Users, assign a second account to staff and a third to cashier.

EMAIL VERIFICATION
Email verification uses PHPMailer with SMTP. Configure these environment variables for Apache/PHP:
MAIL_HOST, MAIL_PORT (usually 587), MAIL_USERNAME, MAIL_PASSWORD, and MAIL_FROM. For Gmail, use
smtp.gmail.com and a Gmail App Password. Restart Apache after changing its environment configuration.
Keep real SMTP credentials outside the submitted ZIP. Verification links are random, stored as SHA-256
hashes, expire after 30 minutes, and cannot be reused. Test delivery using a real inbox before the demo.

DEMO DATA
The SQL file seeds categories, a sample supplier, and sample products.
It intentionally does not seed users with known passwords. Create users via registration so passwords
are stored with password_hash() and accounts use the email verification workflow.

FEATURES INCLUDED
- Registration, verification link flow, resend verification, login/logout and session regeneration
- Admin, staff, cashier roles and backend access checks
- Product CRUD (archive instead of deleting historical product references)
- Category CRUD and supplier management
- Inventory movements and low-stock indicators
- POS cart, payment validation, stock locking, database transaction, receipt
- Restocking requests with approval/rejection/receive workflow
- Date-filtered reports, best-selling products, printable views
- Activity logs
- Queue, stack, and associative-array product lookup data structure examples
- CSRF tokens, prepared statements, output escaping, password hashing

IMPORTANT LIMITATIONS / BEFORE DEFENSE
- This is a starter implementation and should be tested locally before submission.
- Email sending depends on the machine's mail/SMTP configuration and must be tested using a real inbox.
- For production use, add rate limiting, password reset, stronger audit protections, backup procedures,
  and a dedicated SMTP configuration through environment variables.
- The UI and workflows are implemented, but test every page after importing the SQL on your PHP/MySQL version.
- Do not include real email passwords or SMTP credentials in the final ZIP.

DEMO ACCOUNT CREDENTIALS
Create a file named demo_credentials.txt after setting up the app. Include the demo email and passwords
for all three roles that you created yourself. Do not write real email account passwords here.

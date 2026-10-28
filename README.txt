AC STORE MANAGEMENT SYSTEM
=================================

PROJECT OVERVIEW
----------------
A PHP and MySQL web application for managing AC Store. It supports
account registration, email verification, role-based access, product and stock
management, sales checkout, restocking requests, reports, and activity logs.

TECHNOLOGY
----------
- PHP 8+ with MySQLi and prepared statements
- MySQL or MariaDB (InnoDB tables and transactions)
- HTML, CSS, and vanilla JavaScript
- PHPMailer for SMTP email
- Composer for the PHPMailer dependency

INSTALLATION (XAMPP)
--------------------
1. Put the project folder in C:\xampp\htdocs\SariSariStoreManagementSystem.
2. Open XAMPP Control Panel and start Apache and MySQL.
3. Open http://localhost/phpmyadmin.
4. Import database/sari_sari_store.sql. The script creates the database and
   tables, then adds sample categories, a supplier, and products. Importing it
   again drops and recreates those tables, so it deletes existing app data.
5. Review config/database.php. The default local settings are host localhost,
   user root, blank password, database sari_sari_store, and BASE_URL
   http://localhost/SariSariStoreManagementSystem. Change them if your XAMPP
   settings or folder name differ.
6. Open http://localhost/SariSariStoreManagementSystem/.
7. Configure SMTP as described below before registering an account that needs
   email verification.
8. Register your first account. Public registration assigns the cashier role,
   and new accounts start unverified and inactive.
9. Verify the email, then promote the first account to admin using phpMyAdmin:

   UPDATE users SET role = 'admin', is_active = 1
   WHERE email = 'your-email@example.com';

   Use the email address you registered. This SQL step is for local initial
   setup only; do not use public registration or manual database edits as a
   production administrator provisioning process.
10. Sign in again. From Admin > User accounts, an admin can change account
    roles and verified/active status. Create or register the accounts needed
    for staff and cashier demonstrations.

SMTP EMAIL SETUP
----------------
The application uses PHPMailer over SMTP. Registration and resend-verification
messages call send_verification_email() in includes/mail.php. The current code
loads MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM, and
ADMIN_EMAIL constants from includes/mail_config.php. Configure those values for
your mail provider; the default mail transport uses STARTTLS, typically on port
587. For Gmail, use smtp.gmail.com and an App Password, not your normal account
password.

Keep mail credentials private. Do not put real credentials in README files,
source control, screenshots, or submitted project archives. If credentials
have already been pushed to a remote repository, revoke or rotate them. For a
shared or production deployment, load secrets from server environment variables
or a secrets manager instead of storing them in a PHP source file.

Email verification workflow:
1. Registration validates the name, email, and minimum password length (8).
2. The password is hashed with password_hash(). A cashier account is created
   with is_verified=0 and is_active=0.
3. A random 64-character hexadecimal token is created. Only its SHA-256 hash
   is saved in email_verifications; the raw token is sent in the email link.
4. The link expires after 30 minutes. Opening it locks the verification row,
   checks the hash, expiry, and used status, then verifies the account and marks
   the token used. A link cannot be reused.
5. Email verification does not activate an account. An administrator must set
   the account active before it can sign in.
6. Resend verification creates a new token for an eligible unverified account.
   Requests for unknown/already verified accounts use a generic response.
   Requests for an inactive account may send an administrative notice to
   ADMIN_EMAIL; that notice does not activate the account.

MAIN USER WORKFLOW
------------------
1. A user registers and verifies their email. The admin reviews the account,
   assigns its role (admin, staff, or cashier), and activates it.
2. On login, the app checks the password hash, verified flag, and active flag.
   It regenerates the PHP session ID, stores the current user's basic details
   in the session, logs the login, and opens the dashboard.
3. The dashboard shows today's completed sales, active product count, low-stock
   count, pending restock count, quick actions, and a low-stock watchlist.
4. Admin manages products, categories, suppliers, users, and activity logs.
   Products can be archived or reactivated to preserve historical sale links.
   Admin and staff can manage inventory; permitted users can submit restock
   requests.
5. In Inventory, an authorized user records stock movements/adjustments. Stock
   changes are associated with a user and recorded in stock_movements.
6. In Restocking, an eligible user submits a request with a product, quantity,
   optional supplier, and notes. Admin reviews pending requests and approves or
   rejects them. Admin or staff marks an approved delivery as received. The
   receive operation adds the requested quantities to inventory, records stock
   movements, and marks the request received as one database transaction.
7. In POS, the cashier builds a cart, enters the amount received, and sees the
   total and change. Checkout rechecks product availability and stock on the
   server. It locks product rows, verifies quantities and payment, saves the
   sale and sale items, deducts stock, records stock movements and an activity
   log, then commits the transaction. If any step fails, the transaction rolls
   back and the cashier is returned to the POS. A completed sale opens its
   receipt page for viewing or printing.
8. Reports can be filtered by date range and show completed sales and
   best-selling products. Activity logs let admins review recent recorded
   actions. Logging out clears the session.

ROLES AND ACCESS
----------------
- Admin: user and role management, products, categories, suppliers, activity
  logs, inventory, restocking review/receipt, POS, and reports.
- Staff: inventory operations, restocking requests and receiving approved stock,
  POS, and reports. Admin-only actions are rejected by server-side role checks.
- Cashier: POS, reports, and their own restocking requests/history.

MAIN PAGES
----------
- index.php: public welcome page; signed-in users go to the dashboard.
- dashboard.php: store summary and quick actions.
- auth/: registration, login, email verification/resend, and logout.
- admin/products.php: product create/edit/search/archive/reactivate.
- admin/categories.php: category create/edit/delete.
- admin/suppliers.php: supplier management.
- admin/users.php: role, verification, and active-account management.
- admin/logs.php: latest activity log records.
- staff/inventory.php: stock lookup and inventory adjustments.
- staff/restocking.php: request, review, receive, and request history.
- cashier/pos.php: product lookup/cart and payment entry.
- cashier/checkout.php: validates and saves a sale.
- cashier/receipt.php: displays a completed sale receipt.
- reports/index.php: date-filtered sales and top-product reports.

SHARED PHP FUNCTIONS (config/database.php)
------------------------------------------
- e(?string $value): HTML-escapes a value for safe output.
- redirect(string $path): sends the browser to a BASE_URL-relative page and
  exits the current request.
- csrf_token(): creates or returns a random session CSRF token.
- verify_csrf(): compares a submitted form token with the session token and
  rejects invalid requests with HTTP 403.
- flash(string $type, string $message): stores a one-time message in session.
- get_flash(): returns and removes that one-time message.

The same file sets the Manila timezone, starts a hardened session cookie,
configures strict MySQLi errors, connects to MySQL, selects utf8mb4, and defines
application/database constants.

SHARED PAGE AND ACCESS FUNCTIONS (includes/functions.php)
---------------------------------------------------------
- current_user(): returns the signed-in user's session data, or null.
- require_login(): redirects unauthenticated visitors to sign in.
- require_role(array $roles): requires login and checks the user's role; denies
  access with HTTP 403 if their role is not allowed.
- log_activity(...): inserts an action, user, record type/id, details, and IP
  address into activity_logs.
- money(float $n): formats a number as Philippine peso currency.
- csrf_field(): outputs the hidden CSRF input used by POST forms.
- layout_header(string $title): outputs the document header, navigation, flash
  message, page title, and shared stylesheet/script links.
- layout_footer(): closes the shared page layout and outputs the footer.

EMAIL FUNCTIONS (includes/mail.php)
-----------------------------------
- send_verification_email($email, $name, $token): builds an HTML and plain-text
  verification message and sends it through PHPMailer SMTP. It returns true on
  success and false on failure; server-side errors are written to the PHP error
  log rather than displayed with SMTP credentials to the user.
- send_admin_inactive_account_notice($userEmail, $userName): sends an inactive
  account assistance notice to ADMIN_EMAIL. It does not change account status.

DATA STRUCTURE EXAMPLES (includes/classes.php)
----------------------------------------------
- RestockQueue: FIFO queue. enqueue() adds a request, dequeue() removes the
  oldest request, isEmpty() checks for pending items, and all() returns them.
  The restocking page loads pending requests in oldest-first order for display.
- StockAdjustmentStack: LIFO stack. push() adds an adjustment, pop() removes
  the newest, peek() reads the newest, and all() returns the stored adjustments
  newest first. This is a data-structure example for adjustment review.
- ProductLookup: associative lookup keyed by product_code. add() stores a
  product and find() returns the product for a code or null when not found.

FRONT-END JAVASCRIPT (assets/js/app.js)
--------------------------------------
- Mobile navigation: toggles the menu open/closed, closes it when a link is
  selected, and supports Escape to close.
- POS cart: adds products, validates quantity against available stock, removes
  items, recalculates totals/change, serializes cart data for checkout, and
  disables checkout when the cart is empty or payment is insufficient.
- Product lookup: searches the lookup data provided by the POS page and shows
  the matching product's stock and reorder level.

DATABASE TABLES
---------------
- users: account, password hash, role, verified status, and active status.
- email_verifications: hashed one-time verification tokens and expiry/use data.
- categories and suppliers: product groupings and supplier contact details.
- products: code, category, cost/selling prices, stock, reorder level, status.
- sales and sale_items: completed/voided transactions and item-level sale data.
- restock_requests and restock_items: request status and requested quantities.
- stock_movements: stock in/out/adjustment/sale/restock audit records.
- activity_logs: user actions for admin review.

SECURITY AND DATA HANDLING
--------------------------
- Passwords use password_hash() and password_verify(); raw passwords are not
  stored in the database.
- Database writes use MySQLi prepared statements in the main workflows.
- POST forms use session-backed CSRF tokens.
- Output is escaped with e() or htmlspecialchars() where it is rendered.
- Login checks active and verified status, regenerates the session ID, and
  role checks run on the server.
- POS and stock receiving use database transactions; POS product rows are
  locked while stock is checked and deducted.
- Product archive preserves links to historical sales and stock records.

DEMO DATA AND ACCOUNTS
----------------------
The SQL import creates sample categories, one sample supplier, and sample
products. It does not create demo users or known passwords. Register accounts
through the app and use the local first-admin setup steps above. Keep any demo
account list in a separate private file and do not use real personal passwords.

TROUBLESHOOTING
---------------
- Database connection error: confirm MySQL is running, import the SQL file, and
  check DB_HOST, DB_USER, DB_PASS, DB_NAME, and BASE_URL in config/database.php.
- CSS or JavaScript changes do not appear: refresh the page without using the
  browser cache (Ctrl+F5).
- Verification email not received: check includes/mail_config.php, SMTP host,
  port, username, App Password, sender address, internet access, spam folder,
  Apache/PHP error log, and the recipient address.
- Login rejected after verification: the account also needs is_active=1; ask an
  admin to activate it and assign the correct role.

PROJECT LIMITATIONS
-------------------
This is a local/demo management system. Before using it for a real store,
review deployment security, protect SMTP and database credentials, use HTTPS,
configure backups and recovery, add abuse/rate limits, and test email delivery
and transaction workflows in the actual hosting environment.

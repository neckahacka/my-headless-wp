
My Local WordPress Development Setup ??
Last Updated: January 17, 2025

## Quick Start Guide ?????

### To Start Working:
1. Open XAMPP Control Panel
2. Click "Start" for Apache (web server)
3. Click "Start" for MySQL (database)
4. Go to: http://localhost/my-headless-wp/wordpress/wp-admin
5. Log in with: [UPDATED Feb 14, 2025]
   - Username: XXX
   - Email: amynztoday@gmail.com
   - Password: [your password]

### Where to Find Things ??
Everything is in the D: drive:
D:\xampp\htdocs\my-headless-wp\wordpress
 +-- wp-config.php (main settings file) +-- wp-admin\ (dashboard files) +-- wp-content\ (themes, plugins, uploads) +-- wp-includes\ (WordPress core files)
Copy

## Database Information ??
- Name: headless_wp
- Username: root
- Password: (blank/empty)
- Where to manage it: http://localhost/phpmyadmin

## What We Did Today (Jan 17, 2025) ?
1. Set up XAMPP:
   - Installed on D: drive
   - Started Apache and MySQL servers
   - Verified everything works

2. Created WordPress:
   - Made folder: my-headless-wp
   - Downloaded WordPress
   - Set up fresh installation

3. Connected to Database:
   - Created database called 'headless_wp'
   - Set up wp-config.php
   - Got WordPress running

## Common Issues & Fixes ??

### If WordPress Won't Load:
1. Check XAMPP Control Panel:
   - Is Apache running? (should be green)
   - Is MySQL running? (should be green)
   - If not, click "Start" for each

### If Database Won't Connect:
1. Check wp-config.php has:
```php
define( 'DB_NAME', 'headless_wp' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', 'localhost' );

Important Links ??
WordPress Dashboard: http://localhost/my-headless-wp/wordpress/wp-admin
Database Manager: http://localhost/phpmyadmin
XAMPP Control Panel: Look for orange XAMPP icon in system tray

Future Plans ??
Set up React/Vue frontend
Connect WordPress as backend
Create modern, fast-loading website

Need Help? ??
Check XAMPP is running (both Apache and MySQL should be green)
Make sure you're using the correct URL
Verify database connection in wp-config.php
Look at XAMPP logs if something's wrong

Good to Remember ??
This is your LOCAL site (only on your computer)

Changes here won't affect your live website
Always keep your password and wp-config.php safe

Make backups before big changes

Created by Amy on January 17, 2025 For: Headless WordPress Development Project


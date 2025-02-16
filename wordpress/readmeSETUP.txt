My Local WordPress Development Setup
Last Updated: February 14, 2025

🚀 Quick Start Guide
To Start Working Locally:
Open XAMPP Control Panel
Click "Start" for Apache (web server)
Click "Start" for MySQL (database)

[#[Local]#]
Open your browser and go to:
 👉 http://localhost/my-headless-wp/wordpress/wp-admin
Log in with:
Username: (@amy*) *?
Email:admin@hashcats.com
Password: 666


[[LIVE]]
👉http://hashcats.com/wp-admin
 Username: *?
 Email:amynztoday@gmail.com
 Password: 666111]

📂 Where to Find Things (Local Development)
📍 Everything is stored in D: drive:
D:\xampp\htdocs\my-headless-wp\wordpress
 ├── wp-config.php  (Main settings file)
 ├── wp-admin\      (Dashboard files)
 ├── wp-content\    (Themes, plugins, uploads)
 ├── wp-includes\   (WordPress core files)


🗄️ Database Information (Local)
Database Name: headless_wp
Database Username: root
Database Password: (blank/empty)
Database Manager:
 👉 http://localhost/phpmyadmin

📌 What We Did Today (February 14, 2025)
✅ Migrated WordPress to Hostinger
Uploaded WordPress Files to Hostinger public_html
Updated wp-config.php with Hostinger’s database credentials
Imported Database from XAMPP to Hostinger’s phpMyAdmin
Fixed .htaccess & File Permissions
Tested & Successfully Accessed Admin Panel
✅ Local Development Remains Active
Continue working in VS Code (D:\xampp\htdocs\my-headless-wp\frontend)
External hard drive is for backups only, not active development.

❌ Common Issues & Fixes
🛑 If WordPress Won't Load:
Check XAMPP Control Panel:


Is Apache running? (should be green)
Is MySQL running? (should be green)
If not, click Start for each service.
Check Your Local URL:
 👉 http://localhost/my-headless-wp/wordpress/wp-admin


🛑 If Database Won't Connect:
Open wp-config.php and ensure it contains:
define( 'DB_NAME', 'headless_wp' );
 define( 'DB_USER', 'root' );
 define( 'DB_PASSWORD', '' );
 define( 'DB_HOST', 'localhost' );


🔗 Important Links
Local WordPress Dashboard:
 👉 http://localhost/my-headless-wp/wordpress/wp-admin
Local Database Manager (phpMyAdmin):
 👉 http://localhost/phpmyadmin
Hostinger WordPress Dashboard:
 👉 https://your-live-site.com/wp-admin/
XAMPP Control Panel:
 Look for orange XAMPP icon in system tray

🚀 Future Plans
Set up React/Vue frontend with the WordPress REST API
Implement JWT Authentication for secure API access
Develop modern, fast-loading frontend integrated with WordPress backend
Test API calls locally & sync with Hostinger

❗ Need Help?
Check if XAMPP is running (both Apache & MySQL should be green)
Make sure you're using the correct local URL
Verify database connection settings in wp-config.php
Look at XAMPP logs if something seems wrong

📌 Good to Remember
This is your LOCAL development site (only on your computer)
Changes here won’t affect the live Hostinger website
Always back up before making big changes
Keep your password & wp-config.php safe

📌 Created by Amy on January 17, 2025
 📍 Updated: February 14, 2025 – Migration to Hostinger Completed 🎉

Installation Instructions
-------------------------
1. Create DB pearlygirl and import the sql file in pearly-girl.com/extras/db
2. Clone this repository


Post-Installation Notes
-----------------------
It is recommended to follow the following post-installation steps to secure your osCommerce Online Merchant online store:

1. Delete the /Users/Hect1c/Sites/pearly-girl.com/catalog/install directory.
2. Rename the Administration Tool directory located at /Users/Hect1c/Sites/pearly-girl.com/catalog/admin.
3. Set the permissions on /Users/Hect1c/Sites/pearly-girl.com/catalog/includes/configure.php to 644 (or 444 if this file is still writable).
4. Set the permissions on /Users/Hect1c/Sites/pearly-girl.com/catalog/admin/includes/configure.php to 644 (or 444 if this file is still writable).
5. Review the directory permissions on the Administration Tool -> Tools -> Security Directory Permissions page.
6. The Administration Tool should be further protected using htaccess/htpasswd and can be set-up within the Configuration -> Administrators page.
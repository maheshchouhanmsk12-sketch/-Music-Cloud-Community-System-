#!/bin/bash
set -e

PORT="${PORT:-80}"

# Start local MariaDB server as fail-safe fallback
echo "Starting local MariaDB service..."
service mariadb start || /etc/init.d/mariadb start || true
mysql -e "CREATE DATABASE IF NOT EXISTS music_db;" 2>/dev/null || true
mysql -e "CREATE DATABASE IF NOT EXISTS defaultdb;" 2>/dev/null || true
if [ -f /var/www/html/database/music_db.sql ]; then
    mysql music_db < /var/www/html/database/music_db.sql 2>/dev/null || true
fi

echo "Configuring Apache to listen on port ${PORT}..."
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf

# Enable AllowOverride All in apache2.conf so .htaccess rules are executed
sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Global Apache reverse proxy settings - Prevent port 10000 leak in redirects
echo "UseCanonicalName Off" >> /etc/apache2/apache2.conf
echo "UseCanonicalPhysicalPort Off" >> /etc/apache2/apache2.conf
echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Configure VirtualHost for Render
cat <<EOF > /etc/apache2/sites-available/000-default.conf
<VirtualHost *:${PORT}>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html
    ServerName localhost
    UseCanonicalName Off
    UseCanonicalPhysicalPort Off

    <Directory /var/www/html>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        DirectoryIndex index.php index.html
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

# Ensure upload directory has write permissions
mkdir -p /var/www/html/assets/uploads
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html
chmod -R 775 /var/www/html/assets/uploads

echo "Starting Apache Web Server on port ${PORT}..."
exec apache2-foreground

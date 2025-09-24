# Guía de Instalación - Jaguata

Esta guía te ayudará a instalar y configurar Jaguata en tu entorno de desarrollo o producción.

## Requisitos del Sistema

### Requisitos Mínimos
- **PHP**: 7.4 o superior
- **MySQL**: 5.7 o superior (o MariaDB 10.2+)
- **Apache**: 2.4 o superior (o Nginx 1.18+)
- **Espacio en disco**: 100MB mínimo
- **RAM**: 512MB mínimo

### Extensiones PHP Requeridas
- `pdo`
- `pdo_mysql`
- `json`
- `mbstring`
- `curl`
- `gd`
- `fileinfo`
- `zip`

### Requisitos Recomendados
- **PHP**: 8.0 o superior
- **MySQL**: 8.0 o superior
- **RAM**: 1GB o superior
- **Espacio en disco**: 500MB o superior

## Instalación

### 1. Clonar el Repositorio

```bash
git clone https://github.com/tu-usuario/jaguata.git
cd jaguata
```

### 2. Configurar el Entorno

```bash
# Copiar archivo de configuración
cp config.env .env

# Editar configuración
nano .env
```

### 3. Configurar Base de Datos

```sql
-- Crear base de datos
CREATE DATABASE jaguata CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Crear usuario (opcional)
CREATE USER 'jaguata_user'@'localhost' IDENTIFIED BY 'tu_password_segura';
GRANT ALL PRIVILEGES ON jaguata.* TO 'jaguata_user'@'localhost';
FLUSH PRIVILEGES;
```

### 4. Importar Esquema de Base de Datos

```bash
# Importar esquema
mysql -u root -p jaguata < database/schema.sql

# Importar datos de prueba (opcional)
mysql -u root -p jaguata < database/sample_data.sql
```

### 5. Configurar Permisos

```bash
# Dar permisos de escritura
chmod -R 755 assets/uploads/
chmod -R 755 cache/
chmod -R 755 logs/

# En sistemas Unix/Linux
sudo chown -R www-data:www-data assets/uploads/
sudo chown -R www-data:www-data cache/
sudo chown -R www-data:www-data logs/
```

### 6. Configurar Servidor Web

#### Apache

Crear archivo `.htaccess` en la raíz del proyecto:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ public/$1 [QSA,L]
```

#### Nginx

```nginx
server {
    listen 80;
    server_name jaguata.local;
    root /path/to/jaguata/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## Configuración

### Variables de Entorno

Edita el archivo `.env` con tus configuraciones:

```env
# Base de datos
DB_HOST=localhost
DB_DATABASE=jaguata
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password

# Aplicación
APP_URL=http://localhost/jaguata
APP_DEBUG=true

# Email
MAIL_HOST=smtp.gmail.com
MAIL_USERNAME=tu_email@gmail.com
MAIL_PASSWORD=tu_app_password
```

### Configuración de Email

Para Gmail:
1. Habilitar autenticación de 2 factores
2. Generar contraseña de aplicación
3. Usar la contraseña de aplicación en `MAIL_PASSWORD`

### Configuración de Archivos

```bash
# Crear directorios necesarios
mkdir -p assets/uploads/perfiles
mkdir -p assets/uploads/mascotas
mkdir -p cache
mkdir -p logs
mkdir -p backups
```

## Verificación de la Instalación

### 1. Verificar PHP

```bash
php -v
php -m | grep -E "(pdo|json|mbstring|curl|gd|fileinfo|zip)"
```

### 2. Verificar Base de Datos

```sql
USE jaguata;
SHOW TABLES;
```

### 3. Verificar Aplicación

1. Abrir navegador en `http://localhost/jaguata`
2. Verificar que la página principal carga correctamente
3. Probar registro de usuario
4. Probar login

## Solución de Problemas

### Error de Conexión a Base de Datos

```bash
# Verificar que MySQL esté ejecutándose
sudo systemctl status mysql

# Verificar credenciales
mysql -u tu_usuario -p jaguata
```

### Error de Permisos

```bash
# Verificar permisos
ls -la assets/uploads/
ls -la cache/
ls -la logs/

# Corregir permisos
chmod -R 755 assets/uploads/
chmod -R 755 cache/
chmod -R 755 logs/
```

### Error 500

1. Verificar logs de Apache/Nginx
2. Verificar logs de PHP
3. Verificar configuración de `.htaccess`
4. Verificar permisos de archivos

### Error de Memoria

```bash
# Aumentar memoria de PHP
echo "memory_limit = 256M" >> /etc/php/8.0/apache2/php.ini
sudo systemctl restart apache2
```

## Desarrollo

### Instalar Dependencias

```bash
# Instalar Composer (si no está instalado)
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Instalar dependencias
composer install
```

### Configurar IDE

Recomendamos usar VS Code con las siguientes extensiones:
- PHP Intelephense
- PHP Debug
- GitLens
- Prettier
- ESLint

### Ejecutar Tests

```bash
# Instalar PHPUnit
composer install --dev

# Ejecutar tests
composer test
```

## Producción

### Configuración de Seguridad

1. **Cambiar contraseñas por defecto**
2. **Configurar HTTPS**
3. **Ocultar archivos sensibles**
4. **Configurar firewall**
5. **Habilitar logs de seguridad**

### Optimización

1. **Habilitar OPcache**
2. **Configurar cache de archivos**
3. **Optimizar imágenes**
4. **Minificar CSS/JS**
5. **Configurar CDN**

### Backup

```bash
# Backup de base de datos
mysqldump -u root -p jaguata > backup_$(date +%Y%m%d_%H%M%S).sql

# Backup de archivos
tar -czf jaguata_backup_$(date +%Y%m%d_%H%M%S).tar.gz /path/to/jaguata
```

## Actualizaciones

### Actualizar Código

```bash
git pull origin main
composer install
php database/migrations/update.php
```

### Actualizar Base de Datos

```bash
mysql -u root -p jaguata < database/migrations/latest.sql
```

## Soporte

Si encuentras problemas durante la instalación:

1. Revisar esta documentación
2. Verificar logs de error
3. Consultar issues en GitHub
4. Contactar al equipo de desarrollo

## Licencia

Este proyecto está bajo la Licencia MIT. Ver `LICENSE` para más detalles.

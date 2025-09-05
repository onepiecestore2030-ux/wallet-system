FROM php:8.1-apache

# تثبيت امتدادات PHP المهمة (بما فيها PostgreSQL)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo_pgsql pgsql

# نسخ الملفات إلى خادم الويب
COPY . /var/www/html/

# تشغيل Apache
CMD ["apache2-foreground"]

FROM php:8.1-apache

# تثبيت امتدادات PHP المهمة (بما فيها MySQLi)
RUN docker-php-ext-install mysqli pdo_mysql

# نسخ الملفات إلى خادم الويب
COPY . /var/www/html/

# تشغيل Apache
CMD ["apache2-foreground"]

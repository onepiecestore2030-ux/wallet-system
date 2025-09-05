FROM php:8.1-apache

# نسخ الملفات إلى خادم الويب
COPY . /var/www/html/

# تشغيل Apache
CMD ["apache2-foreground"]

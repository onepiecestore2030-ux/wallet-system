FROM ubuntu:22.04

# تحديث النظام وتثبيت Apache وPHP
RUN apt-get update && apt-get install -y \
    apache2 \
    php \
    libapache2-mod-php \
    && rm -rf /var/lib/apt/lists/*

# نسخ الملفات إلى خادم الويب
COPY . /var/www/html/

# تفعيل Apache وتشغيله
CMD ["apache2ctl", "-D", "FOREGROUND"]

# Используем официальный образ PHP 8.1 с включенным сервером CLI
FROM php:8.2-cli

# Устанавливаем рабочую директорию
WORKDIR /app

# Копируем файлы проекта в контейнер
COPY . /app

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Устанавливаем зависимости проекта через Composer
RUN composer install

# Запускаем PHP CLI сервер на порту 8000
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public", "-d", "PHP_CLI_SERVER_WORKERS=5"]

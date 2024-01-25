### Hexlet tests and linter status:
[![Actions Status](https://github.com/NickRyabinin/php-project-9/workflows/hexlet-check/badge.svg)](https://github.com/NickRyabinin/php-project-9/actions)
[![Actions Status](https://github.com/NickRyabinin/php-project-9/workflows/actions/badge.svg)](https://github.com/NickRyabinin/php-project-9/actions)
[![Maintainability](https://api.codeclimate.com/v1/badges/e4b821946dcaaf03a104/maintainability)](https://codeclimate.com/github/NickRyabinin/php-project-9/maintainability)
### Page Analyzer – сайт, который анализирует указанные страницы на SEO пригодность.

Применяемый стек: PHP/Slim. Стилизация - Bootstrap. Используемая БД - PostgreSQL. Данные для подключения берутся из переменной окружения DATABASE_URL вида
```bash
DATABASE_URL=postgres://user:pass@host:port/database
```

### Требования:
php >= 8

composer

### Установка:
```bash
git clone git@github.com:NickRyabinin/php-project-9.git

make install
```
### Локальный запуск:
```bash
make start-local
```
### Запуск в продакшене:
```bash
make start
```
Посмотреть проект можно [по этой ссылке](https://page-seo-check.onrender.com/).
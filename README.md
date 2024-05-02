## About Laravel

One Dashboard RESTful API to manage inventories.

## Requirements

### Manual

- PHP >= 8.2
- Composer
- MySQL

### Container

- Docker Compose


## Installation

### Manual

- Run `composer install`
- Copy `env.example` to `.env` and set it with your own credentials
- Run `php artisan migrate:refresh --seed`
- Run `php artisan key:generate`
- Run `php artisan storage:link`
- Run `php artisan serve`

### Docker

- Copy `env.example` to `.env` and set it with your own credentials
- Copy `/docker/env.example` to `/docker/.env` and set it with your own credentials
- Set **DB_HOST=db** and other DB Credentials in `.env` using MYSQL Credentials in `/docker/.env`
- Run `make build`
- Run `docker compose exec app composer install`
- Run `docker compose exec app php artisan migrate:refresh --seed`
- Run `docker compose exec app php artisan key:generate`
- Run `docker compose exec app php artisan storage:link`
- Open [http://localhost:8080](http://localhost:8080) for the app
- localhost:3306 for database (MySQL)
- Open [http://localhost:8082](http://localhost:8082) for database panel (phpmyadmin)

## Note

- Open [{BASE_URL}/api/v1/documentation](http://localhost:8080/api/v1/documentation) to view the API Documentation (Swagger)

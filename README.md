## About Laravel

One Dashboard RESTful API to manage products.

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
- Set **DB_HOST=db** (or **DB_HOST=host.docker.internal**) and other DB Credentials in `.env` using MYSQL Credentials in `/docker/.env`
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

## Commands
- Reset migration with the data, then generate data for admin user and categries : `artisan migrate:refresh --seed`
- Generate Products data with the Expenses: `artisan db:seed ProductSeeder`
- Generate Transactions data with the Incomes: `artisan db:seed TransactionSeeder` (require generate product data first)

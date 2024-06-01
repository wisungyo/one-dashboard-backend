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

- For generate dummy data (exclude user and category) like Products, Transactions, Expenses and Incomes: Run `php artisan db:seed ProductTransactionSeeder`
- Open [{BASE_URL}/api/v1/documentation](http://localhost:8080/api/v1/documentation) to view the API Documentation (Swagger)


## Prediction flow

Detail perhitungan dan lain-lainnya untuk menentukan nilai prediksi ada di `app/Http/Services/Api/V1/PredictionService.php` function `calculatePrediction`.
Kurang lebih flow atau stepnya seperti ini:
1. Tentukan range periode berdasarkan input tahun dan bulan dengan cara: input tahun dan bulan dikurangi (-) 3 bulan
2. Get total quantity dari masing2 produk dari hasil penjualan di `/api/v1/transactions` dengan type `OUT` untuk range yang sudah ditentukan (step 1)
3. Grouping total quantity dari masing2 produk (step 2) menjadi per tahun dan bulan dan urutkan berdasarkan tahun dan bulan
4. Jika ada data penjualan (step 3) yang kosong (tidak ditemukan) pada salah satu/lebih dari range yang sudah ditentukan (step 1), atur total penjualannya menjadi 0 sebagai nilai default (contoh: jika data penjualan untuk produk A cuma ada untuk bulan 3 dan 5 dari range bulan 3-5, maka untuk penjualan bulan 4 dihitung total penjualannya 0)
5. Lakukan perhitungan exponential smoothing dengan formula berikut untuk masing2 data produk:

```
prediksi_bulan_depan = ALPHA x total_penjualan_bulan_ini + (1 - ALPHA) x nilai_prediksi_bulan_lalu

Note:
- ALPHA = 0.1
- jika `nilai_prediksi_bulan_lalu` tidak ada, bisa menggunakan nilai 0 sebagai nilai default
```

6. Lakukan pengecekkan untuk summary penjualan dari masing2 produk untuk mendapatkan total penjualan bulan sebelumnya, penjualan bulan ini, dan peningkatan total penjualan dari bulan sebelumnya ke bulan ini beserta persentasenya.
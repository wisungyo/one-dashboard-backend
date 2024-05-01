all := nginx app db phpmyadmin
without_db := nginx
only_app := app

build:
	echo "Build all :" $(all)
	docker compose up -d --build $(all)
build_without_db:
	echo "Build without_db :" $(without_db)
	docker compose up -d --build $(without_db)
build_only_app:
	echo "Build only_app :" $(only_app)
	docker compose up -d --build $(only_app)

# common
down:
	echo "[Common] Down all containers"
	docker compose down
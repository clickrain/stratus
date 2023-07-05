install:
	composer create-project --no-interaction craftcms/craft _cms 
	cp .env.craft _cms/.env
	

install-plugin: 
	docker-compose exec cms composer config minimum-stability dev
	docker-compose exec cms composer config prefer-stable true
	docker-compose exec cms composer config repositories.0 path ../plugin
	docker-compose exec cms composer require clickrain/stratus

up:
	docker-compose up -d

down:
	docker-compose down

shell:
	docker-compose exec cms /bin/sh

ssh:
	make shell

generate:
	docker-compose exec ui npm run generate

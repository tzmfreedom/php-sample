.PHONY: build
build:
	docker-compose build

.PHONY: up
up:
	docker-compose up -d

.PHONY: restart
restart:
	docker-compose restart

.PHONY: console
console:
	docker-compose exec php-apache /bin/bash

.PHONY: ps
ps:
	docker-compose ps

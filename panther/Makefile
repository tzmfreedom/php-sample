.PHONY: build
build:
	docker-compose build

.PHONY: up
up:
	docker-compose up -d

.PHONY: console
console:
	docker-compose exec panther /bin/bash

.PHONY: init
init:
	vendor/bin/bdi detect drivers

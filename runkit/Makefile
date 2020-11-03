.PHONY: run
run:
	docker run -v $(PWD):/root -w /root -it runkit /usr/local/bin/php ./main.php

.PHONY: console
console:
	docker run -v $(PWD):/root -it runkit /bin/bash

.PHONY: build
build:
	docker build . -t runkit


build: ## rebuild docker container
	@docker-compose up -d --build > /dev/null

composer-install: ## run composer installation within the docker containers (useful for local development)
	@docker-compose run --rm php_bundle_2 composer install

run:  ## run container
	@docker-compose up -d > /dev/null

attach: ## Entrance to the container in an interactive shell
	@docker exec -it php_bundle_2 bash

shutdown:
	@docker-compose down

tests: ## run tests
	@docker-compose run --rm php_bundle_2 composer phpunit

static-analysis: ## verify code type-level soundness
	docker-compose run --rm php_bundle_2 composer phpstan

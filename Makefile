ifndef PROJECT
	PROJECT=symfony_metadata_loader
endif

ifndef ENV
	ENV=dev
endif

ifndef APP_ENV
	APP_ENV=dev
endif

ifndef UID
	UID=`id -u`
endif

ifndef GID
	GID=`id -g`
endif

.PHONY: tests

COMPOSE=PROJECT=$(PROJECT) ENV=$(ENV) UID=$(UID) GID=$(GID) COMPOSE_PROJECT_NAME=$(PROJECT) docker compose --project-name=$(PROJECT) -f docker-compose.yaml -f docker-compose.$(ENV).yaml
STACK_DEPLOY=PROJECT=$(PROJECT) ENV=$(ENV) UID=$(UID) GID=$(GID) docker stack deploy --compose-file docker-compose.yaml --compose-file docker-compose.$(ENV).yaml $(PROJECT)-$(ENV) --prune --with-registry-auth

env:
	@echo "Setting up env vars for $(ENV) ..."
	@echo "PROJECT is $(PROJECT)"
	@echo "ENV is $(ENV)"
	@echo "APP_ENV is $(APP_ENV)"
	@echo "UID is $(UID)"
	@echo "GID is $(GID)"
	@echo "build.counter is $(CI_PIPELINE_ID)"
	@sed -i -r "s~^ENV=.+~ENV=$(ENV)~g" .env
	@sed -i -r "s~^APP_ENV=[a-z]+~APP_ENV=$(APP_ENV)~g" .env
	@sed -i -r "s~^UID=[0-9]+~UID=$(UID)~g" .env
	@sed -i -r "s~^GID=[0-9]+~GID=$(GID)~g" .env
	@echo "Set up of UID and GID for $(ENV) is completed!"

build: env
	@echo "Building $(ENV) ..."
	@$(COMPOSE) build --build-arg PRIVATE="$$(cat ~/.ssh/id_rsa)" || exit 1
	@echo "Built $(ENV) !"

up:
	@echo "Starting $(ENV) ..."
	@$(COMPOSE) up -d --remove-orphans || exit 1
	@$(COMPOSE) exec -T php composer install
	@echo "Built $(ENV) !"

down:
	@echo "Stopping $(ENV) ..."
	@$(COMPOSE) down
	@echo "Stopped $(ENV) !"

clean:
	@echo "Stopping all containers and cleaning $(ENV) ..."
	@docker stop $$(docker ps -a -q)
	@yes | docker system prune
	@echo "Cleaned $(ENV) !"

test:
	@echo "Testing $(ENV) ..."
	@$(COMPOSE) exec php ./vendor/bin/codecept clean
	@$(COMPOSE) exec php ./vendor/bin/codecept run Unit
	@echo "Done $(ENV) !"

fix:
	@echo "Fixing $(ENV) ..."
	@$(COMPOSE) exec -T php php vendor/bin/php-cs-fixer fix
	@echo "Fixed $(ENV) !"
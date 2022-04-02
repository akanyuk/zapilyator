BINARY_NAME := $(shell git config --get remote.origin.url | awk -F/ '{print $$5}' | awk -F. '{print $$1}')
BINARY_VERSION := $(shell git describe --tags)

PRINTF_FORMAT := "\033[35m%-18s\033[33m %s\033[0m\n"

.PHONY: all docker-build docker-run help

all: docker-build

docker-build: ## Docker image generation
	@printf $(PRINTF_FORMAT) BINARY_NAME: $(BINARY_NAME)
	@printf $(PRINTF_FORMAT) BINARY_VERSION: $(BINARY_VERSION)

	docker build --tag $(BINARY_NAME):$(BINARY_VERSION) .

docker-run: docker-build ## Docker image run
ifneq ($(shell docker ps --filter name=$(BINARY_NAME) -aq),)
	docker container rm --force $(BINARY_NAME) || true
endif
	docker run --detach --name $(BINARY_NAME) -p 80:80 $(BINARY_NAME):$(BINARY_VERSION)

help: ## Display available commands
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

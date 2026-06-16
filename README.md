# api.dangibbs.uk

[![Test](https://github.com/gibbs/api.dangibbs.uk/actions/workflows/test.yml/badge.svg)](https://github.com/gibbs/api.dangibbs.uk/actions/workflows/test.yml)
[![Build](https://github.com/gibbs/api.dangibbs.uk/actions/workflows/build.yml/badge.svg)](https://github.com/gibbs/api.dangibbs.uk/actions/workflows/build.yml)

A [Laravel](https://laravel.com/) API application, primarily serving [dangibbs.uk](https://dangibbs.uk).

## Development

Copy the `.env` file and edit it as required:

```bash
cp .env.example .env
```

Install the dependencies and start the local Docker environment with [Sail](https://laravel.com/docs/sail):

```bash
composer install
sail up -d
```

## Test

Run feature tests with Pest:

```bash
sail artisan test
sail artisan test --coverage-html ./coverage/
```

## Build

To build the Docker image locally:

```bash
source .env
docker build --file .docker/Dockerfile --no-cache -t ${DOCKER_BUILD_NAME}:${DOCKER_BUILD_TAG} -t ${DOCKER_BUILD_NAME}:latest .
```

Pushing a tag to `master` triggers the [Build](.github/workflows/build.yml) workflow, which builds and publishes the image to Docker Hub.

# API-Platform (API Pack with Doctrine)

By default, API-Platform comes with Doctrine StateProviders and StateProcessors. VOM perfectly integrates with API-Platform.
Simply add `'vom' => true` to the normalization and/or denormalization context of an operation to enable the transformation.

This can for example be used to create additional endpoints, that accept or return the transformed data like in this [Person resource](./src/Entity/Person.php) for legacy clients. 

## Run the example

For a very quick start, download the [Symfony-CLI](https://symfony.com/download). But you can also serve it with php directly.

```bash
composer install --no-dev
bin/console doctrine:migrations:migrate --no-interaction

symfony serve
# or alternatively
# php -S localhost:8000 ./public/index.php
```

Open `http://localhost:8000/api` in your browser.

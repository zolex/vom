# VOM with API-Platform

When working with API-Platform, the `VersatileObjectMapper` is available as a Symfony service.

So all you need to do to get the VOM service, is type-hinting it wherever you need it. [See the state provider](./src/State/PersonStateProvider.php) included in this example.

## Run the example

For a very quick start, download the [Symfony-CLI](https://symfony.com/download). But you can also serve it with php directly.

```bash
composer install --no-dev

symfony serve
# or alternatively
# php -S localhost:8000 ./public/index.php
```

Open `http://localhost:8000/api` in your browser :)

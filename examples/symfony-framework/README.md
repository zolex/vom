# VOM with Symfony-Framework

When working with Symfony-Framework, the `VersatileObjectMapper` is available as a Ssymfony service.

So all you need to do to get the VOM service, is type-hinting it wherever you need it. [See the controller](./src/Controller/VomController.php) included in this example.

## Run the example

For a very quick start, download the [Symfony-CLI](https://symfony.com/download). But you can also serve it with php directly.

```bash
composer install --no-dev

symfony serve
# or alternatively
# php -S localhost:8000 ./public/index.php

curl http://localhost:8000/vom-action
```

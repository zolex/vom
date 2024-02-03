# VOM with API-Platform

When working with API-Platform and Custom StateProviders and or StateProcessors there are several options to normalize and denormalize the ApiResources.

The recommended way is to simply type-hint Symfony's `SerializerInterface`, `NormalizerInterface` or `DenormalizerInterface`, [see the Person StatePovider](./src/State/PersonStateProvider.php) in this example.
VOM integrates with the standard symfony serializer, so that calling the `serialize()`, `deserialize()`, `normalize()` and `denormalize()` methods on the `Serializer` will also invoke VOM if it finds a `VOM\Model`.

_Another option would be to type-hint the `VersatileObjectMapper` wherever you need it, but in fact this class just decorates the Serializer and passes every method call directly to it._

## Run the example

For a very quick start, download the [Symfony-CLI](https://symfony.com/download). But you can also serve it with php directly.

```bash
composer install --no-dev

symfony serve
# or alternatively
# php -S localhost:8000 ./public/index.php
```

Open `http://localhost:8000/api` in your browser :)

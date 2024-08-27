# Versatile Object Mapper

[![Integration](https://github.com/zolex/vom/workflows/Integration/badge.svg)](https://github.com/zolex/vom/actions)
[![Release](https://github.com/zolex/vom/workflows/Release/badge.svg)](https://github.com/zolex/vom/actions)
[![Code Coverage](https://codecov.io/gh/zolex/vom/graph/badge.svg?token=RI2NX4S89I)](https://codecov.io/gh/zolex/vom)
![Latest Stable Version](https://img.shields.io/packagist/v/zolex/vom)

![License](https://img.shields.io/packagist/l/zolex/vom)
![Downloads](https://img.shields.io/packagist/dt/zolex/vom)
![Dependabot](https://img.shields.io/badge/dependabot-025E8C?style=for-the-badge&logo=dependabot&style=plastic)

![VOM](docs/logo.png)

![PHP](https://img.shields.io/badge/php-%23777BB4.svg?style=for-the-badge&logo=php&logoColor=white)
![Symfony](https://img.shields.io/badge/symfony-%23000000.svg?style=for-the-badge&logo=symfony&logoColor=white)
![Laravel](https://img.shields.io/badge/laravel-%23FF2D20.svg?style=for-the-badge&logo=laravel&logoColor=white)


The Versatile Object Mapper - or in short VOM - is a PHP library to transform any data structure into strictly typed models, by simply adding PHP attributes to existing classes.

- [Installation](#installation)
- [Quickstart](#quickstart)
- [Full Documentation](./docs/README.md)
- [Examples](#examples)

## Installation

VOM is available on packagist. To install, simply require it via composer. 

```bash
composer require zolex/vom ^0.1
```

### Plain PHP

When installed via composer or as a download from the releases page, you are ready to use [VOM without a framework](https://github.com/zolex/vom-examples/tree/main/without-framework).

### Symfony

When using symfony, the package also integrates as a bundle. With flex and autoconfiguration there is nothing else to do. You are ready to use the [VOM Symfony Service](https://github.com/zolex/vom-examples/tree/main/symfony-framework). For the best interoperability, VOM implements the Symfony normalizer and denormalizer interfaces.

_Without autoconfiguration, or if you choose to not run symfony/flex recipe generation, you have to enable the bundle manually by adding it to `config/bundles.php`._

```php
<?php

return [
    // ...
    Zolex\VOM\Symfony\Bundle\ZolexVOMBundle::class => ['all' => true],
    // ...
];
```

### Laravel

VOM also comes with a Laravel Service Provider. After installing with composer, the `VersatileObjectMapper` class is registered for Dependency Injection and can also be accessed using `app()`, `resolve()` etc.
See the example for [VOM in Laravel](https://github.com/zolex/vom-examples/tree/main/laravel).

## Quickstart

To give you a basic idea of what VOM does, there is a short example.

Given your application receives the following flat array of values from somewhere.

```php
$data = [
    'firstname' => 'Jane',
    'surname' => 'Doe',
    'street' => 'Samplestreet 123',
    'city' => 'Worsthausen',
    'zip' => '12345',
    'country_name' => 'United Kingdom',
    'email_address' => 'jane.doe@coxautoinc.com',
    'phone' => '0123456789'
];
```

Usually you would write some code that creates the model instances, sets their properties and nests them properly.
In very simple scenarios, writing the transformation logic as code can be a good choice, but it can be a pain when it comes to very huge models, the input data structures
and/or application models change while still in development, or if you want to reuse the transformation logic in other projects too because it receives the same inputs and/or uses the same models.

### How it works using VOM

Instead of writing business logic that feeds your models, with VOM you simply configure the models using PHP attributes.

```php
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class Person
{
    #[VOM\Property]
    public string $firstname;
    
    #[VOM\Property('[surname]')]
    public string $lastname;
    
    #[VOM\Property(accessor: false)]
    public Address $address;
    
    #[VOM\Property(accessor: false)]
    public Contact $contact;
}

#[VOM\Model]
class Address
{
    #[VOM\Property]
    public string $street;
    
    #[VOM\Property('[zip]')]
    public string $zipCode;
    
    #[VOM\Property]
    public string $city;
    
    #[VOM\Property('[country_name]')]
    public string $country; 
}

#[VOM\Model]
class Contact
{
    #[VOM\Property('[email_address]')]
    public string $email;
    
    #[VOM\Property]
    public string $phone;
}
```

To create instances of your models, you simply pass the data to the `denormalize()` method.

```php
$person = $objectMapper->denormalize($data, Person::class);
``` 

You may have noticed, that some property attributes have arguments while others don't. For details on that, see the [full documentation](./docs/README.md).

### Now, do I need this?

If there is any difference between the data structure of your input and your application's models, VOM may be a good choice to avoid writing and maintaining code, but instead just add some PHP attributes.**

> [!NOTE]
> If you need to inject data into your entities that already is in a structure matching your models, this library can be used but may be an overhead. In this scenario you could simply utilize a standard [Symfony normalizer](https://symfony.com/doc/current/components/serializer.html#normalizers).


## Documentation

A [full documentation](./docs) of all features is available in the docs folder of this repository.

## Examples

The example from the above quickstart and more can be found in the [VOM Examples repository](https://github.com/zolex/vom-examples).

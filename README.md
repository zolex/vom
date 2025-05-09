# Versatile Object Mapper

[![Release](https://github.com/zolex/vom/workflows/Release/badge.svg)](https://github.com/zolex/vom/actions/workflows/release.yaml)
[![Version](https://img.shields.io/packagist/v/zolex/vom)](https://packagist.org/packages/zolex/vom)
[![Integration](https://github.com/zolex/vom/workflows/Integration/badge.svg)](https://github.com/zolex/vom/actions/workflows/integration.yaml)
[![Code Coverage](https://codecov.io/gh/zolex/vom/graph/badge.svg?token=RI2NX4S89I)](https://codecov.io/gh/zolex/vom)
[![License](https://img.shields.io/packagist/l/zolex/vom)](./LICENSE)
[![Downloads](https://img.shields.io/packagist/dt/zolex/vom)](https://packagist.org/packages/zolex/vom)

![VOM](docs/logo.png)

![PHP](https://img.shields.io/badge/php-%23777BB4.svg?style=for-the-badge&logo=php&logoColor=white)
![Symfony](https://img.shields.io/badge/symfony-%23000000.svg?style=for-the-badge&logo=symfony&logoColor=white)
![Laravel](https://img.shields.io/badge/laravel-%23FF2D20.svg?style=for-the-badge&logo=laravel&logoColor=white)


The Versatile Object Mapper - or in short VOM - is a PHP library to transform any data structure into strictly typed models (and back) by adding PHP 8 attributes to model classes.

__The concept of this package is based on two principles:__
1. Reuse existing functions of symfony/serializer to not reinvent the wheel (and optionally integrate seamlessly into the framework)
2. All mapping configuration should be defined on the model itself, inspired by doctrine and API-Platform

- [Installation](#installation)
- [Quickstart](#quickstart)
- [Documentation](https://github.com/zolex/vom/wiki)
- [Examples](#examples)

## Installation

VOM is available on packagist. To install, simply require it via composer. 

```bash
composer require zolex/vom ^0.9
```

## Quickstart

To give you a basic idea of what VOM does, here is a first short example.

Given, your application receives the following array of values from somewhere.

```php
$data = [
    'firstname' => 'Jane',
    'surname' => 'Doe',
    'street' => 'Samplestreet 123',
    'city' => 'Worsthausen',
    'zip' => '12345',
    'country_name' => 'United Kingdom',
    'email_address' => 'jane.doe@mailprovider.net',
    'phone' => '0123456789'
];
```

Usually you would write some code that creates the model instances, sets their properties and nests them properly.
In very simple scenarios, writing the transformation logic as code might be a good choice, but it can be a pain when it comes to very huge models, the input data structures
and/or application models change while still in development, or if you want to reuse the transformation logic in other projects too, because it receives the same inputs and/or uses the same models.

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

You may have noticed, that some property attributes have arguments while others don't. For all details on that, head to the [full documentation](https://github.com/zolex/vom/wiki).

### Now, do I need this?

If there is any difference between the data structure of your input and your application's models, VOM may be a good choice to avoid writing and maintaining code, but instead just add some PHP attributes.

> [!NOTE]
> If you need to inject data into your entities that already is in a structure matching your models, this library can be used but may be an overhead. In this scenario you could simply utilize a standard [Symfony normalizer](https://symfony.com/doc/current/components/serializer.html#normalizers).

## Documentation

The [full documentation](https://github.com/zolex/vom/wiki) is available in the Wiki section of this repository.

## Examples

The example from the quickstart and more can be found in the [VOM Examples repository](https://github.com/zolex/vom-examples).

## Alternatives

There are many Mapping/Transformation/Hydration libraries out there. In case you don't want to rely on `phpdocumentor/reflection-docblock`, `symfony/serializer` and `symfony/proerty-access` which VOM depends on, here are some alternative packages that cover the same topic with quite different approaches and features.

- [jms/serializer](https://github.com/schmittjoh/serializer)
- [CuyZ/Valinor](https://github.com/CuyZ/Valinor)
- [rekalogika/mapper](https://github.com/rekalogika/mapper)
- [Crell/Serde](https://github.com/Crell/Serde)
- [thephpleague/object-mapper](https://github.com/thephpleague/object-mapper)
- [eventsauce/object-hydrator](https://github.com/EventSaucePHP/ObjectHydrator)
- [spatie/laravel-data](https://github.com/spatie/laravel-data)

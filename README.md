# Versatile Object Mapper

[![Integration](https://github.com/zolex/vom/workflows/Integration/badge.svg)](https://github.com/zolex/vom/actions)
[![Release](https://github.com/zolex/vom/workflows/Release/badge.svg)](https://github.com/zolex/vom/actions)

[![Code Coverage](https://codecov.io/gh/zolex/vom/graph/badge.svg?token=RI2NX4S89I)](https://codecov.io/gh/zolex/vom)
[![Latest Stable Version](http://poser.pugx.org/zolex/vom/v)](https://packagist.org/packages/zolex/vom)
[![Latest Unstable Version](http://poser.pugx.org/zolex/vom/v/unstable)](https://packagist.org/packages/zolex/vom)

<!--[![Required PHP Version](http://poser.pugx.org/zolex/vom/require/php)](https://packagist.org/packages/zolex/vom)-->
[![License](http://poser.pugx.org/zolex/vom/license)](https://packagist.org/packages/zolex/vom)
[![Total Downloads](http://poser.pugx.org/zolex/vom/downloads?update=2)](https://packagist.org/packages/zolex/vom)
[![Monthly Downloads](http://poser.pugx.org/zolex/vom/d/monthly?update=2)](https://packagist.org/packages/zolex/vom)

![VOM](docs%2Flogo.png)

The Versatile Object Mapper - or in short VOM - is a PHP library to transform any data structure into strictly typed models, by simply adding PHP attributes to existing classes.

> [!CAUTION]
> This package is in an early stadium and I can not recommend to use it on production before version `0.1.0` will be released. Starting with that version the package will follow the [SemVer](https://semver.org/) standard. _Until the release of `0.1.0` you should expect BC's even for patch version increments!_

- [Installation](#installation)
- [Quickstart](#quickstart)
- [Documentation](./docs/README.md)
- [Examples](./examples)

## Installation

VOM is available on packagist. To install, simply require it via composer. 

```bash
composer require zolex/vom ^0.0.7
```

### Symfony

When using symfony, the package also integrates as a bundle. With flex and autoconfiguration there is nothing else to do: You are ready to use the [VersatileObjectMapper as a Symfony service](./examples/symfony-framework). For the best interoperability, VOM implements the Symfony normalizer and denormalizer interfaces.
Without autoconfiguration, or if you choose to not run symfony/flex recipe generation, you have to enable the bundle manually by adding it to `config/bundles.php`.

```php
<?php

return [
    // ...
    Zolex\VOM\Symfony\Bundle\ZolexVOMBundle::class => ['all' => true],
    // ...
];
```

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
    
    #[VOM\Property('surname')]
    public string $lastname;
    
    #[VOM\Property(nested: false)]
    public Address $address;
    
    #[VOM\Property(nested: false)]
    public Contact $contact;
}

#[VOM\Model]
class Address
{
    #[VOM\Property]
    public string $street;
    
    #[VOM\Property('zip')]
    public string $zipCode;
    
    #[VOM\Property]
    public string $city;
    
    #[VOM\Property('country_name')]
    public string $country; 
}

#[VOM\Model]
class Contact
{
    #[VOM\Property('email_address')]
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

An [complete documentation](./docs) of all features is available in the docs folder of this repository.

## Examples

The example from the above quickstart and some more working examples can be found in this repository. Head over to the [examples](./examples) folder.

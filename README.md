# Versatile Object Mapper for PHP

The Versatile Object Mapper, or in short VOM, is a PHP library to transform any data structures into the desired models, solely by adding PHP attributes to your existing classes.
It implements the symfony normalizer and denormalizer interfaces for the best interoperability.
This is mainly useful to convert legacy, arbitrary data into strictly typed and object-oriented models, which helps to create more reliable and easier to maintain projects while increasing your productivity at the same time.

To give you a basic idea of what VOM does, head to the [Quickstart](#quickstart).

- [Installation](#installation)
- [Quickstart](#quickstart)
- [Documentation](./docs/README.md)
- [Examples](./examples)


## Installation

VOM is available as an alpha release on packagist. To install, simply require it via composer. **You might need to change minimum-stability in your composer.json to dev** 

```bash
composer require zolex/vom dev-master
```

### Symfony

When you are using symfony, the package also integrates as a bundle. With flex and auto-configuration there is nothing more to do, you are ready to use the `VersatileObjectMapper` as a symfony service.

Without autoconfiguration of if you choose to not run symfony/flex recipe generation, you have to enable the bundle manually in `config/bundles.php`:

```php
<?php

return [
    // ...
    \Zolex\VOM\Symfony\Bundle\ZolexVOMBundle::class => ['all' => true],
];
```

## Quickstart

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

And your application's models are designed in the following way.

```php
class Address
{
    // The properties do not need to be public
    // This is just to not bloat the quickstart
    public string $street;
    public string $zipCode;
    public string $city;
    public string $country; 
}

class Contact
{
    public string $email;
    public string $phone;
}

class Person
{
    public string $firstname;
    public string $lastname;
    public Address $address;
    public Contact $contact;
}
```

### What you would do without VOM

Usually you would write some code that creates the model instances, puts the data from your initial array in it and nests them properly.
In very simple scenarios, writing the transformation logic as code can be a good choice, but it can be a pain when it comes to very huge models, the input data structures
and/or application models change while still in development, or if you want to reuse the transformation logic in other projects too because it receives the same inputs and/or uses the same models.


### How it works using VOM

Instead of writing business logic that feeds your models, with VOM you simply configure the models using PHP attributes.

```php

use Zolex\VOM\Mapping as VOM;

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
```

To create instances of your models, you simply pass the data to the `denormalize()` method.

```php
$person = $objectMapper->denormalize($data, Person::class);
``` 

You may have noticed, that some property attributes have arguments while others don't. For details on that, see the [full documentation](./docs/README.md).

### Now, do I need this?

If you need to inject data into your entities that already is in a structure matching your models, this library can be used but may be an overhead. In this scenario you could simply utilize the standard symfony object normalizer.

**If there is any difference between the data structure of your input and your application's models, VOM may be a good choice to avoid writing and maintaining code, but instead just add some PHP attributes.**


## Documentation

An [complete documentation](./docs) of all features is available in the docs folder of this repository.

## Examples

The example from the above quickstart and some more working examples can be found in this repository. Head over to the [examples](./examples) folder.


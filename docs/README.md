# Versatile Object Mapper Documentation

[![Code Coverage](https://codecov.io/gh/zolex/vom/graph/badge.svg?token=RI2NX4S89I)](https://codecov.io/gh/zolex/vom)
[![Type Coverage](https://shepherd.dev/github/zolex/vom/coverage.svg)](https://codecov.io/gh/zolex/vom)
[![Latest Stable Version](http://poser.pugx.org/zolex/vom/v)](https://packagist.org/packages/zolex/vom)
[![Latest Unstable Version](http://poser.pugx.org/zolex/vom/v/unstable)](https://packagist.org/packages/zolex/vom)

[![Required PHP Version](http://poser.pugx.org/zolex/vom/require/php)](https://packagist.org/packages/zolex/vom)
[![License](http://poser.pugx.org/zolex/vom/license)](https://packagist.org/packages/zolex/vom)
[![Total Downloads](http://poser.pugx.org/zolex/vom/downloads)](https://packagist.org/packages/zolex/vom)
[![Monthly Downloads](http://poser.pugx.org/zolex/vom/d/monthly)](https://packagist.org/packages/zolex/vom)

The Versatile Object Mapper, or in short VOM, is a PHP library to transform any data structures into the desired models, solely by adding PHP attributes to your existing classes.
It implements the symfony normalizer and denormalizer interfaces for the best interoperability.
This is mainly useful to convert legacy, arbitrary data into strictly typed and object-oriented models, which helps to create more reliable and easier to maintain projects while increasing your productivity at the same time.


<!-- toc -->

- [The Object Mapper](#the-object-mapper)
- [Denormalization](#denormalization)
- [Normalization](#normalization)
- [Context](#context)
  * [Groups](#groups)
  * [Skip Null Values](#skip-null-values)
  * [Root Fallback](#root-fallback)
  * [Object to Populate](#object-to-populate)
- [Attribute Configuration](#attribute-configuration)
  * [The Accessor](#the-accessor)
  * [Nested properties](#nested-properties)
  * [Arrays of objects](#arrays-of-objects)
  * [Data Types](#data-types)
    + [Booleans](#booleans)
    + [Flags](#flags)
    + [DateTime](#datetime)
  * [Nesting with Accessors](#nesting-with-accessors)

<!-- tocstop -->

## Recommended Workflow
When starting a new project or refactoring an existing one, you should always design your models first. For example If it's a REST API reading the state from Solr or Soap utilizing API-Platform,
the design of your models will directly reflect it's OpenAPI specification. If it's a commandline tool like a queue-consumer that is sending data to a third party API, your models should reflect the input format of that API etc.
The models can be anything, so if you receive data from somewhere and need to write data to a database, the models can also be Doctrine Entities.

1. **The first step always is to design your models.** At this point you should not have implemented any business logic. Just the plain models.
2. **Now it makes sense to add the VOM attributes to your models.** This way you avoid changing the mapping attributes as your models evolve.
3. **Finally, implement your business logic.** With well-designed models, automatically fed by VOM, implementing your business logic will be a piece of cake.


## The Object Mapper

In symfony framework you can simply use dependency injection to gain access to the preconfigured object mapper service. Also see the [symfony example](../examples/symfony-framework) 

```php
use Zolex\VOM\VersatileObjectMapper;

class AnySymfonyService
{
    public function __construct(private VersatileObjectMapper $objectMapper)
    {
    }
}
```

Without symfony framework, you have to construct the mapper yourself. Also see the [plain php example](../examples/without-framework)

```php
$objectMapper = new \Zolex\VOM\VersatileObjectMapper(
    new \Zolex\VOM\Metadata\Factory\ModelMetadataFactory(
        new \Zolex\VOM\Metadata\Factory\PropertyMetadataFactory()
    ),
    \Symfony\Component\PropertyAccess\PropertyAccess::createPropertyAccessor(),
);
```

## Denormalization

The object mapper can process any kind of PHP data structure as input. This includes arrays, plain old PHP objects, class instances and any combination and nesting of all these.
So the input data might be an array, an stdClass, other models with private properties and their own getters and setters as well as arrays of any type.
To create a model instance from the input data, simply call the `denormalize()` method on the mapper.

A very common use-case is to deserialize json for example with `json_decode()` or using symfony's serializer and pass the result to the `denormalize()` method.

```php
$person = $objectMapper->denormalize($data, Person::class);
```

> [!TIP]
> It can make sense to have additional properties which are not fed by the VOM denormalization, for example if you use the injected data to do further computations and store it on a model before returning it to the client, sending it to another API or storing it in the database.



## Normalization

Normalization creates a plain old PHP array from your model instance. Because VOM implements the symfony normalizer interface, the `normalize()` method must return an array.
If you need it converted to an object, use the `toObject()` method and pass it the denormalization result (this is not just casting, but deeply converting the whole data structure, just leaving indexed, sequential arrays intact).

```php
$person = new Person();
// ...
$array = $objectMapper->normalize($person);
$object = $objectMapper->toObject($array);
```

Note that in the current version it can not convert back data structures to their original, that contain or solely exist of other classes like entities or models. It will instead output normal arrays or objects.

## Context

VOM's `normalize()` and `denormalize()` methods accept several useful properties in the context argument, sticking to existing standards, so that it integrates seamlessly with Symfony and API-Platform where applicable.

### Groups

```php
use Symfony\Component\Serializer\Annotation\Groups;

#[VOM\Model]
class SomeModel
{
    #[Groups('group_a')]
    #[VOM\Property]
    public string $rooted;
    
    #[Groups(['group_a', 'group_b'])]
    #[VOM\Property]
    public NestedClass $nested;
}
```

To only process properties with specific groups you can pass the groups in the context.

```php
$someModel = $objectMapper->denormalize($data, SomeModel::class, context: ['groups' => ['group_a']]);

$someArray = $objectMapper->normalize($someModel, context: ['groups' => ['group_a', 'group_b']]);
```

### Skip Null Values

To skip all null values, which means for denormalization not to initialize their corresponding properties in the model, and for normalization to simply not include them in the resulting data array.

```php
$someModel = $objectMapper->denormalize($data, SomeModel::class, context: ['skip_null_values' => true]);

$someArray = $objectMapper->normalize($someModel, context: ['skip_null_values' => true]);
```

### Root Fallback

Whenever a property accessor can not find anything in the source data this option allows to fall back to the root of the data structure and continue to search from there. This is only available for denormalization.

```php
$someModel = $objectMapper->denormalize($data, SomeModel::class, context: ['root_fallback' => true]);
````

### Object to Populate

If you already have an object that you want to populate, you can pass it to the denormalize method. This can be useful when you are for example reading an entity from a database and want to update values on it.

```php
$someModel = new SomeModel();
$someModel = $objectMapper->denormalize($data, SomeModel::class, context: ['object_to_populate' => $someModel]);
````


## Attribute Configuration

When attributes with no arguments are added to the model properties, VOM will utilize its default behavior, which essentially is the same as the standard symfony object normalizer.
Meaning that in this case no actual data transformation will be done, it will simply inject the values from the data structure where they match in the same structure of your models.

```php
$data = [
    'rooted' => 'I live on the root',
    'nested' => (object)[
        'value' => 'I am a nested value'
    ];
];

#[VOM\Model]
class RootClass
{
    #[VOM\Property]
    public string $rooted;
    
    #[VOM\Property]
    public NestedClass $nested;
}

#[VOM\Model]
class NestedClass
{
    #[VOM\Property]
    public string $value;
}
```
As the data structures and names of all properties are identical, no additional configuration is required. _But as mentioned above, in this scenario you probably just want to use a symfony normalizer to save resources._


### The Accessor

The first argument of the property attribute is the accessor. When not provided, it uses the property's name. It can be written in the following ways.

```php
#[VOM\Property('source_param')]
public string $value;
```

Or to be more explicit:

```php
#[VOM\Property(accessor: 'source_param')]
public string $value;
```

The accessor is not just the name of a parameter, it is [symfony property access syntax](https://symfony.com/doc/current/components/property_access.html).
Note that **VOM always uses the object syntax**, even if the input data is an array! This way the syntax is independent of the type of input. The only exception when array is kept, is when it is an indexed, sequential array!


```php
$data = [
    'onTheRoot' => 'I live on the root',
    'deeper' [
        'inThe' => [
            'structure' => 'I live somewhere deeper in the data structure'
        ]
    ]
];

#[VOM\Model]
class RootClass
{
    #[VOM\Property]
    public string $onTheRoot;
    
    #[VOM\Property(accessor: 'deeper.inThe.structure')]
    public string $fromTheDeep;
}
```


### Nested properties

The data structures can be as deeply nested as you want. By default, every property has the nested argument set to true, which results in the behavior of a standard denormalizer as described above.
When a property is an array or another class and has the nested argument set to false, VOM will reset the all accessors of the directly nested attributes to the source data root,
instead of applying the accessors where they appear in your model structure.

```php
$data = [
    'rooted' => 'I live on the root',
    'value' => 'I am on the input data root too'
];

#[VOM\Model]
class RootClass
{
    #[VOM\Property]
    public string $rooted;
    
    #[VOM\Property(nested: false)]
    public NestedClass $nested;
}

#[VOM\Model]
class NestedClass
{
    #[VOM\Property]
    public string $value;
}
```

VOM will only reset the source to the data root for nested entities that explicitly have the flag set to false, which means that you can decide where exactly you are expecting the input data on each level of deeply nested structures.

```php
$data = [
    'rooted' => 'I live on the root',
    'deeper' [
        'effectivelyFromSecondDimension' => 'I am a nested value on the second dimension'
    ]
];

#[VOM\Model]
class RootClass
{
    #[VOM\Property]
    public string $rooted;
    
    #[VOM\Property(nested: false)]
    public NestedClass $nested;
}

#[VOM\Model]
class NestedClass
{
    #[VOM\Property]
    public string $value;
    
    #[VOM\Property]
    public ThirdDimension $deeper;
}

#[VOM\Model]
class ThirdDimension
{
    #[VOM\Property]
    public string $effectivelyOnSecondDimension;
}
```

### Arrays of objects

VOM can also handle arrays of entities. This is pretty straightforward and needs no further explanation, you just have to add an annotation for the array type.

_In future VOM will also support `Iterator` and `Traversable` (for example Doctrine Collections etc.), but for now it only works with plain old PHP arrays._

```php
$data = [
    'nestedArray' [
        [
            'value' => 'I am the value'
        ],
        [
            'value' => 'I am another value'
        ],
        [
            'value' => 'Yet another value'
        ]
    ]
];

#[VOM\Model]
class RootClass
{
    /**
     * @var NestedClass[] 
     */
    #[VOM\Property]
    public array $nestedArray;
}

#[VOM\Model]
class NestedClass
{
    #[VOM\Property]
    public string $value;
}
```


### Data Types

#### Booleans

In an arbitrary data structure, several values may represent a boolean value. If a property is a strictly typed boolean, VOM makes a handy decision whether a value is mapped to true or false.

```php
#[VOM\Property]
public bool $state;
```

The following values are considered to be `true`

```php
    true, 1, '1', 'on', 'ON', 'yes', 'YES', 'y', 'Y'
```

The following values are considered to be `false`

```php
    null, false, 0, '0', 'off', 'OFF', 'no', 'NO', 'n', 'N'
```

If your boolean is nullable, any value that does not match either the true or false list, the value will become null if it was uninitialized and stay null if it already was.

```php
#[VOM\Property]
public ?bool $nullableState;
```

For normalization purpose you can explicitly tell VOM which value you want to use to represent a boolean.

```php
#[VOM\Property(trueValue: 'ON', falseValue: 'OFF')]
public bool $nullableState;
```


#### Flags

Flags are also booleans but behave in a different way when it comes to denormalization. Usually a set of flags sits in an array or object. VOM can handle two types of Flags.

##### Common Flag

The first flag-type type is a `Common Flag`. These flags must be strings that sit in an array.
The presence of the flag will result in a true value. It's absence will result in a false value (or null if your property is nullable!).
Additionally, a Common flag can be explicitly set to false by prepending an exclamation mark (!) on the flag value.

```php
$data = [
    'flags' => ['is_great', '!is_weak'];
];

#[VOM\Model]
class RootClass
{
    #[VOM\Property]
    public Flags $flags;
}

#[VOM\Model]
class Flags
{
    #[VOM\Property('is_great', flag: true)]
    public bool $great;
      
    #[VOM\Property('is_weak', flag: true)]
    public bool $weak;
    
    #[VOM\Property('is_funny', flag: true)]
    public ?bool $awesome;
}
```

##### Model Flag

The second flag-type is a `Model Flag`. Actually it's not really a flag but just another model. This model can contain any properties, for example a label.
If it has a bool property with the flag argument set to true, this will then become true for any value that is not in the false list of the boolean type documented above.

```php
$data = [
    "labeledFlags" => [
        "flagA" => (object)[
            "text" => "Flag A",
            "value" => true,
        ],
        "flagB" => [
            "text" => "Flag B",
            "value" => "flagB",
        ],
    ],
];


#[VOM\Model]
class ModelFlag
{
    #[VOM\Property('text')]
    public string $label;

    #[VOM\Property('value', flag: true)]
    public bool $isEnabled;
}

#[VOM\Model]
class ModelFlagsContainer
{
    #[VOM\Property]
    public ModelFlag $flagA;

    #[VOM\Property]
    public ModelFlag $flagB;

    #[VOM\Property]
    public ModelFlag $flagC;
}

````



Both types of flags will be expected to sit in their parent property (in the above example 'flags'), but it is also possible to apply flags from different properties
using the `flagOf` argument which will implicitly also set the `flag` argument to true, so you only need to specify one of both arguments.

```php
$data = [
    'flags' => ['is_great'];
    'another_set_of_flags' => ['!is_weak'];
];

#[VOM\Model]
class RootClass
{
    #[VOM\Property]
    public Flags $flags;
}

#[VOM\Model]
class Flags
{
    #[VOM\Property('is_great', flag: true)]
    public bool $great;
    
    #[VOM\Property('is_weak', flagOf: 'another_set_of_flags')]
    public bool $weak;
}
```

#### DateTime

If VOM feeds a property that is a DateTime or DateTimeImmutable type, it will automatically convert the input value into the respective object.

```php
$data = [
    'createdAt' => '2024-01-20 06:00:00',
    'sometime' => '1 year ago',
];

#[VOM\Model]
class MyModel
{
    #[VOM\Property]
    public \DateTime $createdAt;
    
    #[VOM\Property]
    public \DateTimeImmutable $sometime;
}
```

### Nesting with Accessors

Combining the nested flag and the accessor gives you full control over your desired data transformation. Here is a slightly advanced example:

```php
$data = [
    'somewhere' => [
        'but_not_root' => 'I live somewhere but not on the root',
    ],
    'second' [
        'value' => 'I am matching the target data structure'
        'effectivelyOnSecondDimension' => 'also on second dimension because third dimension is nested:false',
        'again' => [
            'aNestedAccessor' => 'I am nested again even if my parent is not',        
        ]
    ],
];

#[VOM\Model]
class RootClass
{
    #[VOM\Property('somewhere.but_not_root')]
    public string $rooted;
    
    #[VOM\Property('second')]
    public SecondDimension $nested;
}

#[VOM\Model]
class SecondDimension
{
    #[VOM\Property]
    public string $value;
    
    #[VOM\Property('third', nested: false)]
    public ThirdDimension $deeper;
}

#[VOM\Model]
class ThirdDimension
{
    #[VOM\Property]
    public string $effectivelyOnSecondDimension;
    
    #[VOM\Property('again.aNestedAccessor')]
    public string $effectivelyOnThirdDimension;
}
```

# Versatile Object Mapper Documentation

[![Integration](https://github.com/zolex/vom/workflows/Integration/badge.svg)](https://github.com/zolex/vom/actions)
[![Release](https://github.com/zolex/vom/workflows/Release/badge.svg)](https://github.com/zolex/vom/actions)

[![Code Coverage](https://codecov.io/gh/zolex/vom/graph/badge.svg?token=RI2NX4S89I)](https://codecov.io/gh/zolex/vom)
[![Type Coverage](https://shepherd.dev/github/zolex/vom/coverage.svg)](https://codecov.io/gh/zolex/vom)
[![Latest Stable Version](http://poser.pugx.org/zolex/vom/v)](https://packagist.org/packages/zolex/vom)
[![Latest Unstable Version](http://poser.pugx.org/zolex/vom/v/unstable)](https://packagist.org/packages/zolex/vom)

<!--[![Required PHP Version](http://poser.pugx.org/zolex/vom/require/php)](https://packagist.org/packages/zolex/vom)-->
[![License](http://poser.pugx.org/zolex/vom/license)](https://packagist.org/packages/zolex/vom)
[![Total Downloads](http://poser.pugx.org/zolex/vom/downloads?update=2)](https://packagist.org/packages/zolex/vom)
[![Monthly Downloads](http://poser.pugx.org/zolex/vom/d/monthly?update=2)](https://packagist.org/packages/zolex/vom)

![VOM](logo.png)

The Versatile Object Mapper - or in short VOM - is a PHP library to transform any data structure into strictly typed models, by simply adding PHP attributes to existing classes.

<!-- toc -->

- [Recommended Workflow](#recommended-workflow)
- [The Object Mapper](#the-object-mapper)
- [Denormalization](#denormalization)
- [Normalization](#normalization)
- [Attribute Configuration](#attribute-configuration)
  * [Constructor Arguments](#constructor-arguments)
  * [Constructor Property Promotion](#constructor-property-promotion)
  * [The Accessor](#the-accessor)
  * [Nested properties](#nested-properties)
  * [Collections](#collections)
  * [Data Types](#data-types)
    + [Booleans](#booleans)
    + [Flags](#flags)
    + [DateTime](#datetime)
  * [Nesting with Accessors](#nesting-with-accessors)
- [Context](#context)
  * [Groups](#groups)
  * [Skip Null Values](#skip-null-values)
  * [Root Fallback](#root-fallback)
  * [Object to Populate](#object-to-populate)

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
        \Zolex\VOM\Symfony\PropertyInfo\PropertyInfoExtractorFactory::create();
    ),
    \Symfony\Component\PropertyAccess\PropertyAccess::createPropertyAccessor(),
);
```

> [!TIP]
> The `PropertyInfoExtractorFactory` creates a default set of extractors utilizing Reflection and PhpDoc. Several other extractors are available such as PHPStan an Doctrine. You can write a custom extractor to give VOM additional information on your model's properties.

## Denormalization

The object mapper can process any kind of PHP data structure as input. This includes arrays, plain old PHP objects, class instances and any combination and nesting of all these.
So the input data might be an indexed array, an associative array an stdClass, other class instances with private properties and their own getters and setters as well as several collections.
To create a model instance from the input data, simply call the `denormalize()` method on the mapper.

A very common use-case is to deserialize json for example with `json_decode()` or using symfony's serializer and pass the result to the `denormalize()` method.

> [!NOTE]
> Right now VOM implements only the symfony normalizer and denormalizer interfaces. Until release of version `0.1.0` it will also implement the serializer and normalizer-aware interfaces, so you won't even need to deserialize input before passing it to VOM!  

```php
$person = $objectMapper->denormalize($data, Person::class);
```

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


## Attribute Configuration

When attributes with no arguments are added to the model properties, VOM will utilize its default behavior, which essentially is the same as the standard symfony object normalizer.
Meaning that in this case no actual data transformation will be done, it will simply inject the values from the data structure where they match in the same structure of your models.

```php
use Zolex\VOM\Mapping as VOM;

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

```php
$data = [
    'rooted' => 'I live on the root',
    'nested' => (object)[
        'value' => 'I am a nested value'
    ];
];

$objectMapper->denormalize($data, RootClass:class);
```

As the data structures and names of all properties are identical, no additional configuration is required. _But as mentioned above, in this scenario you probably just want to use a symfony normalizer to save resources._

> [!TIP]
> Every model that should be processed by VOM needs the `#[VOM\Model]` attribute, so does every property need the `#[VOM\Property]`attribute.
> This includes every nested model and property. On the one hand this increases performance by not trying to process each and everything.
> It can make sense to have additional nested models and properties which are never touched the VOM, for example if you use some of the fed data,
> to do further computations and add it to a model, before returning it to the client, sending it to an API or storing it in the database.

### Constructor Arguments

The `VOM\Property` attribute can be added on constructor arguments. VOM will pass the mapped values into the constructor. All required arguments must be property mapped and pre present in the source data. Otherwise VOM can not create an instance ob the model. Nullable arguments and those with a default value are optional in the source data.

```php
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class ConstructorArguments
{
    private int $id;
    private string $name;
    private ?bool $nullable;
    private bool $default;

    public function __construct(
        #[VOM\Property]
        int $id,
        #[VOM\Property]
        string $name,
        #[VOM\Property]
        ?bool $nullable,
        #[VOM\Property]
        bool $default = true
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->nullable = $nullable;
        $this->default = $default;
    }
}
```

### Constructor Property Promotion

Also constructor property promotion can be handled by VOM similar to normal constructor arguments.

```php
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class PropertyPromotion
{
    public function __construct(
        #[VOM\Property]
        private int $id,
        #[VOM\Property]
        private string $name,
        #[VOM\Property]
        private ?bool $nullable,
        #[VOM\Property]
        private bool $default = true,
    ) {
 }
```

### Method Calls

VOM Properties can also be added to public method arguments using the same technique. VOM will extract the source data for you and call the method with the specified arguments. This is mainly useful if your model expects all or some of the data using a method call and there is no other way to inject it.

```php
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class Calls
{
    private int $id;
    private string $name;

    public function setData(
        #[VOM\Property]
        int $id,
        #[VOM\Property]
        string $name,
    ): void {
        $this->id = $id;
        $this->name = $name;
    }
}
```

> [!NOTE]
> When you are following the naming conventions of setters (e.g. `setName()`for a private `$name` property, specifying the property attributes on the setter method is not required. VOM can handle that using the attribute on the class property itself!


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
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class RootClass
{
    #[VOM\Property]
    public string $onTheRoot;
    
    #[VOM\Property(accessor: 'deeper.inThe.structure')]
    public string $fromTheDeep;
}
```

```php
$data = [
    'onTheRoot' => 'I live on the root',
    'deeper' [
        'inThe' => [
            'structure' => 'I live somewhere deeper in the data structure'
        ]
    ]
];

$objectMapper->denormalize($data, RootClass::class);
```


### Nested properties

The data structures can be as deeply nested as you want. By default, every property has the nested argument set to true, which results in the behavior of a standard denormalizer as described above.
When a property is an array or another class and has the nested argument set to false, VOM will reset the all accessors of the directly nested attributes to the source data root,
instead of applying the accessors where they appear in your model structure.

```php
use Zolex\VOM\Mapping as VOM;

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

```php
$data = [
    'rooted' => 'I live on the root',
    'value' => 'I am on the input data root too'
];

$objectMapper->denormalize($data, RootClass::class);
```

VOM will only reset the source to the data root for nested entities that explicitly have the flag set to false, which means that you can decide where exactly you are expecting the input data on each level of deeply nested structures.

```php
use Zolex\VOM\Mapping as VOM;

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

```php
$data = [
    'rooted' => 'I live on the root',
    'deeper' [
        'effectivelyFromSecondDimension' => 'I am a nested value on the second dimension'
    ]
];

$objectMapper->denormalize($data, RootClass::class);
```

### Collections

VOM can process several types of list, like arrays or doctrine collections, basically any iterable that can be detected as such by the `PropertyInfoExtractor`.

```php
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class RootClass
{
    /**
     * @var NestedClass[] 
     */
    #[VOM\Property]
    public array $valueCollection;
}

#[VOM\Model]
class NestedClass
{
    #[VOM\Property]
    public string $value;
}
```

If you want to pass the `denormalize()` method a collection, it is required to also pass the array notation of the model class (using the square brackets `[]`).

```php
$data = [
    'valueCollection' [
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

$person = $objectMapper->denormalize($collectionOfPeople, RootClass::class.'[]');
$person = $objectMapper->denormalize($collectionOfPeople, 'RootClass[]');
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
    false, 0, '0', 'off', 'OFF', 'no', 'NO', 'n', 'N'
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

The first flag-type is the `Common Flag`. These flags must be strings that sit in an array.
The presence of the flag will result in a true value. It's absence will result in a false value (or null if your property is nullable).
Additionally, a Common flag can be explicitly set to false by prepending an exclamation mark (!) on the flag value.

```php
use Zolex\VOM\Mapping as VOM;

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

```php
$data = [
    'flags' => ['is_great', '!is_weak'];
];
$objectMapper->denormalize($data, RootClass::class);
```

##### Model Flag

The second flag-type is the `Model Flag`. It's not really a flag but just another model that can contain any properties, for example a label.
If it has a bool property with the flag argument set to true, this will then become true for any value that is not in the false list of the boolean type documented above.

```php
use Zolex\VOM\Mapping as VOM;

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
```

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

$objectMapper->denormalize($data, RootClass::class);
```

#### DateTime

If VOM feeds a property that is a DateTime or DateTimeImmutable type, it will automatically convert the input value into the respective object.

```php
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class DateTimeModel
{
    #[VOM\Property]
    public \DateTime $createdAt;
    
    #[VOM\Property]
    public \DateTimeImmutable $sometime;
}
```

```php
$data = [
    'createdAt' => '2024-01-20 06:00:00',
    'sometime' => '1 year ago',
];

$objectMapper->denormalize($data, DateTimeModel::class);
```

For normalization purpose, the `dateTimeFormat` argument can be specified on the Property attribute. The default format is `RFC3339_EXTENDED`.

```php
#[VOM\Property(dateTimeFormat: \DateTimeInterface::W3C)]
public \DateTime $createdAt;
```

### Nesting with Accessors

Combining the nested flag and the accessor gives you full control over your desired data transformation. Here is a slightly advanced example:

```php
use Zolex\VOM\Mapping as VOM;

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

$objectMapper->denormalize($data, RootClass::class);
```

## Context

VOM's `normalize()` and `denormalize()` methods accept several useful properties in the context argument, sticking to existing standards, so that it integrates seamlessly with Symfony and API-Platform where applicable.

### Groups

```php
use Symfony\Component\Serializer\Annotation\Groups;
use Zolex\VOM\Mapping as VOM;

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
```

### Object to Populate

If you already have an object that you want to populate, you can pass it to the denormalize method. This can be useful when you are for example reading an entity from a database and want to update values on it.

```php
$someModel = new SomeModel();
$someModel = $objectMapper->denormalize($data, SomeModel::class, context: ['object_to_populate' => $someModel]);
```


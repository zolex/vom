# Versatile Object Mapper Documentation

[![Release](https://github.com/zolex/vom/workflows/Release/badge.svg)](https://github.com/zolex/vom/actions)
![Version](https://img.shields.io/packagist/v/zolex/vom)
[![Integration](https://github.com/zolex/vom/workflows/Integration/badge.svg)](https://github.com/zolex/vom/actions)
[![Code Coverage](https://codecov.io/gh/zolex/vom/graph/badge.svg?token=RI2NX4S89I)](https://codecov.io/gh/zolex/vom)
![License](https://img.shields.io/packagist/l/zolex/vom)
![Downloads](https://img.shields.io/packagist/dt/zolex/vom)

![VOM](logo.png)

![PHP](https://img.shields.io/badge/php-%23777BB4.svg?style=for-the-badge&logo=php&logoColor=white)
![Symfony](https://img.shields.io/badge/symfony-%23000000.svg?style=for-the-badge&logo=symfony&logoColor=white)
![Laravel](https://img.shields.io/badge/laravel-%23FF2D20.svg?style=for-the-badge&logo=laravel&logoColor=white)


The Versatile Object Mapper - or in short VOM - is a PHP library to transform any data structure into strictly typed models, by simply adding PHP attributes to existing classes.

<!-- toc -->

- [The Object Mapper](#the-object-mapper)
  * [Without Framework](#plain-old-php)
  * [Laravel Framework](#laravel-framework)
  * [Symfony Framework](#symfony-framework)
  * [Denormalization](#denormalization)
  * [Normalization](#normalization)
  * [Deserialization](#deserialization)
  * [Serialization](#serialization)
- [Attribute Configuration](#attribute-configuration)
  * [The Accessor](#the-accessor)
  * [Constructor Arguments](#constructor-arguments)
  * [Constructor Property Promotion](#constructor-property-promotion)
  * [Factory Methods](#factory-methods)
    + [Factory in another class](#factory-in-another-class)
  * [Denormalizer Methods](#denormalizer-methods)
  * [Normalizer Methods](#normalizer-methods)
  * [Custom serialization](#custom-serialization)
  * [Method Dependencies](#method-dependencies)
  * [Disable Nesting](#disable-nesting)
  * [Root flag](#root-flag)
  * [Collections](#collections)
    + [Native Array Collections](#native-array-collections)
    + [ArrayAccess Collections](#arrayaccess-collections)
    + [Doctrine Collections](#doctrine-collections)
    + [Denormalize a Collection](#denormalize-a-collection)
  * [Collection of Collections](#collection-of-collections)
  * [Data Types](#data-types)
    + [Strict Types](#strict-types)
    + [Union Types](#union-types)
    + [Booleans](#booleans)
    + [DateTime](#datetime)
  * [Value Map](#value-map)
  * [Scenarios](#scenarios)
  * [Regular Expression Extractors](#regular-expression-extractors)
- [Interfaces and Abstract Classes](#interfaces-and-abstract-classes)
- [Context](#context)
  * [Skip Null Values](#skip-null-values)
  * [Disable Type Enforcement](#disable-type-enforcement)
  * [Object to Populate](#object-to-populate)
  * [Groups](#groups)
  * [Circular References](#circular-references)

<!-- tocstop -->

## The Object Mapper

### Plain old PHP

Without a framework, you can construct the mapper yourself or simply use the included factory.
Also see the [plain php example](https://github.com/zolex/vom-examples/tree/main/without-framework).
You can pass the `create()` method any `\Psr\Cache\CacheItemPoolInterface` to cache the model's metadata and avoid analyzing it on each execution of your application.

```php
$objectMapper = \Zolex\VOM\Serializer\Factory\VersatileObjectMapperFactory::create();
```

### Laravel Framework

In Laravel the `VersatileObjectMapper` is registered for dependency injection. Also see the [Laravel example with Dependency Injection](https://github.com/zolex/vom-examples/tree/main/laravel).

```php
use Zolex\VOM\Serializer\VersatileObjectMapper;

class ExampleController
{
    public function __construct(private VersatileObjectMapper $objectMapper)
    {
    }
    
    public function deserialize(): void
    {
        $person = $this->objectMapper->deserialize('{"id": 123, "firstname": "Peter"}', Person::class);
    }
}
```

If you prefer, you can also get a VOM instance using Laravel's `app()` or `resolve()` etc.

```php
$objectMapper = resolve(VersatileObjectMapper::class);
$objectMapper = app(VersatileObjectMapper::class);
```

### Symfony Framework

In symfony framework you should use dependency injection to gain access to the preconfigured object mapper service. Also see the [Symfony example](https://github.com/zolex/vom-examples/tree/main/symfony-framework).
The recommended way to use it is by type-hinting Symfony's `SerializerInterface` or `VersatileObjectMapper`.

The only difference is, that `VersatileObjectMapper` by default processes the VOM attributes and the standard symfony serializer needs additional context to enable the VOM features.

```php
use Zolex\VOM\Serializer\VersatileObjectMapper;

class AnySymfonyService
{
    public function __construct(private VersatileObjectMapper $objectMapper)
    {
    }
    
    public function deserialize(): void
    {
        $person = $this->objectMapper->deserialize('{"id": 123, "firstname": "Peter"}', Person::class);
    }
}
```

Symfony Serializer needs additional context to utilize the Versatile Object Mapper. This is required to not interfere with the framework's behavior if not explicitly wanted.
A particular use-case where it would otherwise produce unwanted results is API-Platform, where it would always return the VOM-normalized data if VOM wasn't disabled by default.

_For technical details, check the [StateProvider](https://github.com/zolex/vom-examples/tree/main/api-platform-custom-state/src/State/PersonStateProvider.php) and the [Person Resource](https://github.com/zolex/vom-examples/tree/main/api-platform-custom-state/src/ApiResource/Person.php) in the API-Platform example with custom state
as well as the [Person Resource](https://github.com/zolex/vom-examples/tree/main/api-platform-doctrine/src/Entity/Person.php) in the API-Platform example with Doctrine._

```php
use Symfony\Component\Serializer\SerializerInterface;
class AnySymfonyService
{
    public function __construct(private SerializerInterface $serializer)
    {
    }
    
    public function deserialize(): void
    {
        $person = $this->serializer->deserialize('{"id": 123, "firstname": "Peter"}', Person::class, context: ['vom' => true]);
    }
}
```

> [!TIP]
> The `PropertyInfoExtractorFactory` creates a default set of extractors utilizing Reflection and PhpDoc. Several other extractors are available in Symfony, such as PHPStan and Doctrine. You can also write a custom extractor to give VOM additional information on your model's properties.

### Denormalization

Denormalization is the process of mapping arbitrary, untyped data to strictly typed models. Other well known synonyms for this process are hydration or transformation.

If your data already is an array you can call the `denormalize()` method on the mapper to create a model instance from it.

```php
$person = $objectMapper->denormalize($data, Person::class);
```

### Normalization

Normalization is VOM's process of mapping a strictly typed model to an arbitrary array with eventually untyped or at least not strictly typed values.

If you need a native php array representation of your model you can call the `normalize()` method and pass it your model.

```php
$array = $objectMapper->normalize($personModel);
```

If you need it converted to an object, use VOM's static `toObject()` method and pass it the normalization result.
This is not simply casting, but recursively converts the whole data structure to an `stdClass`, just leaving indexed, sequential arrays intact.

```php
$object = \Zolex\VOM\Serializer\VersatileObjectMapper::toObject($array);
```

### Deserialization

VOM can process several kinds of serialized data, such as JSON or XML. To create a model instance from the string, simply call the `deserialize()` method on the mapper.

```php
$person = $objectMapper->deserialize($jsonString, Person::class);
```

### Serialization

To create a string representation of a model, such as JSON, you can call the `serialize()` method on the object mapper.

```php
$jsonString = $objectMapper->serialize($personModel, 'json'); // json is the default
```

## Attribute Configuration

When attributes with no arguments are added to the model properties, VOM will utilize its default behavior, which essentially is the same as the standard symfony object normalizer.
Meaning that in this case no actual data transformation/mapping will be done, it will simply inject the values from the data structure where they match in the same structure of your models.

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

As the data structures and names of all properties are identical, no additional configuration is required.
_But as mentioned above, in this scenario you probably just want to use a symfony normalizer to save resources._

> [!TIP]
> Every model that should be processed by VOM needs the `#[VOM\Model]` attribute, so does every property need the `#[VOM\Property]`attribute.
> This includes every nested model and property. On the one hand this increases performance by not trying to process each and everything.
> It can make sense to have additional nested models and properties which are never touched the VOM, for example if you use some of the fed data,
> to do further computations and add it to a model, before returning it to the client, sending it to an API or storing it in the database.


### The Accessor

The first argument of the property attribute is the accessor. When not provided, it uses the property's name.
The accessor is not just the name of a parameter but [symfony property access syntax](https://symfony.com/doc/current/components/property_access.html).

```php
#[VOM\Property('[source_param]')]
public string $value;
```

Or to be more explicit:

```php
#[VOM\Property(accessor: '[source_param]')]
public string $value;
```

The following example maps the value for `$fromTheDeep` from a nested array in the source data.

```php
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class RootClass
{
    #[VOM\Property]
    public string $onTheRoot;
    
    #[VOM\Property('[deeper][inThe][structure]')]
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

### Constructor Arguments

Similar to the `VOM\Property` attribute there is the `VOM\Argument` attribute, that can be added on constructor arguments.
All options for the property attribute also exist for the argument attribute.
_(Actually both are using the same abstract class under the hood, this differentiation predominantly exists for better semantics)._
VOM will pass the mapped values into the constructor. All required arguments must be configured with the attribute,
otherwise VOM can not create an instance of the model. Nullable arguments and those with a default value are optional.

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
        #[VOM\Argument]
        int $id,
        #[VOM\Argument(accessor: '[path][to][name]')]
        string $name,
        #[VOM\Argument]
        ?bool $nullable,
        #[VOM\Argument]
        bool $default = true
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->nullable = $nullable;
        $this->default = $default;
    }
}
```

> [!NOTE]
> When using the [`object_to_populate` context](#object-to-populate), the constructor arguments and constructor property promotion will be skipped.

#### Serialized Objects

In case the source data is a serialized representation of the model, the constructor (or factory) can have a single string argument with the `serialized` option set to `true`.

See [Custom serialization](#custom-serialization) to implement the equivalent normalizer.

```php
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class SerializedObject
{
    private int $id;
    private string $name;

    public function __construct(
        #[VOM\Argument(serialized: true)]
        string $data,
    ) {
        [$this->id, $this->name] = explode(':', $data);
    }
}
```

```php
$objectMapper->denormalize('123:test', SerializedObject::class);
```

### Constructor Property Promotion

Also, constructor property promotion can be handled by VOM in the same way as the normal constructor arguments described above.

```php
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class PropertyPromotion
{
    public function __construct(
        #[VOM\Argument]
        private int $id,
        #[VOM\Argument]
        private string $name,
        #[VOM\Argument]
        private ?bool $nullable,
        #[VOM\Argument]
        private bool $default = true,
    ) {
 }
```

### Factory Methods

An alternative to injecting values in the model's constructor, it is also possible to configure factory methods on the model using the `VOM\Factory` together with the `VOM\Argument` attributes.

```php
#[VOM\Model]
class ModelWithFactory
{
    private string $modelName;
    private string|int $modelGroup;

    private function __construct()
    {
    }

    #[VOM\Factory]
    public static function create(
        #[VOM\Argument]
        string $name,
        #[VOM\Argument]
        string|int|null $group
    ): self {
        $instance = new self();
        $instance->setModelName($name);
        if (null !== $group) {
            $instance->setModelGroup($group);
        }

        return $instance;
    }
    
    public function setModelName(string $name): void
    {
        $this->modelName = $name;
    }
    
    public function setModelGroup(string|int $group): void
    {
        $this->mdoelGroup = $group;
    }
}
```

In the case that your source data is inconsistent and/or your model is instantiable with different sets of required properties, you can provide multiple factories that VOM will try to instantiate your model with.
Optionally you can provide a priority for each factory, the higher the value, the earlier VOM with call it. If no priority is given, the order of appearance in the code is used. The first successful factory method wins.

```php
#[VOM\Model]
class ModelWithFactory
{
    #[VOM\Factory]
    public static function createWithSetOne(/* ... */): self
    {
        // ...
    }
    
    #[VOM\Factory(priority: 100)]
    public static function createWithSetTwo(/* ... */): self
    {
        // ...
    }
}
```

#### Factory in another class

It is also possible to create a factory in another class, for example in a (Doctrine) repository.

```php
#[VOM\Model(factory: [RepositoryWithFactory::class, 'createModelInstance'])]
class ModelWithCallableFactory
{
    // ...
}
```

```php
class RepositoryWithFactory
{
    public static function createModelInstance(
        #[VOM\Argument]
        string $name,
        #[VOM\Argument]
        string|int|null $group = null,
    ): ModelWithCallableFactory {
        $model = new ModelWithCallableFactory();
        $model->setName($name);
        if (null !== $group) {
            $model->setGroup($group);
        }

        return $model;
    }
}
```

### Denormalizer Methods

If your models don't follow the symfony conventions for mutators, VOM can call custom methods with arguments. 
It will query the source data using the argument accessor (the same way as for VOM\Property) and call the method. 
The methods must be prefixed with `set` or `denormalize` and the `VOM\Denormalizer` attribute must be added.
_If a denormalizer method sets a property that matches its name, it is recommended to use the `denormalize` prefix.
Otherwise, symfony would use the mutator method to identify the property type which results in unexpected behavior._

```php
use Symfony\Component\Serializer\Attribute\Groups;
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class Calls
{
    private GenericModel $one;
    private GenericModel $two;

    #[VOM\Denormalizer]
    public function denormalizeOne(
        #[VOM\Argument('[ID_FOR_ONE]')]
        int $id,
        #[VOM\Argument('[NAME_FOR_ONE]')]
        string $name,
    ): void {
        $this->one = new GenericModel($id, $name);
    }
    
    #[VOM\Denormalizer]
    public function denormalizeTwo(
        #[VOM\Argument('[ID_FOR_TWO]')]
        int $id,
        #[VOM\Argument('[NAME_FOR_TWO]')]
        string $name,
    ): void {
        $this->two = new GenericModel($id, $name);
    }
}
```

It is possible to use the Groups attribute to control if the method should be called during denormalization, depending on the groups in the denormalization context.
Groups can be added on denormalizer methods with the `set` prefix (virtual property) or on the related property when using the `denormalize` prefix.

```php
use Symfony\Component\Serializer\Attribute\Groups;
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class Calls
{
    private GenericModel $differentName;
    
    #[Groups('two')]
    private GenericModel $two;

    #[Groups('one')]        
    #[VOM\Denormalizer]
    public function setOne(
        #[VOM\Argument('[ID_FOR_ONE]')]
        int $id,
        #[VOM\Argument('[NAME_FOR_ONE]')]
        string $name,
    ): void {
        $this->differentName = new GenericModel($id, $name);
    }
    
    #[VOM\Denormalizer]
    public function denormalizeTwo(
        #[VOM\Argument('[ID_FOR_TWO]')]
        int $id,
        #[VOM\Argument('[NAME_FOR_TWO]')]
        string $name,
    ): void {
        $this->two = new GenericModel($id, $name);
    }
}
```

```php
$objectMapper->denormalize($data, Calls:class, context: ['groups' => ['one']]);
```

##### Non-Scalar Denormalizer Arguments

To prevent unnecessary usage of denormalizers when dealing with arrays, the denormalizer methods only accept scalar arguments by default.
However, if you have some very special requirements, or you want to denormalize a custom data structure including class instances, the denormalize attribute
allows to enable `array` and `object` typehints using the `allowNonScalarArguments` property.

> [!TIP]
> Consider to use [Collections](#collections) instead of writing a custom denormalizer with the allowNonScalarArguments enabled.

```php
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class BadHabits
{
    private string $name;

    #[VOM\Denormalizer(allowNonScalarArguments: true)]
    public function denormalizeBadHabit(
        #[VOM\Argument('[thing]')]
        object $input,
    ): void {
        if (property_exists($input, 'name')) {
            $this->name = $input->name;
        }
    }
}
```

### Normalizer Methods

Similar to the denormalizer methods, also normalizer methods can be configured to be called during normalization. These methods must not have any required arguments. 
Normalizer methods must be prefixed with `get`, `has`, `is` or `normalize`. Groups can be added on normalizer methods with the first three prefixes (virtual property) or on the related property when using the `normalize` prefix.

Without an accessor, the normalizer must return an array which is merged into the normalized data. So the keys of that returned array will be the keys in the normalized output.

```php
use Symfony\Component\Serializer\Attribute\Groups;
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class Calls
{
    #[VOM\Normalizer]
    public function normalizeData(): array
    {
        return [
            'normalized_key' => 'something',
            'another_key' => 123,
        ]
    }
}
```

If a normalizer has an accessor, the return value can be anything and VOM will put it at the given path.

```php
use Symfony\Component\Serializer\Attribute\Groups;
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class Calls
{
    #[VOM\Normalizer(accessor: '[path][in][output]')]
    public function getAnything(): mixed
    {
        return 'anything, even an object or array';
    }
}
```

### Custom serialization

The normalizer attribute can be added on the `__toString()` method. Note that in this case it must be the only normalizer on the model and return a string representation of the object.

See [Serialized Objects](#serialized-objects) to implement the equivalent denormalizer.

```php
use Symfony\Component\Serializer\Attribute\Groups;
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class SerializedObject
{
    public int $id;
    public string $name;

    #[VOM\Normalizer]
    public function __toString(): string
    {
        return $this->id . ':' . $this->name;
    }
}
```

> [!CAUTION]
> It is possible to normalize data and denormalize a model while maintaining the exact same results, no matter how often you repeat this process.
> If this is one of your requirements, and you are using Normalizer and Denormalizer methods, you have to be careful how you store the injected data
> and how you return it, so that the results are matching.

Here is a short example. Note, that the keys and structure returned from the normalizer method match the accessors from the denormalizer arguments.

```php
use Symfony\Component\Serializer\Attribute\Groups;
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class Calls
{
    private GenericModel $data;

    #[VOM\Denormalizer]
    public function setData(
        #[VOM\Argument('[NESTED][ID_FOR_ONE]')]
        int $id,
        #[VOM\Argument('[NESTED][NAME_FOR_ONE]')]
        string $name,
    ): void {
        $this->data = new GenericModel($id, $name);
    }
    
    #[VOM\Normalizer]
    public function getData(): array
    {
        return [
            'NESTED' => [
              'ID_FOR_ONE' => $this->data->getId(),
              'NAME_FOR_ONE' => $this->data->getName(),
           ],
        ];
    }
}
```

### Method Dependencies

If you need any dependencies in addition to the source data to be mapped, it is possible to inject any object (like a symfony service) into methods.
To do so, just typehint the dependency in the method along with the VOM arguments. All dependencies must be explicitly registered.
In symfony framework, you can simply add any symfony service by adding it to the package config:

_config/packages/zolex_vom.yaml_
```yaml
zolex_vom:
  method_dependencies:
    - '@parameter_bag'
```

Without symfony, you simply call the respective method on the `ModelMetadataFactory`

```php
$factory = new \Zolex\VOM\Metadata\Factory\ModelMetadataFactory(/*...*/);
$factory->injectMethodDependency(new \Some\Dependency());
```

If you are using the `VersatileObjectMapperFactory` you can pass the dependencies to the `create` method.

```php
$vom = VersatileObjectMapperFactory::create(null, [new DependencyA(), new DependencyB()]);
```

**Constructor Dependency**

```php
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class ConstructorDependency
{
    private string $foobar;

    public function __construct(
        #[VOM\Argument]
        int $something,
        ParameterBagInterface $parameterBag,
        #[VOM\Argument]
        string $else
    ): self {
        $this->foobar = $parameterBag->get('foobar');
    }
}
```

**Normalizer Dependency**

```php
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class NormalizerDependency
{
    #[VOM\Normalizer]
    public function getData(
        #[VOM\Argument]
        int $something,
        ParameterBagInterface $parameterBag,
        #[VOM\Argument]
        string $else
    ): array {
        return [
            'foo' => $parameterBag->get('foo')
        ];
    }
}
```

**Denormalizer Dependency**

```php
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class DenormalizerDependency
{
    public string $var;

    #[VOM\Denormalizer]
    public function denormalizeData(
        #[VOM\Argument]
        int $something,
        ParameterBagInterface $parameterBag,
        #[VOM\Argument]
        string $else
    ): void {
        $this->var = $parameterBag->get('foo') ? $something ? $else;
    }
}
```

**Factory Dependency**

```php
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class NormalizerDependency
{
    #[VOM\Factory]
    public static function create(
        #[VOM\Argument]
        int $something,
        ParameterBagInterface $parameterBag,
        #[VOM\Argument]
        string $else
    ): self {
        return new self($parameterBag->get('foo'));
    }
}
```

### Disable Nesting

The data structures can be as deeply nested as you want. By default, nesting is enabled. That is when no accessor is given or the accessor is symfony property access syntax.

If a property is another `VOM\Model` (or any type of collection that contains models) and it has `accessor: false` configured, VOM will look for the nested model's properties on the same level as the property itself. 

```php
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class RootClass
{
    #[VOM\Property]
    public string $rooted;
    
    #[VOM\Property(accessor: false)]
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
    'value' => 'I am on the data root too'
];

$objectMapper->denormalize($data, RootClass::class);
```

VOM only disables nesting for properties, which explicitly have the accessor set to false. So other models that are deeper nested will not be flattened. This allows mapping between nested and _partially_ flattened data structures.

```php
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class RootClass
{
    #[VOM\Property]
    public string $rooted;
    
    #[VOM\Property(accessor: false)]
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
    'value' => 'I am here too!',
    'deeper' [
        'effectivelyFromSecondDimension' => 'I am a nested value on the second dimension'
    ]
];

$objectMapper->denormalize($data, RootClass::class);
```

### Root flag

The `root` flag can can be used on every property (unlike `acessor: false` which only makes sense on properties that are other models). It will make VOM apply the property's accessor to the root of the source data.
When applied on a property that is a model, or any type of collection that contains other models, the data structure within that model will be kept intact. 

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
    #[VOM\Property(root: true)]
    public string $value;
    
    #[VOM\Property]
    public ThirdDimension $deeper;
}

#[VOM\Model]
class ThirdDimension
{
    #[VOM\Property(root: true)]
    public string $effectivelyOnRoot;
}
```

```php
$data = [
    'rooted' => 'I live on the root',
    'value' => 'I am here too!',
    'effectivelyOnRoot' => 'Where else would you expect me?'
];

$objectMapper->denormalize($data, RootClass::class);
```

> [!NOTE]
> A combination of actual accessors, `accessor: false` and `root: true` gives you full control over the data mapping. Some results can be achieved with different combinations of these settings.
> But there are scenarios which can only be achieved with a clever combination of all of them.   


### Collections

VOM can process several types of collections, like native arrays or ArrayObject (including doctrine collections), basically any iterable that can be detected as such by the `PropertyInfoExtractor`.
To tell VOM what types sit in a collection you have to add PhpDoc tags and use the [phpdoc array or collection syntax](https://symfony.com/doc/current/components/property_info.html#type-iscollection) as shown in the following example.

#### Native Array Collections

```php
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class RootClass
{
    // for a native array, use the array syntax
    /** @var Thing[] */
    #[VOM\Property]
    public array $valueCollection;
}

#[VOM\Model]
class Thing
{
    #[VOM\Property]
    public string $value;
}
```

#### ArrayAccess Collections

For all collections that implement `ArrayAccess` the model preferably should have adder and remover methods and the collection should be initialized in the constructor.

```php
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class RootClass
{    
    // for ArrayAccess, use the collection syntax
    /** @var ArrayObject<Thing> */
    #[VOM\Property]
    private ArrayAccess $things;
    
    public function __construct()
    {
        $this->things = new ArrayObject();
    }
    
    public function addThing(Thing $thing): void
    {
        $this->things->append($thing);
    }
    
    public function removeThing(Thing $thing): void
    {
        // remove it from the ArrayAccess
    }
}
```

If you don't need to add or remove single collection items, and in your use-case it is acceptable to overwrite potentially existing items,
a simpler option is to create a mutator method that accepts an array.

```php
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class RootClass
{    
    /** @var ArrayObject<Thing> */
    #[VOM\Property]
    private ArrayAccess $things;
    
    public function setThings(array $things): void
    {
        $this->things = new ArrayObject($things);
    }
}
```

#### Doctrine Collections

If you are working with symfony and create doctrine entities using the maker-bundle, all of this will be generated automatically. The following example is only here to show the extra `VOM\Model` and `VOM\Property` attributes you have to add.
You don't even need to add the `@var` PhpDoc for the collection value type because VOM can determine it by looking at the doctrine entity associations.

```php
use Doctrine\ORM\Mapping as ORM;
use Zolex\VOM\Mapping as VOM;

#[ORM\Entity()]
#[VOM\Model]
class DoctrineAddress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[VOM\Property]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[VOM\Property]
    private ?string $street = null;
}
```

```php
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Zolex\VOM\Mapping as VOM;

#[ORM\Entity]
#[VOM\Model]
class DoctrinePerson
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[VOM\Property]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[VOM\Property]
    private ?string $name = null;

    #[ORM\OneToMany(targetEntity: DoctrineAddress::class)]
    #[VOM\Property]
    private Collection $addresses;

    public function __construct()
    {
        $this->addresses = new ArrayCollection();
    }

    public function addAddress(DoctrineAddress $address): void
    {
        if (!$this->addresses->contains($address)) {
            $this->addresses->add($address);
        }
    }

    public function removeAddress(DoctrineAddress $address): void
    {
        $this->addresses->removeElement($address);
    }
}
```

#### Denormalize a Collection

If you want to pass the `denormalize()` method an array, it is required to also pass the array syntax for the model.

```php
$data = [
    'valueCollection' [
        [
            'value' => 'I am the value'
        ],
        [
            'value' => 'I am another value'
        ]
    ]
];

$person = $objectMapper->denormalize($collectionOfPeople, RootClass::class.'[]');
$person = $objectMapper->denormalize($collectionOfPeople, 'RootClass[]');
```

### Collection of Collections

It is also possible to define properties that are collections which contain further levels of collections.

```php
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class CollectionOfCollections
{
    // an array of arrays
    /** @var array[] */
    #[VOM\Property]
    public array $array;

    // a collection of an array of DateAndTime models
    /** @var \ArrayObject<DateAndTime[]> */
    #[VOM\Property]
    public \ArrayAccess $collection;
}

#[VOM\Model]
class DateAndTime
{
    #[VOM\Property]
    public \DateTime $dateTime;
}
```

With the above configuration you can pass VOM the following data structure for denormalization.

```php
$data = [
    'array' => [
        [1, 2, 3],
        [4, 5, 6],
    ],
    'collection' => [
        [
            ['dateTime' => '2011-05-15 10:11:12'],
            ['dateTime' => '2012-06-16 10:11:12'],
        ],
        [
            ['dateTime' => '2013-07-15 10:11:12'],
            ['dateTime' => '2014-08-15 10:11:12'],
        ],
    ],
];

$collection = self::$serializer->denormalize($data, CollectionOfCollections::class);
```

### Data Types

#### Strict Types

By default, VOM will throw an exception if you are trying to denormalize a value that does not match the expected type. It is possible to [disable strict type checking](#disable-type-enforcement) using the context.

#### Union Types

By utilizing union types, you can work with strict typing and still allow several types to be denormalized.

```php
#[VOM\Property]
public int|float|null $value;
```

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

#### DateTime

If VOM feeds a property that is a DateTime or DateTimeImmutable type, it will automatically convert the input value into the respective object. See more on the Symfony documentation

```php
use Symfony\Component\Serializer\Attribute\Context;
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class DateTimeModel
{
    #[VOM\Property]
    public \DateTime $createdAt;
    
    #[Context(['datetime_format' => \DateTimeInterface::W3C])]
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

### Value Map

A common task is to map single values. To do so you can specify a map in the VOM Property. The keys of the map represent the source values.

```php
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class ValueMap
{
    #[VOM\Property(map: [
        'TYPE1' => 'A',
        'TYPE2' => 'B',
        'TYPE3' => 'C',
    ])]
    public string $type;
}
```

```php
$object = $objectMapper->denormalize(['type' => 'TYPE2'], ValueMap::class);
// $object->type is set to 'B'
```

If the source value is not found in the map, the property will not be set.

```php
$object = $objectMapper->denormalize(['type' => 'INVALID_TYPE'], ValueMap::class);
// $object->type remains uninitialized
```

If the property has a default value and the source value is not found in the map, the default value will be set.

```php
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class ValueMap
{
    #[VOM\Property(map: [
        'RED' => '#FF0000',
        'GREEN' => '#00FF00',
        'BLUE' => '#0000FF',
    ])]
    public string $color = '#000000';
}
```

```php
$object = $objectMapper->denormalize(['color' => 'RAINBOW'], ValueMap::class);
// $object->color is '#000000', the default value
```

## Regular Expression Extractors

If the data is a string consisting of multiple values, you can use regexp extractors to read the properties into your model.

On the `VOM\Model` attribute the regexp extractor should have named subpattern matching the property accessors.

```php
use Zolex\VOM\Mapping as VOM;

#[VOM\Model(extractor: '/^(?<filename>.+),tag:(?<tag>.*),description:(?<text>.*)/')]
class RegexpExtractorModel
{
    #[VOM\Property]
    public string $filename;
    
    #[VOM\Property]
    public string $tag;
    
    #[VOM\Property]
    public string $text;
}
```

```php
$model = $objectMapper->denormalize('image1.jpg,tag:foobar,description:source data is shit', RegexpExtractorModel::class);
```

On the `VOM\Property` attribute the regexp extractor must have exactly one subpattern to extract the data.

```php
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class RegexpExtractorProperty
{
    #[VOM\Property(extractor: '/^([^,]+)/')]
    public string $filename;
    
    #[VOM\Property(extractor: '/tag:([^,]+)/')]
    public string $tag;
    
    #[VOM\Property(
        map: ['visible' => true, 'hidden' => false],
        extractor: '/visibility:(visible|hidden)/'
    )]
    public bool $isVisible;
```

```php
$model = $objectMapper->denormalize('image1.jpg,tag:foobar,visibility:hidden', RegexpExtractorProperty::class);
```

## Scenarios

If there is more than one representation of your normalized data, the `scenario` argument allows to switch between different mappings.

```php
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class ModelWithScenarios
{
    #[VOM\Property]
    #[VOM\Property('[FIRST_NAME]', scenario: 'second')]
    #[VOM\Property('[name][first]'), scenario: 'third'])]
    public string $firstName;
    
    #[VOM\Property]
    #[VOM\Property('[LAST_NAME]', scenario: 'second')]
    #[VOM\Property('[name][last]'), scenario: 'third'])]
    public string $lastName;
}
```

The scenario can be passed in the context of the `serialize`, `deserialize`, `normalize` and `denormalize` methods of the ObjectMapper.

```php
$data = [
    'firstName' => 'Peter',
    'lastName' => 'Parker'
];
$objectMapper->denormalize($data, ModelWithScenarios::class);

$data = [
    'FIRST_NAME' => 'Peter',
    'LAST_NAME' => 'Parker'
];
$objectMapper->denormalize($data, ModelWithScenarios::class, null, ['scenario' => 'second']);

$data = [
    'name' => [
        'first' => 'Peter',
        'last' => 'Parker'
    ]
];
$objectMapper->denormalize($data, ModelWithScenarios::class, null, ['scenario' => 'third']);
```

The `scenario` argument can also be used on the arguments of constructors, factories and denormalizers as well as on normalizer methods.

**Constructor Scenarios**

```php
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class ModelWithScenarios
{
    public function __construct(
      #[VOM\Argument]
      #[VOM\Argument('[FIRST_NAME]', scenario: 'second')]
      string $firstName,
      
      #[VOM\Argument]
      #[VOM\Argument('[LAST_NAME]', scenario: 'second')]
      string $lastName,
    ) {
        // ...
    }
}
```

**Factory Scenarios**

```php
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class ModelWithScenarios
{
    #[VOM\Factory]
    public function create(
      #[VOM\Argument]
      #[VOM\Argument('[FIRST_NAME]', scenario: 'second')]
      string $firstName,
      
      #[VOM\Argument]
      #[VOM\Argument('[LAST_NAME]', scenario: 'second')]
      string $lastName,
    ): self {
        // ...
    }
}
```

**Denormalizer Scenarios**

```php
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class ModelWithScenarios
{
    #[VOM\Denormalizer]
    public function denormalizeValues(
      #[VOM\Argument]
      #[VOM\Argument('[FIRST_NAME]', scenario: 'second')]
      string $firstName,
      
      #[VOM\Argument]
      #[VOM\Argument('[LAST_NAME]', scenario: 'second')]
      string $lastName,
    ) {
        // ...
    }
}
```

**Normalizer Scenarios**

```php
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class ModelWithScenarios
{
    private string $firstName = 'Peter';
    private string $lastName = 'Parker';

    #[VOM\Normalizer(accessor: '[firstName]')]
    public function normalizeFirstNameForDefaultScenario(): string {
        return $this->firstName;
    }
    
    #[VOM\Normalizer(accessor: '[FIRST_NAME]', scenario: 'second')]
    public function normalizeFirstNameForSecondScenario(): string {
        return strtoupper($this->firstName);
    }
    
    #[VOM\Normalizer(accessor: '[lastName]')]
    public function normalizeLastNameForDefaultScenario(): string {
        return return $this->lastName;
    }
    
    #[VOM\Normalizer(accessor: '[LAST_NAME]', scenario: 'second')]
    public function normalizeFirstNameForSecondScenario(): string {
        return strtoupper($this->lastName);
    }
}
```

> [!NOTE]
> For fine-grained control, scenarios can also be combined with the `Groups` feature.

```php
use \Symfony\Component\Serializer\Attribute\Groups;
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class ModelWithScenarios
{
    private string $firstName = 'Peter';
    private string $lastName = 'Parker';

    #[Groups('firstname')]
    #[VOM\Normalizer(accessor: '[firstName]')]
    public function getFirstNameForDefaultScenario(): string {
        return $this->firstName;
    }
    
    #[Groups('firstname')]
    #[VOM\Normalizer(accessor: '[FIRST_NAME]', scenario: 'second')]
    public function getFirstNameForSecondScenario(): string {
        return strtoupper($this->firstName);
    }
    
    #[Groups('lastname')]
    #[VOM\Normalizer(accessor: '[lastName]')]
    public function getLastNameForDefaultScenario(): string {
        return return $this->lastName;
    }
    
    #[Groups('lastname')]
    #[VOM\Normalizer(accessor: '[LAST_NAME]', scenario: 'second')]
    public function getFirstNameForSecondScenario(): string {
       return strtoupper($this->lastName);
    }
}
``` 


## Interfaces and Abstract Classes

When dealing with objects that are fairly similar or share properties, you can use interfaces or abstract classes.
VOM allows to serialize and deserialize such objects using discriminator class mapping.
The discriminator is the property used to differentiate between the possible classes.

> [!NOTE]
> Additionally to the Symfony `DiscriminatorMap` attribute, you must provide the `VOM\Model` attribute on
> the abstract class or interface and also the `VOM\Property` attribute on the discriminator.

```php
use Symfony\Component\Serializer\Attribute\DiscriminatorMap;
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
#[DiscriminatorMap(typeProperty: 'whichThing', mapping: [
    'one-thing' => OneThing::class,
    'another-thing' => AnotherThing::class,
])]
abstract class Thing
{
    #[VOM\Property]
    public string $whichThing;
    // ...
}
```

```php
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class OneThing extends Thing
{
    // ...
}
```

```php
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class AnotherThing extends Thing
{
    // ...
}
```

Now you can denormalize using the abstract class (or interface) and VOM will create the proper model depending on the discriminator.

```php
$data = ['whichThing' => 'one-thing'];
$thing = $objectMapper->denormalize($data, Thing::class);
// $thing is an instance of `OneThing`

$data = ['whichThing' => 'another-thing'];
$thing = $objectMapper->denormalize($data, Thing::class);
// $thing is an instance of `AnotherThing`
```

According to the above example, VOM will now add the `whichThing` property with the respective value during normalization:

```php
$thing = new OneThing();
$data = $objectMapper->normalize($oneThing);
// $data['whichThing'] will be 'one-thing'
```


## Context

VOM's `normalize()` and `denormalize()` methods accept several useful properties in the context argument, sticking to existing standards, so that it integrates seamlessly with Symfony and API-Platform where applicable.

### Skip Null Values

During normalization values that are null can be skipped, so they won't be included in the normalized data.

```php
$someArray = $objectMapper->normalize($someModel, context: ['skip_null_values' => true]);
```

### Disable Type Enforcement

Not recommended, but sometimes necessary, you can disable type enforcement/strict typing during denormalization.

```php
$someArray = $objectMapper->normalize($someModel, context: ['disable_type_enforcement' => true]);
```

### Object to Populate

If you already have an object that you want to populate, you can pass it to the denormalize method. This can be useful when you are for example reading an entity from a database and want to update values on it.

```php
$someModel = new SomeModel();
$someModel = $objectMapper->denormalize($data, SomeModel::class, context: ['object_to_populate' => $someModel]);
```

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

### Circular References

Sometimes your models reference each other in a way called circular reference. 
One way to deal with that is configuring the groups context.

```php
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class Person
{
    #[VOM\Property]
    public int $id;
    
    #[VOM\Property]
    public string $name;
    
    #[VOM\Property]
    public Address $address;
}
```

```php
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class Address
{
    #[VOM\Property]
    public int $id;
    
    #[VOM\Property]
    public string $street;
    
    #[VOM\Property]
    public Person $person;
}
```

```php
$person = new Person();
$person->id = 3;
$person->name = 'Peter Parker';
$person->address = new Address();
$person->address->id = 6;
$person->address->street = 'Examplestreet 123';
$person->address->person = $person;
```

If VOM finds a circular reference, by default it will throw an exception to let you know what is happening.

To ignore any circular references you can set the `skip_circular_reference` context:

```php
$objectMapper->normalize($person, null ['skip_circular_reference' => true]);
// results in the following array
[
    'id' => 3,
    'name' => 'Peter Parker',
    'address' => [
        'id' => 6,
        'street' => 'Examplestreet 123'
    ]
]
```

You can also define a circular reference handler to return a value that you want to replace the circular reference with.

```php
$objectMapper->normalize($person, null, ['circular_reference_handler' => function($object) {
    return sprintf('/%s/%d', get_class($object), $object->id);
}]);
// results in the following array
[
    'id' => 3,
    'name' => 'Peter Parker',
    'address' => [
        'id' => 6,
        'street' => 'Examplestreet 123'
        'person' => '/Person/3'
    ]
]
```

You can increase the circular reference limit to allow embedding circular references until the specified limit is reached (default is 1).

```php
$objectMapper->normalize($person, null, ['circular_reference_limit' => 2]);
// results in the following array
[
    'id' => 3,
    'name' => 'Peter Parker',
    'address' => [
        'id' => 6,
        'street' => 'Examplestreet 123'
        'person' => [
            'id' => 3,
            'name' => 'Peter Parker',
            'address' => [
                'id' => 6,
                'street' => 'Examplestreet 123'   
            ]
        ]
    ]
]
```

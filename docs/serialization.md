# Serialization

The standalone core depends on `SerializerInterface`, not on Symfony Serializer or JMS Serializer:

```php
$factory = new ResponseFactory(new NativeSerializer());
$factory->register('json', new JsonFormatter(new NativeSerializer()));
```

`NativeSerializer` supports scalars, arrays, traversables, `JsonSerializable`, stringable objects, and public object properties. Applications with DTO metadata, groups, name converters, dates, or denormalization should inject an adapter around Symfony Serializer or JMS Serializer.

Serialization is normalization, not validation. Validate input before producing a response, and use an allow-list serializer context when exposing DTOs.

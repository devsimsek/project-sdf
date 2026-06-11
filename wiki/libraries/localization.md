# Localization

The `SDF\Localization\Translator` class provides a singleton-based translation system with dot-notation key lookup, parameter replacement, and pluralization.

## Configuration

Configure locale and fallback in `app/config/localization.php`:

```php
$config['localization'] = [
    'locale' => 'en',
    'fallback_locale' => 'en',
];
```

## Translator API

```php
use SDF\Localization\Translator;

$t = Translator::getInstance();
$t->setLocale('tr');
echo $t->getLocale(); // 'tr'
```

| Method | Description |
|---|---|
| `getInstance()` | Get or create the singleton |
| `setLocale(string $locale)` | Switch the active locale |
| `getLocale(): string` | Return the active locale |

## Translation Files

Files live at `app/lang/{locale}/{namespace}.php`. They return an associative array:

```php
<?php
// app/lang/en/auth.php
return [
    'failed' => 'These credentials do not match our records.',
    'throttle' => 'Too many attempts. Please try again in :seconds seconds.',
];
```

```php
<?php
// app/lang/tr/auth.php
return [
    'failed' => 'Bu kimlik bilgileri kayıtlarımızla eşleşmiyor.',
    'throttle' => 'Çok fazla deneme. Lütfen :seconds saniye sonra tekrar deneyin.',
];
```

## Dot-Notation Lookup

The `get()` method resolves `namespace.key` into `$translations[locale][namespace][key]`:

```php
$t = Translator::getInstance();
echo $t->get('auth.failed'); // "These credentials do not match our records."
```

## Parameter Replacement

Use `:placeholder` in the translation string and pass replacements as the second argument:

```php
echo $t->get('auth.throttle', ['seconds' => 60]);
// "Too many attempts. Please try again in 60 seconds."
```

## Pluralization

The `choice()` method handles plural forms using pipe-separated segments.

### Simple `one|other`

```php
// app/lang/en/app.php
return [
    'apples' => 'one apple|many apples',
];

echo $t->choice('app.apples', 1); // "one apple"
echo $t->choice('app.apples', 5); // "many apples"
```

### Explicit Ranges

```php
// app/lang/en/app.php
return [
    'apples' => '{0} none|[1,9] some apples|[10,*] many apples',
];

echo $t->choice('app.apples', 0);  // "none"
echo $t->choice('app.apples', 3);  // "some apples"
echo $t->choice('app.apples', 15); // "many apples"
```

The `:count` placeholder is automatically available:

```php
return [
    'apples' => '{0} none|[1,9] :count apples|[10,*] :count apples',
];

echo $t->choice('app.apples', 3); // "3 apples"
```

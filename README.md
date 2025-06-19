# lambdatt-php/settings

A SplitPHP Framework plugin for create a general purpose settings control.

---

## Installation

Install via Composer:

```bash
composer require lambdatt-php/settings
```

Run the Migrations:
```bash
php console migrations:apply --module=settings
```

**PS: this can only be installed on a SplitPHP Framework project. For more information refer to: https://github.com/splitphp/core**

## Usage:

- Set a variable and its value:
```php
$this->getService('settings/settings')->change($context, $fieldname, $value);
```
- Retrieve a variable and its value:
```php
$this->getService('settings/settings')->get($context, $fieldname);
```
- Retrieve an object of a context containing all its variables and values:
```php
$this->getService('settings/settings')->contextObject($context);
```
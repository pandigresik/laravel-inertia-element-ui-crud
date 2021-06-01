# element-ui crud generator

[![Latest Version on Packagist](https://img.shields.io/packagist/v/xt/element-ui-crud.svg?style=flat-square)](https://packagist.org/packages/xt/element-ui-crud)
[![Total Downloads](https://img.shields.io/packagist/dt/xt/element-ui-crud.svg?style=flat-square)](https://packagist.org/packages/xt/element-ui-crud)

Generate CRUD for Element-UI (Vue 3) and Inertia Js.

This package will generate files to perform CRUD, based on the database table schema. It will generate all the fields in list, create and update form pages.

## Features
- Generate list, create and update view pages
- Generate controller which perform insert, update and delete
- Generate form for all the fillable fields defined in model file
- Server side validation (required and maximum length) 

## Installation

You can install the package via composer:

```bash
composer require xt/element-ui-crud
```

Install npm package:

```bash
npm install laravel-inertia-element-ui-crud-vue3 --save
```

## Usage

Add following code to the boot method of the app/Providers/AppServiceProvider.php

```php
Inertia::share('flash', function () {
    return [
        'message' => Session::get('message'),
    ];
});

Inertia::share([
    'success' => function () {
        return Session::get('success')
            ? Session::get('success')
            : '';
    },
]);

Inertia::share([
    'error' => function () {
        return Session::get('error')
            ? Session::get('error')
            : '';
    },
]);

Inertia::share([
    'errors' => function () {
        return Session::get('errors')
            ? Session::get('errors')->getBag('default')->getMessages()
            : (object) [];
    },
]);
```

## Command to generate CRUD

```bash
php artisan crud:generator <ControllerName> <ModelName>
```

Prepare model before generate crud

make sure controller name without including controller for example `User` which generates `UserController`

Example:
```bash
php artisan crud:generator User User
```


### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email hiren.reshamwala@gmail.com instead of using the issue tracker.

## Credits

-   [Hiren Reshamwala](https://github.com/hirenreshamwala)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

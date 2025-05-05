# A package for Laravel to merge two Eloquent models into one.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/bernskiold/laravel-record-merge.svg?style=flat-square)](https://packagist.org/packages/bernskiold/laravel-record-merge)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/bernskiold/laravel-record-merge/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/bernskiold/laravel-record-merge/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/bernskiold/laravel-record-merge/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/bernskiold/laravel-record-merge/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/bernskiold/laravel-record-merge.svg?style=flat-square)](https://packagist.org/packages/bernskiold/laravel-record-merge)

## Installation & Usage

You can install the package via composer:

```bash
composer require bernskiold/laravel-record-merge
```

After installing the package, you may publish the configuration file:

```bash
php artisan vendor:publish --provider="Bernskiold\LaravelRecordMerge\LaravelRecordMergeServiceProvider" --tag="config"
```

This will publish a `record-merge.php` file in your `config` directory.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Bernskiold](https://github.com/bernskiold)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

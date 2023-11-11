# SelfPHP Framework - SP Class

## Overview

The `SP` class is a central controller within the SelfPHP Framework, responsible for managing resources, asset handling, and serving as the foundation for controllers and models. This README provides an overview of the class, its methods, and usage.

## Table of Contents

- [Installation](#installation)
- [Usage](#usage)
- [Configuration](#configuration)
- [Methods](#methods)
  - [Constructor](#constructor)
  - [request_config](#request_config)
  - [serve_json](#serve_json)
  - [setup_config](#setup_config)
  - [env](#env)
  - [app_name](#app_name)
  - [domain](#domain)
  - [login_page](#login_page)
  - [dashboard_page](#dashboard_page)
  - [verify_domain_format](#verify_domain_format)
  - [public_path](#public_path)
  - [asset_path](#asset_path)
  - [storage_path](#storage_path)
  - [resource](#resource)
  - [scanDirectory](#scanDirectory)
  - [fileParser](#fileParser)
  - [csvToArray](#csvToArray)
  - [storageAdd](#storageAdd)
  - [initSqlDebug](#initSqlDebug)
  - [debugBacktraceShow](#debugBacktraceShow)

## Installation

To use the `SP` class, include the class file in your project or use Composer to install the package.:

### Include the class file
```php
require_once 'path/to/SP.php';
use SelfPhp\SP;
```

### Composer installation
```bash

composer require selfphp-framework/selfphp-framework

```

## Usage

```php
$sp = new SP();
$sp->setup_config();

// Example: Get application name
$appName = $sp->app_name();

// Example: Serve JSON response
$data = ['key' => 'value'];
$jsonResponse = $sp->serve_json($data);
```

## Configuration

Ensure that the `config` directory contains the necessary configuration files. The `SP` class automatically loads these configurations during initialization.

## Methods

### Constructor

```php
public function __construct()
```

Initializes the `SP` class, loading application configurations.

### request_config

```php
public function request_config($config)
```

Requests and returns a specified configuration file.

### serve_json

```php
public function serve_json(array $data)
```

Returns a JSON-encoded representation of an array.

### setup_config

```php
public function setup_config()
```

Set up configurations.

### env

```php
public function env($var_name)
```

Retrieves the value of an environment variable.

### app_name

```php
public function app_name()
```

Gets the application name.

### domain

```php
public function domain()
```

Retrieves the application domain.

### login_page

```php
public function login_page()
```

Retrieves the login page name.

### dashboard_page

```php
public function dashboard_page()
```

Retrieves the dashboard page name.

### verify_domain_format

```php
public function verify_domain_format($domain=null)
```

Verifies the format of the provided domain.

### public_path

```php
public function public_path($path=null)
```

Constructs the public path by appending the path to the application domain.

### asset_path

```php
public function asset_path($path=null)
```

Constructs the asset path by appending the path to the application domain.

### storage_path

```php
public function storage_path($path=null)
```

Constructs the storage path by appending the path to the application domain.

### resource

```php
public function resource($view, $data=[])
```

Requires and parses a view file, providing the data to be used.

### scanDirectory

```php
public function scanDirectory($resourcePath)
```

Scans a directory and returns an array of file paths.

### fileParser

```php
public function fileParser($data=[], $filename = null)
```

Parses HTML/PHP files with post data.

### csvToArray

```php
public static function csvToArray($filepath, $maxLength = 1000)
```

Converts CSV file data to an associative array.

### storageAdd

```php
public static function storageAdd($fileMetadata, $path)
```

Moves and stores a file in the application's storage directory.

### initSqlDebug

```php
public static function initSqlDebug($dbConnection = null)
```

Initializes SQL debugging based on the DEBUG environment variable.

### debugBacktraceShow

```php
public static function debugBacktraceShow($exception = null)
```

Shows debug backtrace based on the DEBUG environment variable.
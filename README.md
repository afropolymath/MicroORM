# MicroORM
Lightweight PHP ORM Solution. Supports only MySQL for now.
###Installation
Run the following command within the root folder of your application
```
composer require "andela-cnnadi/micro-orm": "dev-master"
```
Or you can just add the package name "andela-cnnadi/micro-orm": "dev-master" as a dependency within your composer.json file.
###Getting Started
To get started you need to configure your database connection. This can be done within the `config` folder in the package directory. There's a file called `config.php` which you can fill out with your database configuration information.
###Start Creating Models
To create a model you need to create a new class that extends the MicroORM class. Each separate model should extend this class. Recommended file structure is shown below. See example for more details.
```
-app
  - vendor
    - micro-orm
  - models
    - ModelA.php
    - ModelB.php
  - index.php
```
Models can then be required within the application as shown below
```
require 'vendor/autoload.php';
require 'models/ModelA.php';
require 'models/ModelB.php';
```

###Model Configuration options
The `$model` variable within the Model class is used to store the field configuration of the model you are creating. It contains the field names as keys and field configurations as value.
The field configuration contains key-value pairs of the various configuration options against the required values. The available configuration options include

- `type` - Type of the field. Available options include int, text, varchar, smallint
- `null` - (optional) True or False value indicating whether the field can be null or not respectively. Defaults to true if nothing is specified
- `size` - Supports fields like varchar, int. Used to specify the maximum length of the field.

An example of a Model using the configuration options is shown below:
```
<?php
class ModelA extends MicroORM {
  protected $model = [
    'title' => [
      'type' => 'varchar',
      'length' => 255,
      'null' => false
    ],
    'content' => [
      'type' => 'text',
      'null' => false
    ]
  ]
}
?>
```

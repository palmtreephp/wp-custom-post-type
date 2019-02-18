# :palm_tree: WordPress Custom Post Types

[![License](http://img.shields.io/packagist/l/palmtree/curl.svg)](LICENSE)

Library to assist in the creation of custom post types within WordPress.

## Requirements
* PHP >= 5.6

## Installation
Use composer to add the package to your dependencies:
```bash
composer require palmtree/wp-custom-post-type
```

## Usage

### Basic Usage
```php
<?php
use Palmtree\WordPress\CustomPostType\CustomPostType;

$project = new CustomPostType('project');

$callToAction = new CustomPostType([
    'post_type' => 'cta',
    'public' => false,
]);
```

### Advanced Usage
```php
<?php
use Palmtree\WordPress\CustomPostType\CustomPostType;

$project = new CustomPostType([
    'post_type' => 'project',
    'front' => 'my-projects',
    'taxonomies' => [
        [
            'name' => 'Project Tags',
            'hierarchical' => false,
        ],
        [
            'name' => 'Project Categories',
            'singluar_name' => 'Project Category',    
        ]
    ],
]);
```

## License
Released under the [MIT license](LICENSE)

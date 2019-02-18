<?php

namespace Palmtree\WordPress\CustomPostType;

use Palmtree\Collection\Collection;

class CustomPostTypeCollection extends Collection
{
    public function __construct($items = [], $type = CustomPostType::class)
    {
        parent::__construct($items, $type);
    }

    public function set($key, $args)
    {
        if (is_array($args) && !isset($args['post_type'])) {
            $args['post_type'] = $key;
        }

        $customPostType = new CustomPostType($args);

        return parent::set($key, $customPostType);
    }
}

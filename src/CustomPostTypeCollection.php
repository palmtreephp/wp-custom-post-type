<?php

namespace Palmtree\WordPress\CustomPostType;

use Palmtree\Collection\Map;

/**
 * @method CustomPostType get(string $key)
 */
class CustomPostTypeCollection extends Map
{
    public function __construct($items = [], $type = CustomPostType::class)
    {
        parent::__construct($type);

        $this->add($items);
    }

    public function set($key, $args)
    {
        if (\is_array($args) && !isset($args['post_type'])) {
            $args['post_type'] = $key;
        }

        $customPostType = new CustomPostType($args);

        return parent::set($key, $customPostType);
    }
}

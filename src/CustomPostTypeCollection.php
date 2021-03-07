<?php

namespace Palmtree\WordPress\CustomPostType;

use Palmtree\Collection\Map;

class CustomPostTypeCollection
{
    /** @var Map<string, CustomPostType> */
    private $map;

    public function __construct($items = [])
    {
        $this->map = new Map(CustomPostType::class);

        $this->map->add($items);
    }

    public function set(string $key, $args): self
    {
        if (\is_array($args) && !isset($args['post_type'])) {
            $args['post_type'] = $key;
        }

        $customPostType = new CustomPostType($args);

        $this->map->set($key, $customPostType);

        return $this;
    }

    public function get(string $key): CustomPostType
    {
        return $this->map->get($key);
    }
}

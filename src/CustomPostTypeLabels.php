<?php

declare(strict_types=1);

namespace Palmtree\WordPress\CustomPostType;

class CustomPostTypeLabels implements \ArrayAccess, \IteratorAggregate
{
    private CustomPostType $postType;
    private array $labels;

    public function __construct(CustomPostType $postType)
    {
        $this->setPostType($postType);

        $this->set([
            'name'               => $this->getX($postType->getName()),
            'singular_name'      => $this->getX($postType->getSingularName()),
            'add_new'            => $this->getX('Add New'),
            'add_new_item'       => $this->getX('Add New %s', $postType->getSingularName()),
            'edit_item'          => $this->getX('Edit %s', $postType->getSingularName()),
            'new_item'           => $this->getX('New %s', $postType->getSingularName()),
            'view_item'          => $this->getX('View %s', $postType->getSingularName()),
            'search_items'       => $this->getX('Search %s', $postType->getName()),
            'not_found'          => $this->getX('No %s found', $postType->getName()),
            'not_found_in_trash' => $this->getX('No %s found in Trash', $postType->getName()),
            'parent_item_colon'  => $this->getX('Parent %s:', $postType->getSingularName()),
            'menu_name'          => $this->getX($postType->getName()),
        ]);
    }

    public function setPostType(CustomPostType $postType): self
    {
        $this->postType = $postType;

        return $this;
    }

    public function getPostType(): CustomPostType
    {
        return $this->postType;
    }

    public function set(array $labels): self
    {
        $this->labels = $labels;

        return $this;
    }

    public function toArray(): array
    {
        return $this->labels;
    }

    protected function getX($text, $context = []): string
    {
        $context = (array)$context;

        return _x(vsprintf($text, $context), $this->postType->getPostType());
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->labels);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->labels[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->labels[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->labels[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->labels[$offset]);
    }
}

<?php

namespace Palmtree\WordPress\CustomPostType;

use Palmtree\ArgParser\ArgParser;
use Palmtree\NameConverter\SnakeCaseToHumanNameConverter;

class CustomTaxonomy
{
    /** @var array */
    public static $defaultArgs = [
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_in_menu'      => true,
        'show_admin_column' => true,
        'query_var'         => false,
        'rewrite'           => [
            'with_front' => false,
        ],
    ];
    /** @var string */
    private $taxonomy;
    /** @var string */
    private $name;
    /** @var string */
    private $singularName;
    /** @var bool */
    private $public = true;
    /** @var array */
    private $args;
    /** @var array */
    private $labels = [];
    /** @var array */
    private $postTypes;

    public function __construct($taxonomy, array $postTypes = [], array $args = [])
    {
        $this->taxonomy = $taxonomy;

        $parser = new ArgParser($args);
        $parser->parseSetters($this);

        $this->setupProperties();

        $this->args      = wp_parse_args($args, $this->getDefaultArgs());
        $this->postTypes = $postTypes;

        add_action('init', function () {
            $postTypes = array_map(function (CustomPostType $customPostType) {
                return $customPostType->getPostType();
            }, $this->postTypes);
            register_taxonomy($this->taxonomy, $postTypes, $this->args);
        });
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setSingularName(string $singularName): self
    {
        $this->singularName = $singularName;

        return $this;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function setPublic(bool $public): self
    {
        $this->public = $public;

        return $this;
    }

    public function addPostType($postType): self
    {
        if ($postType instanceof CustomPostType) {
            $postType = $postType->getPostType();
        }

        $this->postTypes = $postType;

        return $this;
    }

    public function setPostTypes(array $postTypes): self
    {
        foreach ($postTypes as $postType) {
            $this->addPostType($postType);
        }

        return $this;
    }

    private function setupProperties(): void
    {
        if (empty($this->name)) {
            $normalizer = new SnakeCaseToHumanNameConverter();
            $this->name = $normalizer->normalize($this->taxonomy);
        }

        if (empty($this->singularName)) {
            $this->singularName = $this->name;
            $this->name         = $this->singularName . 's';
        }

        if (empty($this->taxonomy)) {
            $this->taxonomy = $this->taxonomy . 's';
        }

        if (empty($this->labels) && !empty($this->name) && !empty($this->singularName)) {
            $this->labels = $this->getLabels();
        }

        if (empty($this->slug)) {
            $this->slug = $this->taxonomy;
        }
    }

    private function getDefaultArgs(): array
    {
        $defaults = static::$defaultArgs;

        $defaults['labels'] = $this->getLabels();

        return $defaults;
    }

    private function getLabels(): array
    {
        $labels = [
            'name'                       => _x($this->name, 'taxonomy general name'),
            'singular_name'              => _x($this->singularName, 'taxonomy singular name'),
            'search_items'               => __('Search ' . $this->name),
            'popular_items'              => __('Popular ' . $this->name),
            'all_items'                  => __('All ' . $this->name),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __('Edit ' . $this->singularName),
            'update_item'                => __('Update ' . $this->singularName),
            'add_new_item'               => __('Add New ' . $this->singularName),
            'new_item_name'              => __('New ' . $this->singularName . ' Name'),
            'separate_items_with_commas' => __('Separate terms with commas'),
            'add_or_remove_items'        => __('Add or remove ' . strtolower($this->name)),
            'choose_from_most_used'      => __('Choose from the most used ' . strtolower($this->name)),
            'not_found'                  => __('No ' . strtolower($this->name) . ' found.'),
            'menu_name'                  => __($this->name),
        ];

        return $labels;
    }
}

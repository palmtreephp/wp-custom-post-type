<?php

namespace Palmtree\WordPress\CustomPostType;

class CustomTaxonomy
{
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

    public $taxonomy;
    public $name;
    protected $singularName;
    protected $public = true;

    protected $args = [];
    protected $labels = [];

    protected $postTypes = [];

    public function __construct($taxonomy, $name = '', $postTypes = [], $args = [])
    {
        $this->taxonomy = $taxonomy;
        $this->name     = $name;

        $this->setupProperties();

        $this->args      = wp_parse_args($args, $this->getDefaultArgs());
        $this->postTypes = (array)$postTypes;

        add_action('init', [$this, 'register']);
    }

    public function register()
    {
        register_taxonomy($this->taxonomy, $this->postTypes, $this->args);
    }

    protected function setupProperties()
    {
        if (empty($this->name)) {
            $this->name = ucwords(str_replace('_', ' ', $this->taxonomy));
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

    protected function getDefaultArgs()
    {
        $defaults = static::$defaultArgs;

        $defaults['labels'] = $this->getLabels();

        return $defaults;
    }

    protected function getLabels()
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

    /**
     * @return mixed
     */
    public function isPublic()
    {
        return $this->public;
    }

    /**
     * @param mixed $public
     */
    public function setPublic($public)
    {
        $this->public = $public;
    }

    public function addPostType($postType)
    {
        if ($postType instanceof CustomPostType) {
            $postType = $postType->postType;
        }

        $this->postTypes = $postType;
    }

    public function setPostTypes(array $postTypes)
    {
        foreach ($postTypes as $postType) {
            $this->addPostType($postType);
        }
    }
}

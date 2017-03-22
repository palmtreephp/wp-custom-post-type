<?php

namespace Palmtree\WordPress\CustomPostType;

class CustomTaxonomy
{
    public $taxonomy;
    public $name;
    protected $singular_name;
    protected $public = true;

    protected $args = [];
    protected $labels = [];

    protected $post_types = [];

    public function __construct($taxonomy, $name = '', $post_types = [], $args = [])
    {
        $this->taxonomy = $taxonomy;
        $this->name     = $name;

        $this->setupProperties();

        $this->args       = wp_parse_args($args, $this->getDefaultArgs());
        $this->post_types = (array)$post_types;

        add_action('init', [$this, 'register']);
    }

    public function register()
    {
        register_taxonomy($this->taxonomy, $this->post_types, $this->args);
    }

    protected function setupProperties()
    {
        if (empty($this->name)) {
            $this->name = ucwords(str_replace('_', ' ', $this->taxonomy));
        }

        if (empty($this->singular_name)) {
            $this->singular_name = $this->name;
            $this->name          = $this->singular_name . 's';
        }

        if (empty($this->taxonomy)) {
            $this->taxonomy = $this->taxonomy . 's';
        }

        if (empty($this->labels) && ! empty($this->name) && ! empty($this->singular_name)) {
            $this->labels = $this->getLabels();
        }

        if (empty($this->slug)) {
            $this->slug = $this->taxonomy;
        }
    }

    protected function getDefaultArgs()
    {
        $defaults = [
            'hierarchical'      => true,
            'labels'            => $this->getLabels(),
            'show_ui'           => true,
            'show_in_menu'      => true,
            'show_admin_column' => true,
            'query_var'         => false,
            'rewrite'           => false,
        ];

        return $defaults;
    }

    protected function getLabels()
    {
        $labels = [
            'name'                       => _x($this->name, 'taxonomy general name'),
            'singular_name'              => _x($this->singular_name, 'taxonomy singular name'),
            'search_items'               => __('Search ' . $this->name),
            'popular_items'              => __('Popular ' . $this->name),
            'all_items'                  => __('All ' . $this->name),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __('Edit ' . $this->singular_name),
            'update_item'                => __('Update ' . $this->singular_name),
            'add_new_item'               => __('Add New ' . $this->singular_name),
            'new_item_name'              => __('New ' . $this->singular_name . ' Name'),
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
}

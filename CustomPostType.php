<?php

namespace Palmtree\WordPress\CustomPostType;

use Palmtree\NameConverter\SnakeCaseToCamelCaseNameConverter;
use Palmtree\ArgParser\ArgParser;

class CustomPostType
{
    public static $defaultArgs = [
        'labels'       => [],
        'hierarchical' => false,

        'supports' => ['title', 'editor', 'thumbnail', 'page-attributes'],

        'show_in_nav_menus' => false,
        'show_ui'           => true,
        'show_in_menu'      => true,
        'menu_position'     => 20,

        'has_archive' => false,
        'can_export'  => true,

        'rewrite'              => ['slug' => '', 'with_front' => false, 'feeds' => false],
        'capability_type'      => 'post',
        'register_meta_box_cb' => '',

        'public'              => true,
        'publicly_queryable'  => true,
        'exclude_from_search' => false,
        'query_var'           => true,
    ];

    public $postType;
    protected $name;
    protected $singularName;
    protected $actions = [];
    protected $slug;
    public $front = '';

    /** @var CustomTaxonomy[] $taxonomies */
    protected $taxonomies = [];

    protected $public = true;

    public $args = [];
    protected $labels;

    public $rewriteRules = [];

    public function __construct($args = [])
    {
        $this->args = $this->parseArgs($args);
        $this->setMissingProperties();
        $this->args = wp_parse_args($this->args, $this->getDefaultArgs());

        $this->addActions();

        $this->addTaxonomies();

        $this->setPermalinkStructure();

        add_action('init', [$this, '_register']);
        add_filter('post_type_link', [$this, '_filterPostTypeLink'], 10, 2);

        add_filter('rewrite_rules_array', [$this, '_filterRewriteRules']);
    }

    protected function setPermalinkStructure()
    {
        if (!$this->isPublic()) {
            return;
        }

        if (!empty($this->taxonomies)) {
            $taxonomy = reset($this->taxonomies);

            // /front/term-slug/post-slug matches a post
            $this->addRewriteRule(
                sprintf('%s/(.+)/(.+)/?', $this->front),
                sprintf('index.php?%s=$matches[2]', $this->postType)
            );

            // /front/term-slug matches a taxonomy term
            $this->addRewriteRule(
                sprintf('%s/(.+)/?', $this->front),
                sprintf('index.php?%s=$matches[1]', $taxonomy->name)
            );
        }
    }

    protected function addActions()
    {
        foreach ($this->actions as $key => $value) {
            add_action($key, $value);
        }
    }

    protected function addTaxonomies()
    {
        $args = [
            'rewrite' => [
                'slug'       => $this->front,
                'with_front' => false,
            ],
        ];

        foreach ($this->taxonomies as $key => $taxonomy) {
            if ($taxonomy instanceof CustomTaxonomy) {
                $taxonomy->addPostType($this->postType);
                continue;
            }

            if ($taxonomy === true || is_int($key)) {
                if (is_int($key)) {
                    unset($this->taxonomies[$key]);
                    $key = $taxonomy;
                }
                //$this->taxonomies[$key] = new CustomTaxonomy($key, null, $this->postType, $args);
            } else if (is_array($taxonomy)) {
                $args = array_merge($args, $taxonomy);
            }

            if (!array_key_exists('public', $args) && !$this->isPublic()) {
                $args['public'] = false;
            }

            $this->taxonomies[$key] = new CustomTaxonomy($key, null, $this->postType, $args);
        }
    }

    public function _filterRewriteRules($rules)
    {
        if (empty($this->rewriteRules)) {
            return $rules;
        }

        return array_merge($rules, $this->rewriteRules);
    }

    public function _register()
    {
        register_post_type($this->postType, $this->args);
    }

    protected function getDefaultArgs()
    {
        $defaults = static::$defaultArgs;

        $defaults['labels']          = $this->getLabels();
        $defaults['rewrite']['slug'] = $this->slug;

        if (!array_key_exists('has_archive', $this->args) || $this->args['has_archive'] !== false) {
            $defaults['has_archive'] = $this->front;
        }

        if (!$this->isPublic()) {
            $defaults = array_merge($defaults, [
                'public'              => false,
                'publicly_queryable'  => false,
                'exclude_from_search' => true,
                'query_var'           => false,
                'rewrite'             => false,
            ]);
        }

        return $defaults;
    }

    public function _filterPostTypeLink($link, $post_id)
    {
        $post = get_post($post_id);

        if (!$post instanceof \WP_Post || $post->post_type !== $this->postType) {
            return $link;
        }

        $link = preg_replace_callback('~%([^%]+)%~', function ($matches) use ($post) {
            $match = $matches[1];

            if (isset($this->taxonomies[$match])) {
                $term = $this->getPrimaryTerm($match, (int)$post->ID);

                $slug = ($term) ? $term->slug : '';

                return $slug;
            }
        }, $link);

        return $link;
    }

    protected function getPrimaryTerm($taxonomy, $post_id)
    {
        $term = false;
        if (class_exists('WPSEO_Primary_Term')) {
            $primary_term = new \WPSEO_Primary_Term($taxonomy, $post_id);
            $term_id      = $primary_term->get_primary_term();

            if ($term_id) {
                $term = get_term($term_id, $taxonomy);
            }
        }

        if (!$term instanceof \WP_Term) {
            $terms = wp_get_object_terms($post_id, $taxonomy);

            if ($terms) {
                $term = reset($terms);
            }
        }

        return $term;
    }

    protected function setMissingProperties()
    {
        if (empty($this->name)) {
            $this->name = ucwords(str_replace('_', ' ', $this->postType));
        }

        if (empty($this->singularName)) {
            $this->singularName = $this->name;
            $this->name         = $this->singularName . 's';
        }

        if (empty($this->slug)) {
            $this->slug = $this->postType . 's';
        }

        foreach ($this->taxonomies as $key => $taxonomy) {
            if (is_string($key)) {
                $taxonomy = $key;
            }
            $this->slug = "{$this->front}/%{$taxonomy}%";
            break;
        }

        if (empty($this->labels) && !empty($this->name) && !empty($this->singularName)) {
            $this->labels = $this->getLabels();
        }

        if (empty($this->slug)) {
            $this->slug = $this->postType;
        }
    }

    protected function getLabels()
    {
        if ($this->labels === null) {
            $this->labels = [
                'name'               => _x($this->name, $this->postType),
                'singular_name'      => _x($this->singularName, $this->postType),
                'add_new'            => _x('Add New', $this->postType),
                'add_new_item'       => _x('Add New ' . $this->singularName, $this->postType),
                'edit_item'          => _x('Edit ' . $this->singularName, $this->postType),
                'new_item'           => _x('New ' . $this->singularName, $this->postType),
                'view_item'          => _x('View ' . $this->singularName, $this->postType),
                'search_items'       => _x('Search ' . $this->name, $this->postType),
                'not_found'          => _x('No ' . $this->name . ' found', $this->postType),
                'not_found_in_trash' => _x('No ' . $this->name . ' found in Trash', $this->postType),
                'parent_item_colon'  => _x('Parent ' . $this->singularName . ':', $this->postType),
                'menu_name'          => _x($this->name, $this->postType),
            ];
        }

        return $this->labels;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function setSingularName($name)
    {
        $this->singularName = $name;
    }

    /**
     * @return boolean
     */
    public function isPublic()
    {
        return $this->public;
    }

    /**
     * @param boolean $public
     *
     * @return $this
     */
    public function setPublic($public)
    {
        $this->public = $public;

        return $this;
    }

    protected function parseArgs($args)
    {
        $parser = new ArgParser($args, 'post_type', new SnakeCaseToCamelCaseNameConverter());

        $parser->parseSetters($this);

        return $parser->getArgs();
    }

    /**
     * @param array $labels
     *
     * @return $this
     */
    public function setLabels($labels)
    {
        $this->labels = $labels;

        return $this;
    }

    /**
     * @param mixed $postType
     *
     * @return CustomPostType
     */
    public function setPostType($postType)
    {
        $this->postType = $postType;

        return $this;
    }

    /**
     * @param mixed $slug
     *
     * @return $this
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @param mixed $front
     */
    public function setFront($front)
    {
        $this->front = $front;
    }

    /**
     * @return array
     */
    public function getRewriteRules()
    {
        return $this->rewriteRules;
    }

    /**
     * @param array $rewriteRules
     */
    public function setRewriteRules($rewriteRules)
    {
        $this->rewriteRules = $rewriteRules;
    }

    public function addRewriteRule($pattern, $match)
    {
        $this->rewriteRules[$pattern] = $match;
    }

    /**
     * @param mixed $taxonomies
     *
     * @return CustomPostType
     */
    public function setTaxonomies($taxonomies)
    {
        $this->taxonomies = $taxonomies;

        return $this;
    }

    /**
     * @param mixed $actions
     *
     * @return CustomPostType
     */
    public function setActions($actions)
    {
        $this->actions = $actions;

        return $this;
    }

}

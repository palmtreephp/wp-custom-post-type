<?php

namespace Palmtree\WordPress\CustomPostType;

class CustomPostType
{
    /** @var string Post type e.g project */
    private $postType;
    /** @var string Name e.g My Projects */
    private $name;
    /** @var string Singular name e.g Project */
    private $singularName;
    /** @var string Slug e.g my-projects */
    private $slug;
    /** @var string */
    private $front = '';
    /** @var CustomTaxonomy[] $taxonomies */
    private $taxonomies = [];
    /** @var bool Whether the post type is publicly accessible via URL, search etc */
    private $public = true;
    /** @var array */
    private $args = [];
    /** @var array */
    private $labels;
    /** @var array */
    private $rewriteRules = [];

    public function __construct($args = [])
    {
        $hydrator = new CustomPostTypeHydrator($this);
        $hydrator->hydrate($args);

        $this->setTaxonomies(TaxonomyFactory::createTaxonomies($this));
        $this->setPermalinkStructure();

        add_action('init', function () {
            register_post_type($this->getPostType(), $this->getArgs());
        });

        add_filter('post_type_link', [$this, 'filterPostTypeLink'], 10, 2);
        add_filter('rewrite_rules_array', [$this, 'filterRewriteRules']);
    }

    /**
     * @return string
     */
    public function getPostType()
    {
        return $this->postType;
    }

    /**
     * @param string $postType
     *
     * @return self
     */
    public function setPostType($postType)
    {
        $this->postType = $postType;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getSingularName()
    {
        return $this->singularName;
    }

    /**
     * @param string $name
     *
     * @return self
     */
    public function setSingularName($name)
    {
        $this->singularName = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     *
     * @return self
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return string
     */
    public function getFront()
    {
        return $this->front;
    }

    /**
     * @param string $front
     *
     * @return self
     */
    public function setFront($front)
    {
        $this->front = $front;

        return $this;
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
     * @return self
     */
    public function setPublic($public)
    {
        $this->public = $public;

        return $this;
    }

    /**
     * @return array
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * @param array $args
     */
    public function setArgs($args)
    {
        $this->args = $args;
    }

    public function getLabels()
    {
        if ($this->labels === null) {
            $this->labels = new CustomPostTypeLabels($this);
        }

        return $this->labels;
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
     * @return CustomTaxonomy[]
     */
    public function getTaxonomies()
    {
        return $this->taxonomies;
    }

    /**
     * @param CustomTaxonomy[] $taxonomies
     *
     * @return CustomPostType
     */
    public function setTaxonomies($taxonomies)
    {
        $this->taxonomies = $taxonomies;

        return $this;
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
    public function setRewriteRules(array $rewriteRules)
    {
        $this->rewriteRules = $rewriteRules;
    }

    public function addRewriteRule($pattern, $match)
    {
        $this->rewriteRules[$pattern] = $match;
    }

    public function filterRewriteRules($rules)
    {
        $newRules = $this->getRewriteRules();

        if (empty($newRules)) {
            return $rules;
        }

        return array_merge($rules, $newRules);
    }

    public function filterPostTypeLink($link, $post_id)
    {
        $post = get_post($post_id);

        if (!$post instanceof \WP_Post || $post->post_type !== $this->getPostType()) {
            return $link;
        }

        $link = preg_replace_callback('~%([^%]+)%~', function ($matches) use ($post) {
            $match = $matches[1];

            if (isset($this->taxonomies[$match])) {
                $term = TaxonomyFactory::getPrimaryTerm($match, (int)$post->ID);

                return $term ? $term->slug : '';
            }

            return $matches[0];
        }, $link);

        return $link;
    }

    private function setPermalinkStructure()
    {
        if (!$this->isPublic()) {
            return;
        }

        if (!empty($this->taxonomies)) {
            $taxonomy = reset($this->taxonomies);

            // /front/term-slug/post-slug matches a post
            // e.g: /shop/t-shirts/mens-red-polo-shirt
            $this->addRewriteRule(
                sprintf('%s/(.+)/(.+)/?', $this->getFront()),
                sprintf('index.php?%s=$matches[2]', $this->getPostType())
            );

            // /front/term-slug matches a taxonomy term
            // e.g: /shop/t-shirts
            $this->addRewriteRule(
                sprintf('%s/(.+)/?', $this->getFront()),
                sprintf('index.php?%s=$matches[1]', $taxonomy->name)
            );
        }
    }
}

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
    /** @var string Front of URL structure for this post type e.g my-projects */
    private $front = '';

    /** @var string Generated slug for this post type.
     *  If the post type has a taxonomy slug is generated as: <front>/%term%
     *  If there are no taxonomies, slug is generated as <front>
     */
    private $slug;

    /** @var CustomTaxonomy[] */
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

    public function getPostType(): string
    {
        return $this->postType;
    }

    public function setPostType(string $postType): self
    {
        $this->postType = $postType;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSingularName(): ?string
    {
        return $this->singularName;
    }

    public function setSingularName(string $name): self
    {
        $this->singularName = $name;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getFront(): string
    {
        return $this->front;
    }

    public function setFront(string $front): self
    {
        $this->front = $front;

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

    public function getArgs(): array
    {
        return $this->args;
    }

    public function setArgs(array $args)
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

    public function setLabels(array $labels): self
    {
        $this->labels = $labels;

        return $this;
    }

    /**
     * @return array<CustomTaxonomy>
     */
    public function getTaxonomies(): array
    {
        return $this->taxonomies;
    }

    /**
     * @param array<CustomTaxonomy> $taxonomies
     */
    public function setTaxonomies(array $taxonomies): self
    {
        $this->taxonomies = $taxonomies;

        return $this;
    }

    public function getRewriteRules(): array
    {
        return $this->rewriteRules;
    }

    public function setRewriteRules(array $rewriteRules): self
    {
        $this->rewriteRules = $rewriteRules;

        return $this;
    }

    public function addRewriteRule($pattern, $match): void
    {
        $this->rewriteRules[$pattern] = $match;
    }

    public function filterRewriteRules(array $rules): array
    {
        $newRules = $this->getRewriteRules();

        if (empty($newRules)) {
            return $rules;
        }

        return array_merge($rules, $newRules);
    }

    public function filterPostTypeLink($link, $post_id): string
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

    private function setPermalinkStructure(): void
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
                sprintf('index.php?%s=$matches[1]', $taxonomy->getName())
            );
        }
    }
}

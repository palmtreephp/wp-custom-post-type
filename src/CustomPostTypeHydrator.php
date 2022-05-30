<?php

declare(strict_types=1);

namespace Palmtree\WordPress\CustomPostType;

use Palmtree\ArgParser\ArgParser;
use Palmtree\NameConverter\SnakeCaseToHumanNameConverter;

class CustomPostTypeHydrator
{
    /** @var array */
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

    /** @var CustomPostType */
    private $postType;

    public function __construct(CustomPostType $postType)
    {
        $this->postType = $postType;
    }

    public function hydrate($args = []): void
    {
        $this->postType->setArgs($this->parseArgs($args));

        if (empty($this->postType->getName())) {
            $nameConverter = new SnakeCaseToHumanNameConverter();
            $this->postType->setName($nameConverter->normalize($this->postType->getPostType()));
        }

        if (empty($this->postType->getSingularName())) {
            $this->postType->setSingularName($this->postType->getName());
            $this->postType->setName($this->postType->getSingularName() . 's');
        }

        foreach ($this->postType->getTaxonomies() as $key => $taxonomy) {
            if (\is_string($key)) {
                $taxonomy = $key;
            }

            $this->postType->setSlug(sprintf('%s/%%%s%%', $this->postType->getFront(), $taxonomy));

            break;
        }

        if (empty($this->postType->getSlug() && !empty($this->postType->getFront()))) {
            $this->postType->setSlug($this->postType->getFront());
        }

        if (empty($this->postType->getSlug())) {
            $this->postType->setSlug($this->postType->getPostType() . 's');
        }

        $this->postType->setArgs(wp_parse_args($this->postType->getArgs(), $this->getDefaultArgs()));
    }

    private function getDefaultArgs(): array
    {
        $defaults = self::$defaultArgs;

        $defaults['labels'] = $this->postType->getLabels()->toArray();
        $defaults['rewrite']['slug'] = $this->postType->getSlug();

        $args = $this->postType->getArgs();

        if (!\array_key_exists('has_archive', $args) || $args['has_archive'] !== false) {
            $defaults['has_archive'] = $this->postType->getFront();
        }

        if (!$this->postType->isPublic()) {
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

    /**
     * @param array|string $args
     */
    public function parseArgs($args): array
    {
        $parser = new ArgParser($args, 'post_type');

        $parser->parseSetters($this->postType);

        return $parser->getArgs();
    }
}

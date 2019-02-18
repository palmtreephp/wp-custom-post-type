<?php

namespace Palmtree\WordPress\CustomPostType;

class TaxonomyFactory
{
    public static function createTaxonomies(CustomPostType $postType)
    {
        $args = [
            'rewrite' => [
                'slug'       => $postType->getFront(),
                'with_front' => false,
            ],
        ];

        $taxonomies = $postType->getTaxonomies();

        foreach ($taxonomies as $key => $taxonomy) {
            if ($taxonomy instanceof CustomTaxonomy) {
                $taxonomy->addPostType($postType->getPostType());
                continue;
            }

            if ($taxonomy === true || is_int($key)) {
                if (is_int($key)) {
                    unset($taxonomies[$key]);
                    $key = $taxonomy;
                }
                //$postType->taxonomies[$key] = new CustomTaxonomy($key, null, $postType->postType, $args);
            } elseif (is_array($taxonomy)) {
                $args = array_merge($args, $taxonomy);
            }

            if (!array_key_exists('public', $args) && !$postType->isPublic()) {
                $args['public'] = false;
            }

            $taxonomies[$key] = new CustomTaxonomy($key, null, $postType->getPostType(), $args);
        }

        return $taxonomies;
    }

    public static function getPrimaryTerm($taxonomy, $post_id)
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
}

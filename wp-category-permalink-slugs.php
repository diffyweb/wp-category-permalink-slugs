<?php
/**
 * Plugin Name: Category Permalink Slugs
 * Description: Allows duplicate post slugs when the permalink structure includes %category%, since the category prefix ensures URL uniqueness.
 * Version:     1.1.0
 * Author:      Diffyweb
 * License:     GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check whether the current permalink structure includes %category%.
 */
function cps_has_category_permalink() {
	static $result = null;
	if ( null === $result ) {
		$result = false !== strpos( get_option( 'permalink_structure' ), '%category%' );
	}
	return $result;
}

/**
 * Allow duplicate post slugs when permalink structure includes %category%.
 *
 * WordPress enforces globally unique post_name values even when the URL
 * structure makes collisions impossible (e.g., /category-a/my-post/ vs
 * /category-b/my-post/). This filter short-circuits the uniqueness check
 * for posts when the permalink structure includes %category%.
 *
 * @see https://core.trac.wordpress.org/ticket/18962
 */
add_filter( 'pre_wp_unique_post_slug', function ( $override_slug, $slug, $post_id, $post_status, $post_type, $post_parent ) {
	if ( 'post' !== $post_type || ! cps_has_category_permalink() ) {
		return $override_slug;
	}
	return $slug;
}, 10, 6 );

/**
 * Fix post resolution for duplicate slugs.
 *
 * When WP_Query resolves a single post by name, it ignores category_name
 * in the SQL — it only queries by post_name. When two posts share a slug,
 * WP picks the first DB result regardless of the requested category.
 *
 * This filter intercepts the results and returns only the post that belongs
 * to the requested category.
 */
add_filter( 'posts_results', function ( $posts, $query ) {
	if ( ! $query->is_main_query() || is_admin() ) {
		return $posts;
	}

	if ( ! $query->is_single() || ! cps_has_category_permalink() ) {
		return $posts;
	}

	$category_name = $query->get( 'category_name' );
	if ( ! $category_name || count( $posts ) < 2 ) {
		return $posts;
	}

	$cat = get_category_by_slug( $category_name );
	if ( ! $cat ) {
		return $posts;
	}

	// Filter to only the post in the requested category.
	$filtered = array_filter( $posts, function ( $post ) use ( $cat ) {
		return has_category( $cat->term_id, $post );
	} );

	return ! empty( $filtered ) ? array_values( $filtered ) : $posts;
}, 10, 2 );

/**
 * Cancel canonical redirect when the correct post is already resolved.
 *
 * After posts_results fixes the resolution, redirect_canonical may still
 * fire if WP's internal state is stale. This ensures we don't redirect
 * away from a valid URL.
 */
add_filter( 'redirect_canonical', function ( $redirect_url, $requested_url ) {
	if ( ! is_singular( 'post' ) || ! cps_has_category_permalink() ) {
		return $redirect_url;
	}

	$current_permalink = get_permalink();
	if ( $current_permalink && trailingslashit( $requested_url ) === trailingslashit( $current_permalink ) ) {
		return false;
	}

	return $redirect_url;
}, 10, 2 );

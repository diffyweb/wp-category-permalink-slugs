# Category Permalink Slugs

WordPress plugin that allows duplicate post slugs when the permalink structure includes `%category%`.

## Problem

WordPress enforces globally unique `post_name` values, even when the permalink structure (e.g., `/%category%/%postname%/`) makes URL collisions impossible. Two posts titled "Introduction" in different categories can't share the slug `introduction` — one gets `introduction-2` despite their URLs being distinct (`/category-a/introduction/` vs `/category-b/introduction/`).

This is [WordPress core ticket #18962](https://core.trac.wordpress.org/ticket/18962), open since 2011.

## Solution

This plugin addresses three layers of the problem:

1. **`pre_wp_unique_post_slug`** — Skips the uniqueness suffix when `%category%` is in the permalink structure, allowing posts in different categories to share a slug.

2. **`posts_results`** — When `WP_Query` returns multiple posts with the same slug (because WordPress ignores `category_name` in the SQL for single post lookups), this filter narrows the results to the post belonging to the requested category.

3. **`redirect_canonical`** — Prevents WordPress from 301-redirecting a valid category-prefixed URL to a different post's URL.

## Requirements

- WordPress 5.1+ (for `pre_wp_unique_post_slug` filter)
- Permalink structure must include `%category%` (e.g., `/%category%/%postname%/`)

The plugin is inert when the permalink structure doesn't include `%category%`.

## Installation

1. Copy the `wp-category-permalink-slugs` directory to `wp-content/plugins/`
2. Activate via WP admin or `wp plugin activate wp-category-permalink-slugs`

## After Activation

Existing posts with suffixed slugs (e.g., `introduction-2`) need to be manually corrected:

```bash
wp eval '
global $wpdb;
$wpdb->update($wpdb->posts, ["post_name" => "introduction"], ["ID" => <POST_ID>]);
clean_post_cache(<POST_ID>);
'
wp rewrite flush
```

The plugin only prevents *future* suffix additions. It does not retroactively fix existing slugs.

## Limitations

- Only applies to the `post` post type. Pages and custom post types are unaffected.
- If any code uses `get_page_by_path()` without category context, it may return the wrong post when slugs collide. This is uncommon on content-focused sites.
- The `posts_results` filter only activates when the main query returns 2+ posts with the same slug — no performance impact on normal queries.

## License

GPL-2.0-or-later

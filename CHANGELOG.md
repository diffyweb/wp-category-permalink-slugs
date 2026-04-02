# Changelog

## 1.1.0 — 2026-04-01

- Add `posts_results` filter to resolve the correct post when duplicate slugs exist
- Add `redirect_canonical` filter to prevent erroneous 301 redirects
- Fix: WordPress ignores `category_name` in single-post SQL queries, causing wrong post resolution

## 1.0.0 — 2026-04-01

- Initial release
- `pre_wp_unique_post_slug` filter to allow duplicate slugs with `%category%` permalinks

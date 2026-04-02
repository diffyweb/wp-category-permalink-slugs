# wp-category-permalink-slugs

WordPress plugin that allows duplicate post slugs when the permalink structure includes `%category%`.

## Tech Stack

- **Language**: PHP
- **Platform**: WordPress plugin (no build step)
- **Min WP version**: 5.1 (for `pre_wp_unique_post_slug` filter)

## Project Structure

```
wp-category-permalink-slugs.php   # Single-file plugin, all logic here
README.md                         # Usage docs
CHANGELOG.md                      # Version history
```

## How It Works

Three filters, each addressing a different layer of WordPress's slug handling:

1. **`pre_wp_unique_post_slug`** — Prevents WordPress from appending `-2` suffixes to duplicate slugs when `%category%` is in the permalink structure.

2. **`posts_results`** — Filters `WP_Query` results on the main query for single posts. When multiple posts share a slug, returns only the post matching the requested category. This is necessary because WordPress's single-post SQL ignores the `category_name` query var.

3. **`redirect_canonical`** — Cancels the 301 redirect when the resolved post's permalink matches the requested URL. Without this, WordPress redirects to whichever duplicate-slug post it finds first.

All three filters are guarded by `cps_has_category_permalink()`, which checks that `%category%` is in the permalink structure. The plugin is completely inert otherwise.

## Key Conventions

- No settings page, no options, no admin UI — the plugin is purely functional
- Only affects the `post` post type (not pages, attachments, or CPTs)
- Prefix: `cps_` for all functions
- All hooks use anonymous closures (no class wrapper needed for this scope)

## Testing

After activating, verify with posts that share a slug across categories:

```bash
# Should all return 200, not 301
curl -sk -o /dev/null -w '%{http_code}' 'https://example.com/category-a/shared-slug/'
curl -sk -o /dev/null -w '%{http_code}' 'https://example.com/category-b/shared-slug/'

# Verify correct post resolution
curl -sk 'https://example.com/category-a/shared-slug/' | grep 'postid-'
curl -sk 'https://example.com/category-b/shared-slug/' | grep 'postid-'
```

## Git Conventions

- Conventional commits
- Single-file plugin — keep it that way unless complexity demands splitting

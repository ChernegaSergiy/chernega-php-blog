# chernega.eu.org Web Stack

[![Better Stack Badge](https://uptime.betterstack.com/status-badges/v1/monitor/255i1.svg)](https://uptime.betterstack.com/?utm_source=status_badge)

This directory contains the full PHP codebase powering **chernega.eu.org**, built around Twig templates. The code base powers a markdown-backed blog with SQLite persistence, auxiliary tools, and a minimalist terminal-inspired UI.

## Key Features

- **Twig templating** with a shared base layout and structured page templates.
- **Admin panel** for creating, updating, and deleting posts through a secure dashboard.
- **Role-aware access control** with granular permissions (viewer, editor, admin) and detailed audit logging.
- **Media library** featuring optimized image uploads, automated housekeeping, and quick clipboard-ready URLs.
- **SQLite-backed blog engine** with configurable posts-per-page and category filtering.
- **Markdown authoring pipeline** via Parsedown for safe HTML rendering.
- **Utility pages** including a Mermaid.js diagram visualiser and CSSM Unlimited License generator.
- **Structured helper layer** for preview rendering, date localisation (UTC → Europe/Kiev), and template data mapping.

## Requirements

- PHP **7.3.10** (CLI or FPM) with the following extensions:
  - `sqlite3`
  - `mbstring`
  - `json`
  - `gd`
- [Composer](https://getcomposer.org/) (optional but recommended) to install Twig.
- Write permissions for `data/` and (optionally) `uploads/`.

> **Important:** Twig is not bundled. Install it before serving the site (see [Installing Twig](#installing-twig)).

## Directory Layout

```
html/
├── 404.php                # Renders the custom 404 page via Twig
├── about.php              # Static "About" page controller
├── bootstrap.php          # Twig environment bootstrap
├── contact.php            # Static "Contact" page controller
├── data/                  # SQLite database storage (blog.db)
├── Database.php           # SQLite data access layer
├── helpers.php            # Markdown + view-model helpers
├── index.php              # Homepage controller (recent posts)
├── mermaid-diagrams.php   # Mermaid.js playground controller
├── Parsedown.php          # Bundled Markdown parser
├── post.php               # Post view by numeric ID
├── post_slug.php          # Post view by slug
├── posts.php              # Paginated post catalogue + search/filter
├── templates/             # Twig templates (base, posts, static, tools)
└── ul-generator.php       # CSSM Unlimited License generator controller
```

Static assets live under `assets/` (CSS, JS, fonts). Iconography resides in `assets/icons/`, while PWA metadata is grouped under `assets/manifest/`.

## Installing Twig

Twig must be available at runtime. Choose one of the following approaches:

1. **Composer (recommended)**
   ```bash
   composer require twig/twig:^3.0
   ```
   This creates `vendor/` with the autoloader that `bootstrap.php` consumes automatically.

2. **Manual install (without Composer)**
   - Download the Twig release archive from <https://github.com/twigphp/Twig/releases>.
   - Extract it inside `html/vendor/twig/`.
   - Include Twig's autoloader before `bootstrap.php` (e.g. `require __DIR__ . '/vendor/autoload.php';`).

If Twig classes are still missing at runtime, the bootstrapper throws a descriptive `RuntimeException`.

## Configuration

Runtime options are stored in `config/app.php` and can be overridden via environment variables:

- `APP_ENV` (defaults to `development`) controls features such as Twig caching, which switches on automatically for `production`.
- `APP_URL` sets the canonical base URL used for generated links and metadata.
- `ADMIN_USERNAME` and `ADMIN_PASSWORD` seed the initial administrator account (fallback credentials are `admin` / `admin123`).
- `ADMIN_PASSWORD_HASH` lets you provide a pre-generated password hash if you prefer not to expose a plain password.

Twig cache files live in `cache/twig`; the directory is created automatically when caching is enabled.

## Database Initialisation

On first run the application will:

- Create `data/blog.db` if it does not exist.
- Provision the `admins`, `posts`, and `settings` tables.
- Seed default settings (`~/chernega.blog`, `posts_per_page=5`).

You can pre-populate posts via your own script or by calling `$db->addPost(...)` within `Database.php`.

## Running the Application

Use PHP's built-in server from the `html/` directory:
```bash
php -S 127.0.0.1:8000 -t .
```

Then open <http://127.0.0.1:8000/> in your browser. Frequently used entry points:

- `/` – homepage with the most recent posts.
- `/posts.php` – full archive with pagination, category filter, and search.
- `/post.php?id=123` – view post by numeric ID.
- `/{slug}` – view post by slug (via `post_slug.php`).
- `/about.php`, `/contact.php` – static pages.
- `/mermaid-diagrams.php` – Mermaid.js live editor.
- `/ul-generator.php` – CSSM UL v2.0 license generator.
- `/admin/` – administrative dashboard (requires authentication).

To serve behind Apache/Nginx, configure the document root to this `html/` directory and route unknown slugs to `post_slug.php`.

## Admin Panel

- Sign in at `/admin/login.php` using the credentials from `config/app.php` or the corresponding environment variables (defaults: `admin` / `admin123`).
- After authentication you can create, edit, and delete posts; every action is CSRF-protected and validated before data is persisted.
- Supply an `ADMIN_PASSWORD_HASH` environment variable to distribute a pre-hashed credential. Generate hashes with `password_hash('your-password', PASSWORD_DEFAULT)` in PHP.
- Use `/admin/logout.php` to terminate the session; PHP's native session handler stores the session on the server.

## Roles & Permissions

| Role   | Capabilities |
| :----- | :----------- |
| `viewer` | Reserved for future read-only dashboards. |
| `editor` | Manage posts and media assets, trigger upload optimization workflows. |
| `admin`  | Full access: manage content, revoke media, adjust fellow admin roles (via database), and run housekeeping routines. |

Roles are stored inside the `admins` table (`role` column). The default account seeded from `config/app.php` receives the `admin` role.

## Media Library

- Uploads live at `/uploads/media/<YYYY>/<MM>/<filename>`; the helper automatically creates the folder structure.
- Images are resized to a safe maximum (1600×1600 by default), EXIF orientation is respected, and output quality is tuned per format.
- The admin UI exposes copy-to-clipboard links, previews, and deletion controls. Editors can adjust items-per-page; admins can trigger housekeeping.
- Housekeeping removes database records for missing files, deletes orphaned files on disk, and tidies up empty folders.

## Audit Logs

- Every privileged action (sign-in/out, post CRUD, media operations, housekeeping) is written to the `audit_logs` table.
- The admin dashboard (/admin/) surfaces the most recent events, showing timestamp, actor, entity, metadata, and IP address.
- Extend logging by calling `adminAudit()` inside custom workflows – metadata accepts arbitrary arrays and is stored as JSON.

## Twig Templates

`templates/` is organised by feature:

- `base.html.twig` – shared layout, navigation, and footer.
- `home.html.twig` – homepage (recent posts).
- `posts/index.html.twig` – list view + pagination.
- `posts/show.html.twig` – single post view (metadata aware).
- `static/` – simple static pages (`about`, `contact`, `404`).
- `tools/` – interactive utilities (`mermaid`, `license-generator`).

Modify the navigation or footer once in `base.html.twig`. Controller PHP files convert database rows into view models via helper functions, ensuring templates stay presentation-focused.

## Helper Utilities

`helpers.php` provides reusable functions:

- `markdownParser()` – returns a cached, safe-mode `Parsedown` instance.
- `generatePreviewHtml()` – builds 200-char excerpts for lists.
- `formatDateToKiev()` – converts UTC timestamps to `Europe/Kiev` dates.
- `mapPostForList()` and `mapPostForDetails()` – prepare associative arrays consumed by Twig.

`bootstrap.php` centralises Twig setup, sets global site metadata (`site.title`, navigation items, footer), and handles lazy Composer autoloading.

## Assets & Frontend

All styling relies on `assets/css/main.css` (Solarized terminal aesthetic). JavaScript is used sparingly:
- Mermaid visualiser loads the CDN bundle.
- License generator embeds a small inline script for form handling.
- No build pipeline is required; everything is vanilla CSS/JS.

## Development Workflow

1. Ensure PHP 7.3+ and SQLite extensions are available.
2. Install Twig (see [Installing Twig](#installing-twig)).
3. Start the local server (`php -S`).
4. Edit Twig templates or controllers; refresh the browser to see changes.
5. Optional: enable Twig caching by adjusting the `$twig` environment options in `bootstrap.php` once a writable cache directory is configured.

## Troubleshooting

- **`RuntimeException: Twig dependency not found`** — Twig not installed or autoloader missing. Install via Composer or require Twig's autoloader manually.
- **Blank page / HTTP 500** — PHP error. Run `php -l <file>` or enable `display_errors` in `php.ini` during development.
- **Posts missing on homepage** — Empty `posts` table. Add records via admin tooling or direct SQL.
- **Incorrect dates** — Server timezone not UTC. Ensure timestamps stored as UTC; helper converts to `Europe/Kiev`.
- **404 for `/some-slug`** — Slug absent or conflicts with protected filenames. Confirm slug exists and is not blacklisted in `post_slug.php`.

## Future Ideas

- Introduce role-based permissions and audit logging for administrators.
- Add media uploads with automated image optimisation and storage housekeeping.
- Automate testing (PHPUnit) and linting within a CI pipeline.
- Provide API endpoints (REST/JSON) for headless or mobile clients.

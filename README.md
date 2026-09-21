# chernega.eu.org Web Stack

[![Better Stack Badge](https://uptime.betterstack.com/status-badges/v2/monitor/255i1.svg)](https://uptime.betterstack.com/?utm_source=status_badge)

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

- PHP **8.1+** (CLI or FPM) with the following extensions:
  - `sqlite3` (or `pdo_sqlite`)
  - `mbstring`
  - `json`
  - `gd`
- [Composer](https://getcomposer.org/) to install dependencies.
- Write permissions for `data/` and `public/media/`.

## Directory Layout

```text
/
+-- assets/      # Frontend source assets (CSS, JS)
+-- bin/         # Symfony console
+-- config/      # Framework configuration files
+-- data/        # SQLite database file (`blog.db`)
+-- public/      # Web root (`index.php`, compiled assets, media)
|   +-- assets/  # Static CSS, icons, fonts
|   \-- media/   # Uploaded media directory
+-- src/         # PHP codebase (Controllers, Entities, Repositories)
\-- templates/   # Twig templates
```

Static assets live under `assets/` (CSS, JS, fonts). Iconography resides in `assets/icons/`, while PWA metadata is grouped under `assets/manifest/`.

## Installing Dependencies

This project is built on Symfony and uses Composer for all dependencies (including Twig).

1. **Composer (recommended)**
   ```bash
   composer install
   ```
   This creates `vendor/` and sets up the autoloader. Twig is bundled natively via Symfony Flex.

If classes are still missing at runtime, ensure you ran `composer install`.

## Configuration

Runtime options are managed via Symfony parameters and environment variables:

- `APP_ENV` (defaults to `dev`) controls features such as Twig caching, which switches on automatically for `prod`.
- Secrets and tokens can be set in `.env.local` or the Symfony secrets vault.

## Database Initialisation

The application uses Doctrine ORM. To provision the tables:
```bash
php bin/console doctrine:schema:update --force
```

After creating the tables, generate your initial administrator account:
```bash
php bin/console app:create-admin admin mysecretpassword
```

## Running the Application

Use Symfony's local web server from the project directory:
```bash
symfony serve -d
```

Or run PHP's built-in server from the `public/` directory:
```bash
php -S 127.0.0.1:8000 -t public/
```

Then open <http://127.0.0.1:8000/> in your browser. Frequently used entry points:

- `/` – homepage with the most recent posts.
- `/posts` – full archive with pagination, category filter, and search.
- `/post/{slug}` – view post by slug.
- `/about`, `/contact` – static pages.
- `/admin/` – administrative dashboard (requires authentication).

To serve behind Apache/Nginx, configure the document root to the `public/` directory and use the standard Symfony front controller routing.

## Admin Panel

- Sign in at `/admin/login` using the credentials you created during setup.
- After authentication you can create, edit, and delete posts; every action is CSRF-protected and validated before data is persisted.
- Use `/logout` to terminate the session; Symfony Security handles the session natively.

## Roles & Permissions

| Role   | Capabilities |
| :----- | :----------- |
| `viewer` | Reserved for future read-only dashboards. |
| `editor` | Manage posts and media assets, trigger upload optimization workflows. |
| `admin`  | Full access: manage content, revoke media, adjust fellow admin roles (via database), and run housekeeping routines. |

Roles are stored inside the `admins` table (`role` column).

## Media Library

- Uploads live at `/media/`; the folder structure is mapped securely.
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

## Framework Integration

The application leverages Symfony's ecosystem:
- **Routing**: Handled by Symfony Attributes.
- **ORM**: Doctrine entities manage the database schema.
- **Security**: Symfony Security bundle handles authentication and roles.

## Assets & Frontend

All styling relies on `assets/css/main.css` (Solarized terminal aesthetic). JavaScript is used sparingly:

- Mermaid visualiser loads the CDN bundle.
- License generator embeds a small inline script for form handling.
- No build pipeline is required; everything is vanilla CSS/JS.

## Development Workflow

1. Ensure PHP 8.1+ and SQLite extensions are available.
2. Install dependencies via `composer install`.
3. Start the local server (`symfony serve -d`).
4. Edit Twig templates or controllers; refresh the browser to see changes.

## Troubleshooting

- **Blank page / HTTP 500** — PHP error. Check `var/log/dev.log` or the Symfony Web Profiler.
- **Posts missing on homepage** — Empty `posts` table. Add records via admin tooling or direct SQL.

## Contributing

Contributions are welcome and appreciated! Here's how you can contribute:

1. Fork the project
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

Please make sure to update tests as appropriate and adhere to the existing coding style.

## License

This project is licensed under the CSSM Unlimited License v2.0 (CSSM-ULv2). See the [LICENSE](LICENSE) file for details.

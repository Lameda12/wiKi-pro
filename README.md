# Wiki

A Wikipedia-style wiki built to be more readable than Wikipedia. Laravel 11, Markdown with `[[wikilinks]]`, plain CSS and vanilla JS.

## Run it

```bash
composer install          # also creates .env, an app key and the SQLite file
php artisan migrate --seed
php artisan serve
```

Compiled assets are committed in `public/build`, so no Node is needed to run the site. To change CSS or JS:

```bash
npm install
npm run dev     # or: npm run build, and commit public/build
```

## Test

```bash
composer test
```

The suite runs on in-memory SQLite. The schema and tests also pass on PostgreSQL 16 (`DB_CONNECTION=pgsql`).

## How it works

- Articles and revisions are separate tables; `articles.current_rev` points at the live revision.
- On save, `App\Services\ArticleRenderer` converts Markdown to HTML, resolves `[[Article]]` and `[[Article|display]]` links (red when the target does not exist), sanitises with HTMLPurifier, and adds heading ids. The HTML is stored on the revision, so reads are cheap.
- Link colour is decided at save time. Creating an article later does not recolour red links in older revisions until those pages are saved again.
- Reading preferences (theme, size, line width) are stored in `localStorage` under `wiki:prefs`.

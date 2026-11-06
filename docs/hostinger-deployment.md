# Hostinger client demo deployment

This project is Laravel 13 and requires PHP 8.3 or newer. Hostinger currently offers PHP 8.3+ and GitHub deployment for custom PHP sites, but confirm the exact plan has the required PHP extensions, MySQL, and Git integration before deployment.

## Recommended layout

Keep the Laravel application outside the subdomain's public web root. Point the subdomain document root at the application's `public` directory. For example:

```text
/home/ACCOUNT/apps/thameside-erp/       # repository, .env, vendor, storage, app code
/home/ACCOUNT/domains/demo.hasbyte.com/public_html/  # contents of Laravel public/
```

If Hostinger's Git deploy integration can only deploy into `public_html`, do not deploy the whole repository there. Use SSH/Git to clone the repository into a private directory and configure the subdomain document root to that project's `public` directory, or configure a `public_html` front controller that points to the private project directory. Never make the Laravel project root, `.env`, `vendor`, `storage`, or `database` publicly accessible.

## Before deployment

1. Create the subdomain in hPanel and enable HTTPS.
2. Confirm PHP 8.3+ is selectable for that subdomain and enable the extensions required by `composer.json` and Laravel (including PDO MySQL, mbstring, OpenSSL, tokenizer, XML, cURL, fileinfo, and GD or Imagick if required by enabled features).
3. Create a MySQL database and a database user in hPanel.
4. Keep the GitHub repository private unless the source is intentionally public. `.env` is ignored by Git; never add it to the repository.
5. Build frontend assets before publishing with `npm ci` and `npm run build`. The generated `public/build` directory is ignored by Git, so include it in the release artifact or build it on the server if Node.js is available.
6. Install production PHP dependencies with `composer install --no-dev --optimize-autoloader` in the private application directory.
7. Create the server `.env` directly on the host. Set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://demo.hasbyte.com`, a newly generated `APP_KEY`, MySQL settings, and appropriate session/cache settings. Do not copy the local `.env`.
8. Run migrations with `php artisan migrate --force`, then `php artisan storage:link` if required. Set writable permissions for `storage` and `bootstrap/cache`.

## Demo accounts and data

Do not run `php artisan db:seed` on a public site in its current form. It creates accounts with the publicly known password `password`, and the login page advertises those accounts. Before a client-facing deployment, replace that demo login flow with a unique, rotated credential or create a separate client demo account on the deployed database. Prefer a read-only demo role or protect the demo behind HTTP authentication so visitors cannot alter or delete the sample ERP records.

## Release checklist

- Confirm the subdomain document root serves only the Laravel `public` directory.
- Confirm `.env`, source files, logs, database files, and storage files cannot be fetched over HTTP.
- Confirm `APP_DEBUG=false`, HTTPS works, and the app uses the intended MySQL database.
- Sign in with the dedicated demo user and verify core pages and assets load.
- Keep database backups and remove the demo database/account when the client review is finished.

Hostinger documentation: [Git deployment](https://www.hostinger.com/support/1583302-how-to-deploy-a-git-repository-in-hostinger/), [PHP versions](https://www.hostinger.com/support/1575755-how-to-change-the-php-version-of-your-hostinger-hosting-plan/), and [SSH access](https://www.hostinger.com/support/1583645-how-to-enable-ssh-access-in-hostinger/).

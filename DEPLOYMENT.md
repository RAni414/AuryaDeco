# Deploying AuryaDeco on EasyPanel

AuryaDeco is a PHP 8 + MySQL shop served by Apache. In production it runs as **one Docker app**
(built from the `Dockerfile` in this repo) plus **one MySQL/MariaDB service**. Product and review
photos live on a **volume**.

```
Internet ── Traefik (EasyPanel, HTTPS) ── app :8080 (PHP + Apache, non-root) ── MariaDB :3306
                                              └── volume: /var/www/html/uploads
```

## 1. Create the database service

1. In your EasyPanel project: **+ Service → MariaDB** (MySQL 8 also works).
2. Name it, e.g. `auryadeco-db`. Set a database name (`auryadeco`), a user (`auryadeco`) and a
   **strong password**. Deploy it.
3. Note the **internal host** shown by EasyPanel. It is `<project>_<service>`, for example
   `gouidex_auryadeco-db`. The database is only reachable inside EasyPanel. Do not expose it publicly.

The app creates the tables itself on first start. A new, empty database gives you a fresh shop.

## 2. Create the app

1. **+ Service → App**, name it, e.g. `auryadeco`.
2. **Source → GitHub**: owner `RAni414`, repository `AuryaDeco`, branch `main`, build path `/`.
   The EasyPanel GitHub token must have access to this private repository.
3. **Build → Dockerfile**, file `Dockerfile`. No build arguments are needed.
4. **Environment**: add the variables from the table below (start from `.env.example`).
5. **Mounts → Add Volume**: name `uploads`, mount path **`/var/www/html/uploads`**.
   Use a *Volume*, not a *Bind mount*. The container runs as `www-data` (uid 33), and a volume
   inherits the right owner automatically.
6. **Domains → Add domain**: your domain (for example `auryadeco.tn`), **port `8080`**, HTTPS on.
   EasyPanel/Traefik issues the Let's Encrypt certificate. Point the domain's DNS `A` record
   (and `www`) to the server IP first.
7. **Deploy**. Watch the logs. A good first start prints:
   ```
   [auryadeco] Shop installed. Admin account 'admin' created.
   [auryadeco] Database ready (schema version 3).
   ```
8. Open `https://your-domain/admin/` and sign in with `ADMIN_USER` / `ADMIN_PASSWORD`.
   Then change the password in **Mon compte**. You may remove `ADMIN_PASSWORD` from the
   environment afterwards: it is only read when the database is empty.

## 3. Environment variables

All variables are **runtime** variables (read when the container starts or on each request).
No build-time variables or build arguments are needed: there is no frontend build step.

| Name | Required | Description | Example |
|---|---|---|---|
| `DB_HOST` | yes | Internal host of the database service. Setting it switches the app to env-based config. | `gouidex_auryadeco-db` |
| `DB_PORT` | no | Database port (default `3306`). | `3306` |
| `DB_NAME` | yes | Database name. | `auryadeco` |
| `DB_USER` | yes | Database user. | `auryadeco` |
| `DB_PASSWORD` | yes | Database password. **Secret.** | *(strong password)* |
| `ADMIN_USER` | first start | Admin login created when the database is empty (3–60 chars: letters, digits, `.`, `_`, `-`). | `admin` |
| `ADMIN_PASSWORD` | first start | Admin password, minimum 8 characters. Only used when the database is empty. **Secret.** | *(strong password)* |
| `SHOP_NAME` | no | Shop name stored at first install (default `AuryaDeco`). | `AuryaDeco` |
| `SEED_DEMO_PRODUCTS` | no | `1` adds the demo catalogue at first install (default `0`). | `0` |
| `GEMINI_API_KEY` | no | Google Gemini key for the decoration assistant. Empty means demo mode. **Secret.** | *(from Google AI Studio)* |
| `GEMINI_MODEL` | no | `gemini-3.1-flash-lite` (default) or `gemini-2.5-flash-lite`. | `gemini-3.1-flash-lite` |
| `PORT` | no | Port Apache listens on inside the container (default `8080`). Must match the domain port. | `8080` |
| `APP_TIMEZONE` | no | PHP timezone (default `Africa/Tunis`). | `Africa/Tunis` |
| `APP_DEBUG` | no | `1` shows PHP errors in pages. **Never in production.** | `0` |
| `BASE_URL` | no | Only if the shop is served under a sub-path, e.g. `/shop`. Leave empty otherwise. | *(empty)* |
| `DB_WAIT_SECONDS` | no | How long startup waits for the database (default `60`). | `60` |

## 4. Health check, port and logs

- **Port**: the container listens on `0.0.0.0:8080` (`PORT`).
- **Health check**: `GET /health` returns `200 {"status":"ok"}` when PHP and the database answer,
  otherwise `503`. The Dockerfile `HEALTHCHECK` calls it every 30 s.
- **Logs**: Apache access/error logs and PHP errors go to the container output (EasyPanel → Logs).
  Client IPs are the real visitor IPs (mod_remoteip trusts Traefik on private networks).
  **After the first deploy, check the access log shows real visitor IPs.** If every line shows
  the same proxy IP (for example Cloudflare in front of Traefik), all visitors share one login and
  assistant limit. Fix it by setting `RemoteIPHeader CF-Connecting-IP` (Cloudflare) in
  `docker/apache.conf`, or by enabling forwarded headers in Traefik.
- **Shutdown**: Apache stops gracefully on `SIGWINCH` (set in the image).

## 5. Updates, migrations and backups

- **Updating**: push to `main`, then **Deploy** in EasyPanel (or enable auto-deploy).
- **Migrations** run automatically at startup (`docker/install-cli.php`) and on each request
  (`includes/migrate.php`). They only add tables or columns and never delete data.
- **Sessions** are kept inside the container. A redeploy signs admins out; shop carts are reset.
- **Back up** both the database (EasyPanel → database service → Backups, or `mysqldump`) and
  the `uploads` volume. Products and orders are in the database, and photos are in the volume.

## 6. Security notes

- The web installer `install.php` is disabled in Docker (config comes from the environment).
- Security headers (CSP, HSTS over HTTPS, X-Frame-Options, nosniff, Referrer-Policy,
  Permissions-Policy) are set in `.htaccess`. `X-Powered-By` and the Apache version are hidden.
- Admin login is limited per session (5 tries / 5 min) **and** per IP (10 tries / 15 min).
  The decoration assistant has per-IP, per-session and site-wide limits.
- Uploaded files are checked (JPG/PNG/WebP, 10 MB max), re-encoded to JPEG, and PHP can never run
  in `uploads/`.
- There is no cross-origin API, so no CORS headers are sent (same-origin only).

## 7. Test locally with Docker

```bash
docker compose up --build
```

Then open http://localhost:8080 (admin: `admin` / `local-dev-admin`; these are local test values only).
If port 8080 is taken: `APP_PORT=8095 docker compose up --build`. `docker-compose.yml` is for local
testing only. EasyPanel builds from the `Dockerfile`.

## 8. Classic hosting (without Docker)

Still supported: upload the files, open `install.php`, then delete it. See `LISEZMOI.md`.
When `DB_HOST` is not set, the app reads `config.php` as before.

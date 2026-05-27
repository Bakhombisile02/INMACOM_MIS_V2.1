# INMACOM MIS V2 — Hostinger Deployment Guide

Drop-in deployment bundle for Hostinger shared / cloud hosting.

```
lets go live/
├── database/
│   └── inmacom.sql        ← Import via phpMyAdmin
├── public_html/           ← Upload contents into Hostinger's public_html
│   ├── .htaccess
│   ├── index.php          (already patched to look one folder up at ../app/)
│   ├── build/             (Vite production assets, pre-built)
│   ├── animations/
│   ├── images/
│   ├── favicon.ico
│   └── robots.txt
├── app/                   ← Upload to /home/<user>/domains/<domain>/app/
│   ├── .env.production    (rename to .env on server)
│   ├── vendor/            (already installed, --no-dev --optimize-autoloader)
│   ├── app/, bootstrap/, config/, database/, lang/, resources/views/, routes/, storage/
│   └── ...
└── DEPLOY.md              ← This file
```

## 0. Prerequisites

- Hostinger plan with **PHP 8.3** support (set under hPanel → Advanced → PHP Configuration).
- MySQL database already provisioned (you have):
  - Database: `u550237388_INMACOMV2`
  - Username: `u550237388_inmacomadminv2`
  - Password: `INMACOM@Version2`
- Domain pointed at this Hostinger account (or use the temporary `*.hostingersite.com` preview URL).
- A Firebase Admin SDK service-account JSON file ready to upload.

## 1. Confirm PHP version

hPanel → **Advanced → PHP Configuration** → select **PHP 8.3** → save.
Required extensions (all enabled by default on Hostinger 8.3):
`bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `zip`, `gmp` (for Firebase JWT signing).

## 2. Upload the application files

### 2a. Upload `app/` to a private folder (above `public_html`)

In **hPanel → File Manager**, navigate to your home directory and upload (or drag-and-drop) the entire `lets go live/app/` folder so it ends up at:

```
/home/u550237388/domains/<your-domain>/app/
```

(If your home layout differs, anywhere works as long as it is **a sibling of `public_html`** — i.e. the same folder that *contains* `public_html`. If you put it somewhere else, edit `public_html/index.php` and change the line `$APP_ROOT = realpath(__DIR__.'/../app');` to point at the correct absolute path.)

### 2b. Upload `public_html/` contents into Hostinger's `public_html`

Upload the **contents** of `lets go live/public_html/` (not the folder itself) into:

```
/home/u550237388/domains/<your-domain>/public_html/
```

Result inside `public_html/`:
```
.htaccess   index.php   favicon.ico   robots.txt
animations/   build/   images/
```

### 2c. Rename `.env.production` → `.env`

In File Manager, navigate to `app/`, rename `.env.production` to `.env`, then edit it and set:

- `APP_URL=https://your-real-domain.com` (no trailing slash)
- `MAIL_USERNAME=` and `MAIL_PASSWORD=` (your Hostinger SMTP credentials)
- `MAIL_FROM_ADDRESS="noreply@your-real-domain.com"`

`APP_KEY` and Firebase web-config keys are already filled in. **Do not edit them** unless you rebuild the JS bundle.

### 2d. Upload Firebase service-account JSON

Place your Firebase Admin SDK JSON at:

```
/home/u550237388/domains/<your-domain>/app/storage/app/firebase-service-account.json
```

Right-click → Permissions → set to `0600` (owner read/write only).

The placeholder file `firebase-service-account.json.README` can stay or be deleted.

## 3. Import the database

hPanel → **Databases → phpMyAdmin** → log in → select `u550237388_INMACOMV2` → **Import** tab → choose `lets go live/database/inmacom.sql` → click **Go**.

After import you should see (Structure tab):

- 47 tables
- `stations` → 30 rows
- `management_areas` → 15 rows
- `water_quality_parameters` → 39 rows
- `compliance_thresholds` → 510 rows
- `measurements` → 54 rows
- `registration_pins` → 1 row (code `RFC42M`, role `clerk_admin`)

The bootstrap PIN `RFC42M` is your first-time admin sign-up token. Use it once to create the first `clerk_admin` user; it self-consumes.

### 3a. (Optional) Add production data

Open `inmacom.sql` in any text editor and scroll to the marker:

```sql
-- PASTE PRODUCTION DATA BELOW THIS LINE
```

Append `INSERT INTO ...` statements for your live stations, measurements, documents, etc. Re-import (or run via phpMyAdmin's **SQL** tab).

## 4. First-boot caching

### Path A — SSH available (Business / Cloud / VPS plans)

```bash
ssh u550237388@your-server-host
cd ~/domains/<your-domain>/app
php artisan storage:link         # creates public_html/storage symlink
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Path B — No SSH (Single / Premium plans)

1. Open **hPanel → File Manager**, navigate to `public_html/`.
2. Create a new file `_bootstrap.php` with this content (one-time use):

   ```php
   <?php
   // DELETE THIS FILE IMMEDIATELY AFTER FIRST SUCCESSFUL RUN
   if (($_GET['token'] ?? '') !== 'change-this-secret-then-delete') {
       http_response_code(403); exit('Forbidden');
   }
   require __DIR__.'/index.php';   // boots the framework
   $kernel = app(\Illuminate\Contracts\Console\Kernel::class);
   foreach (['storage:link', 'config:cache', 'route:cache', 'view:cache'] as $cmd) {
       echo "<pre>$cmd:\n";
       $kernel->call($cmd);
       echo $kernel->output()."</pre>";
   }
   ```

3. Visit `https://your-domain.com/_bootstrap.php?token=change-this-secret-then-delete` once.
4. **Delete `_bootstrap.php`** from File Manager. Do not leave it on the server.

### Path C — Storage symlink without shell access

If `php artisan storage:link` cannot run, in File Manager **copy** (don't move) the folder `app/storage/app/public/` into `public_html/storage/`. Re-copy after every upload. Long-term you want SSH for this.

## 5. Smoke tests

| Check | Expected |
|---|---|
| `GET https://<domain>/` | 200, landing page renders |
| `GET https://<domain>/explore` | 200, public GIS map loads |
| `GET https://<domain>/login` | 200, Firebase sign-in box appears |
| `GET https://<domain>/.env` | 403 |
| `GET https://<domain>/storage` (after storage:link) | dir listing denied, individual public files served |
| Register flow with PIN `RFC42M` | Creates first `clerk_admin` user, PIN is consumed |

## 6. Firebase configuration on the Firebase Console

In **Firebase Console → Authentication → Settings → Authorized domains**, add:

- `your-real-domain.com`
- `u550237388.hostingersite.com` (Hostinger preview, if used during testing)

Otherwise sign-in popups will be blocked.

## 7. Updating later

To deploy code changes:

1. Locally: `npm run build` → copy updated `public/build/` to `public_html/build/`.
2. Locally: `composer install --no-dev --optimize-autoloader` → upload changed files under `app/` (most often only `app/`, `routes/`, `resources/views/`, `database/migrations/`).
3. If migrations changed: re-run them.
   - SSH: `php artisan migrate --force`.
   - No SSH: hand-write the migration as SQL and run via phpMyAdmin, OR temporarily add `migrate` to the `_bootstrap.php` command list above.
4. Clear caches:
   - SSH: `php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache`.
   - No SSH: re-run `_bootstrap.php` (don't forget to delete it again).

## 8. Troubleshooting

| Symptom | Fix |
|---|---|
| 500 error, "Deployment misconfigured: Laravel app folder not found" | `app/` folder isn't the sibling of `public_html/`. Edit `public_html/index.php` line `$APP_ROOT = realpath(__DIR__.'/../app')` and point at the absolute path. |
| 500 error, "No application encryption key has been specified" | `APP_KEY` missing in `.env`. Generate locally with `php artisan key:generate --show` and paste. |
| Vite "Manifest not found" | `public_html/build/manifest.json` missing or 403. Re-upload `build/`. |
| Firebase: `auth/unauthorized-domain` | Add your domain in Firebase Console → Authentication → Settings → Authorized domains. |
| Firebase: server-side "no_pin" error on register | Bootstrap PIN already consumed. Create new PINs via the admin console after first sign-in. |
| `SQLSTATE[HY000] [1045] Access denied` | `DB_USERNAME` / `DB_PASSWORD` typo in `.env`. Hostinger expects `127.0.0.1` or `localhost` as host. |
| `Class "Redis" not found` | Don't set `CACHE_STORE=redis` on shared hosting; keep `database`. |
| Mail timeouts | Confirm Hostinger SMTP port (`465` SSL or `587` TLS) and use the **email account** password, not your hPanel password. |
| File uploads 413 | hPanel → Advanced → PHP Configuration → raise `upload_max_filesize` and `post_max_size`. |

## 9. Security checklist before going public

- [ ] `APP_DEBUG=false` in `.env`
- [ ] `.env` file permissions are `0640` or stricter
- [ ] `firebase-service-account.json` permissions are `0600`
- [ ] `_bootstrap.php` has been deleted (if used)
- [ ] `https://<domain>/.env` returns 403
- [ ] `https://<domain>/composer.json` returns 403
- [ ] Auto-generated `RFC42M` PIN has been used and consumed
- [ ] Firebase Authorized domains list contains only your real domain(s)
- [ ] HTTPS forced (already in `.htaccess`)

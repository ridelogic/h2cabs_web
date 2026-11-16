# H2 CABS — Deployment Guide

This guide shows how to deploy the H2 CABS Laravel app **without exposing application files** and without restructuring the project.

## The one rule

> **The web server's document root must be the Laravel `public/` directory — never the project root.**

Only `public/` is meant to be reachable from the internet. Everything else stays outside the web root, including `.env` (secrets), `vendor/`, `storage/`, `app/`, `config/`, `database/` and `composer.json`.

**Do not**:

- move `public/index.php` into the project root;
- create a `public_html/` folder inside the project;
- upload the whole project into `public_html/`;
- "fix" a blank page or 404 by changing paths inside `public/index.php`.

If the document root points at the project root, anyone could download `https://your-domain/.env`. That file holds your `APP_KEY` and database password.

---

## Build before you deploy

Run these on your machine or in CI. Compiled assets go to `public/build/`, which is git-ignored, so they must be built and uploaded.

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

Upload `public/build/` with the release. Node is **not** needed on the server.

---

## Option A — cPanel (shared hosting)

### Recommended layout

```
/home/CPANELUSER/
├── h2cabs/                 ← whole Laravel project (NOT web-accessible)
│   ├── app/  bootstrap/  config/  database/  resources/  routes/
│   ├── storage/  vendor/
│   ├── .env
│   └── public/             ← the ONLY web-accessible folder
│       ├── index.php
│       ├── .htaccess
│       ├── build/
│       └── images/
└── public_html/            ← cPanel default, left untouched
```

### A1. Point the domain at `h2cabs/public` (preferred)

1. Upload the project to `/home/CPANELUSER/h2cabs` with File Manager, Git Version Control or SFTP. Do **not** upload it into `public_html`.
2. In cPanel go to **Domains** and choose **Manage** (or **Addon Domains** / **Subdomains** on older cPanel).
3. Set the **Document Root** to `h2cabs/public`.
   - Some hosts lock the primary domain's document root. If yours does, use **A2**, or ask support to change it. Most hosts will.
4. In **MultiPHP Manager**, set PHP **8.2 or newer** for the domain. Enable `mbstring`, `openssl`, `pdo_mysql` (or `pdo_sqlite`), `fileinfo`, `tokenizer`, `xml` and `ctype`.

### A2. Primary domain document root is locked: use a symlink

Only do this if you have SSH (Terminal) access. Back up `public_html` first.

```bash
cd ~
mv public_html public_html_backup_$(date +%Y%m%d)
ln -s ~/h2cabs/public ~/public_html
```

The web server still only serves `h2cabs/public`, and the project files stay one level above it.

> Avoid the common "copy `public/*` into `public_html` and edit `index.php` paths" workaround. Every deploy has to repeat it, it breaks `public_path()` and Vite asset paths, and it is the usual cause of the recurring `public` / `public_html` problems. Use it only as a last resort with the host's help.

### A3. Configure and finish (cPanel Terminal or SSH)

Follow **[Production release procedure](#production-release-procedure)** below: back up first, then configure `.env`, migrate, seed, link storage, create the admin and cache.

`APP_ENV=production` is what lets search engines index the site. Every other environment sends `noindex, nofollow`.

### File permissions

- Directories `755`, files `644`.
- `storage/` and `bootstrap/cache/` must be writable by the PHP user. On most cPanel hosts PHP runs as your account user, so `755` is enough.
- Never set `777`.

---

## Option B — VPS (Ubuntu with Nginx or Apache)

### Layout

```
/var/www/h2cabs/            ← project root, owned by a deploy user
/var/www/h2cabs/public      ← web server document root
```

```bash
sudo chown -R deploy:www-data /var/www/h2cabs
sudo find /var/www/h2cabs -type d -exec chmod 755 {} \;
sudo find /var/www/h2cabs -type f -exec chmod 644 {} \;
sudo chmod -R ug+rwx /var/www/h2cabs/storage /var/www/h2cabs/bootstrap/cache
```

### Nginx

```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    root /var/www/h2cabs/public;          # ← public/, not the project root

    index index.php;
    charset utf-8;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Deny dotfiles such as .env and .git, but keep .well-known for certificates.
    location ~ /\.(?!well-known).* {
        deny all;
    }

    location /build/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

Then add HTTPS with `sudo certbot --nginx -d your-domain.com -d www.your-domain.com`.

### Apache

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    DocumentRoot /var/www/h2cabs/public

    <Directory /var/www/h2cabs/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Enable `mod_rewrite` (`sudo a2enmod rewrite`). The project's `public/.htaccess` handles routing.

### Deploy steps on the VPS

```bash
cd /var/www/h2cabs
git pull                                   # or upload the release
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo systemctl reload php8.2-fpm
```

---

## Production release procedure

Run every command **on the production server, from the project directory**, after confirming which database `.env` points at. Never run these against a database you have not identified.

### 1. Before you start

```bash
cd /path/to/h2cabs
grep -E '^(APP_ENV|APP_URL|DB_CONNECTION|DB_HOST|DB_DATABASE)=' .env   # confirm the target
php artisan migrate:status                                            # see what will run
```

**Back up the database and `storage/app/public` first.** For MySQL: `mysqldump --single-transaction -u USER -p DBNAME > h2cabs-$(date +%F).sql`. On cPanel, use **Backup** or **phpMyAdmin → Export**. Keep the backup off the web root.

### 2. `APP_KEY`

- On the **first** deploy only: `php artisan key:generate`. It writes a random key into `.env`.
- **Never** regenerate it on a live site. That logs everyone out and makes encrypted data unreadable.
- Never commit it, share it or copy it between environments. Store a copy in your password manager.
- If it ever leaks, rotate it (`key:generate`) during a quiet period, knowing that all sessions end.

### 3. Database (production)

Use MySQL/MariaDB (or PostgreSQL) in production rather than the local SQLite file:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=h2cabs
DB_USERNAME=h2cabs_app       # a dedicated user, not root
DB_PASSWORD=long-random-password
```

Grant that user rights on this database only. `SESSION_DRIVER=database`, `CACHE_STORE=database` and `QUEUE_CONNECTION=database` use the tables created by the migrations.

### 4. Upload code and assets

Build locally or in CI (see **Build before you deploy**), then upload the project **including `public/build/` and `vendor/`**, excluding `.env`, `node_modules/`, `storage/logs/*` and the local `database/database.sqlite`.

### 5. Migrate and seed

```bash
php artisan down --retry=60          # optional maintenance page during the update
php artisan migrate --force          # additive migrations; review migrate:status output first
php artisan db:seed --force          # idempotent: creates missing reference members, vehicles and proposed fares only
```

The seeders never overwrite admin edits, never re-create deleted members and create **no** user accounts.

### 6. Storage link and permissions

```bash
php artisan storage:link             # public/storage → storage/app/public (member/vehicle photos)
chmod -R ug+rwX storage bootstrap/cache
```

Only `storage/app/public` is web-visible (through the link). `storage/logs`, `storage/framework` and `storage/app/private` must **not** be reachable. They are outside `public/`, so a correct document root protects them. Never copy `storage/` into `public/`.

### 7. First administrator

```bash
php artisan h2cabs:create-admin owner@your-domain.com --name="Owner"
```

The password is typed at a hidden prompt (minimum 10 characters) and never appears in shell history. Create one account per person, don't share logins, and use a password manager. Re-running the command for the same email resets that password.

### 8. Caches and going live

```bash
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan up
```

Re-run `config:cache` after **any** `.env` change. On a VPS, also reload PHP-FPM: `sudo systemctl reload php8.2-fpm`.

### 9. HTTPS

- **cPanel:** enable **SSL/TLS Status → AutoSSL** (Let's Encrypt) for the domain and `www`, then force HTTPS under **Domains → Force HTTPS Redirect**.
- **VPS:** `sudo certbot --nginx -d your-domain.com -d www.your-domain.com` (or `--apache`). Certbot renews automatically; check with `sudo certbot renew --dry-run`.
- Set `APP_URL=https://…` so canonical, sitemap and Open Graph URLs use HTTPS.

### 10. Rolling back

- **Code:** redeploy the previous release, then run the cache commands again.
- **Database:** restore the backup from step 1. Avoid `migrate:rollback` on production. Rolling back the Local/Rental split migration deletes Rental fare rules and their rental packages.
- Never run `migrate:fresh`, `migrate:reset`, `db:wipe` or `key:generate` on production.

## Post-deploy checklist

Security:

- [ ] `https://your-domain/.env` returns **404 or 403**, never the file.
- [ ] `https://your-domain/composer.json`, `/storage/logs/laravel.log`, `/vendor/autoload.php` and `/database/database.sqlite` all return **404**.
- [ ] `APP_DEBUG=false`. An error page shows a generic message, never a stack trace.
- [ ] `http://` redirects to `https://`, and the certificate is valid for both domain names.
- [ ] `https://your-domain/admin` redirects to the login page, and five wrong passwords trigger the lockout message.
- [ ] Page source contains no Google server key: only `GOOGLE_MAPS_BROWSER_KEY` may appear, and only on pages with the booking form.

Site:

- [ ] `https://your-domain/` loads with styles (`/build/assets/...` returns 200).
- [ ] `https://your-domain/sitemap.xml` returns XML with `https://` URLs on your real domain.
- [ ] Page source shows `<meta name="robots" content="index, follow">`. If not, check `APP_ENV=production` and re-run `php artisan config:cache`.
- [ ] Canonical and `og:url` use the real domain. If they don't, set `APP_URL` and clear the config cache.
- [ ] Update `public/robots.txt` with `Sitemap: https://your-domain/sitemap.xml`.
- [ ] The About page lists the members, and `/admin` → Members shows them.
- [ ] A test booking of each type (Local, Rental, Outstation One Way, Outstation Round Trip) reaches the review page with a **booking reference**, and appears in Admin → **Booking Requests** with status "New request". With `H2CABS_WHATSAPP` set, **Open WhatsApp to send** opens your business chat with the full message, including the reference. Delete or cancel the test requests afterwards.
- [ ] The dashboard's setup checklist shows no missing items you rely on.
- [ ] Fares have been reviewed and marked **Approved tariff** before they are presented as final prices.

## Production environment variables

Set these in the server's `.env` (never in Git; `.env` is git-ignored). Run `php artisan config:cache` after any change.

| Variable | Required | Purpose |
|---|---|---|
| `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://your-domain` | Yes | Indexing, error pages, canonical/sitemap URLs |
| `APP_KEY` | Yes | Set once with `php artisan key:generate` |
| `DB_*` | Yes | Database connection |
| `H2CABS_WHATSAPP` | For WhatsApp booking | Business WhatsApp number, e.g. `+91 98765 43210`. Without it no WhatsApp links appear. |
| `H2CABS_PHONE`, `H2CABS_EMAIL`, `H2CABS_ADDRESS`, `H2CABS_HOURS` | Recommended | Contact details; blank values are hidden |
| `H2CABS_PASSENGER_ANDROID_URL`, `H2CABS_PASSENGER_IOS_URL` | Only when published | Real `https://` store listing URLs. Store buttons and QR codes appear only for these. |
| `H2CABS_DRIVER_ANDROID_URL`, `H2CABS_DRIVER_IOS_URL` | Optional | Driver app links |
| `H2CABS_FACEBOOK_URL`, `H2CABS_INSTAGRAM_URL`, `H2CABS_YOUTUBE_URL` | Optional | Footer social links |
| `GOOGLE_MAPS_BROWSER_KEY` | Optional | Places autocomplete in the browser (public, referrer-restricted key) |
| `GOOGLE_PLACES_API_KEY` | Optional | Server-side Places autocomplete (used only when no browser key is set) |
| `GOOGLE_ROUTES_API_KEY` | Optional | Road distance for fare estimates. Falls back to `GOOGLE_PLACES_API_KEY` if blank. |
| `PLACES_PROVIDER` | Optional | Server provider; `google` (default) |

## Business details

Contact numbers, email, address, hours, app store links and social links are read from `.env` (see the `H2CABS_*` keys in `.env.example`). Empty values are hidden or shown as "coming soon". Run `php artisan config:cache` after changing them.

### WhatsApp booking

Set `H2CABS_WHATSAPP` to the business WhatsApp number, for example `H2CABS_WHATSAPP="+91 98765 43210"`. A 10-digit Indian mobile number without `+91` also works.

Every valid booking is **stored first** (Admin → Booking Requests) with a unique reference such as `H2C-261009-7KQ4M` and the status **New request**. The WhatsApp message is then built from that stored record.

- With a valid number, the review page shows the reference and an **Open WhatsApp to send** button. The `https://wa.me/<number>?text=…` link goes to that **one** configured number, with the message percent-encoded (line breaks, `&`, `₹` and so on). It pre-fills the message: the customer must still press **Send** in WhatsApp. When the customer taps the button, the request is marked "WhatsApp opened by customer". The site cannot know whether the message was actually sent, and never marks a request confirmed.
- Without a valid number, no WhatsApp links are shown anywhere. The request is still stored. The review page shows the reference, says WhatsApp is not available, and offers the summary to share by phone or email.

Customer mobile numbers must be exactly **10 digits** (numbers only). They are stored as typed, without adding `+91`.

### Location search: Google Places Autocomplete (required for booking)

Pickup and destination must be **chosen from Google suggestions**: the server requires a Google place ID for the pickup and for any drop-off. There is no manual-entry fallback, so **bookings cannot be submitted until Google location search is configured and working**. Google requires a **billing account** on the Cloud project, and Places requests are billed per Google's current pricing. Set a budget alert.

There are two supported setups. The site picks one automatically:

| Mode | `.env` | How it works |
|---|---|---|
| **Browser (recommended)** | `GOOGLE_MAPS_BROWSER_KEY=...` | The Maps JavaScript API loads in the browser and calls `google.maps.places.AutocompleteSuggestion` (Places API (New)) with session tokens. When a suggestion is chosen, `Place.fetchFields` stores the address and Google place ID. |
| **Server proxy** | `GOOGLE_PLACES_API_KEY=...` (no browser key) | The browser calls `/places/search`, and the server calls Places API (New) Autocomplete. The key never reaches the browser. |
| **Unavailable** | neither key | Location fields and the submit button stay disabled, with "Google location search is unavailable. Please try again later." Bookings cannot be submitted. |

**Browser key setup**

1. In Google Cloud, enable **Maps JavaScript API** and **Places API (New)**.
2. Create an API key and restrict it:
   - **Application restrictions:** HTTP referrers, e.g. `https://your-domain.com/*` and `https://www.your-domain.com/*` (add `http://127.0.0.1:8000/*` only for local testing).
   - **API restrictions:** Maps JavaScript API and Places API (New) only.
3. Set `GOOGLE_MAPS_BROWSER_KEY=...` in `.env` and run `php artisan config:cache`.

This browser key is **visible in the page source by design**; the referrer restriction is what protects it. Never put the server key (`GOOGLE_PLACES_API_KEY` or `GOOGLE_ROUTES_API_KEY`) in the browser key setting.

**Server key setup (proxy mode and route distance):** restrict that key to your server's IP address and to Places API (New) and Routes API.

**How the fields behave (homepage widget and `/booking`):**

- The pickup and destination fields are rendered **disabled** and say "Loading Google location search…". They are enabled only after the Places library has loaded in browser mode, or straight away in proxy mode, where the server has a key.
- If Google is missing, the script fails to load or does not load within 10 seconds, the key is rejected (`gm_authFailure`), or a search request errors, **both location fields and the submit button are disabled**. A notice then says "Google location search is unavailable. Please try again later." No suggestions are invented.
- The notice has a **Try again** button. It reloads the page and restores everything already entered (trip type, dates, vehicle, name, mobile, notes) from the browser tab's session storage.
- Only the location-dependent booking flow is disabled; the rest of the site is unaffected.
- Text typed into a location field without choosing a suggestion is refused, both in the browser and on the server.

Suggestions are shown with the required "powered by Google" attribution. In proxy mode, a rejected server key only shows up on the first search, which then disables the fields.

### Road distance and fare estimates (optional)

Fare estimates need the road distance between pickup and destination. This comes from the **Google Routes API** (`computeRoutes`), called by the server only.

1. Enable **Routes API** on the same Google Cloud project, either on the Places key or on a separate server key.
2. Restrict the key to the Routes API (and Places API (New) if shared) and to your server's IP address.
3. Set `GOOGLE_ROUTES_API_KEY=...` in `.env`. If left blank, `GOOGLE_PLACES_API_KEY` is used.

Without a Routes key, no distance is ever guessed and no amount is shown. Customers see "Fare will be confirmed after the route is verified." (approved fare) or "Fare will be confirmed by H2 CABS." (unapproved fare).

**Fare display rules** (homepage widget, `/booking`, review page, WhatsApp message and stored request all use the same server calculation):

| Fare rule | Distance known | Customers see |
|---|---|---|
| Not approved | either | "Fare will be confirmed by H2 CABS." No amount, rate or minimum is shown anywhere. |
| Approved, no rate/package/return policy | either | "Fare will be confirmed by H2 CABS." |
| Approved | no | "Fare will be confirmed after the route is verified." The configured rate and minimum distance are listed. |
| Approved | yes | "Estimated fare: ₹X — not a confirmed quote", with the breakdown. The amount is stored on the request. |

> Use an **IP-restricted server key** for Routes. A referrer-restricted browser key will be rejected when called from the server.

### Verify Google after adding keys

Live Google responses have **not** been tested with a valid key during development (only failure handling with an invalid key, plus automated tests with simulated responses). After adding keys:

0. Open `/booking`. Within a few seconds the pickup and destination fields should become enabled, with no amber "unavailable" notice. If the notice appears, check the browser console and the key restrictions.
1. Choose **Outstation One Way** and type `Chennai Cen` in the destination. Suggestions with "powered by Google" should appear; pick one with the arrow keys and Enter.
2. Type a pickup and pick a suggestion. The **Fare estimate** box should show an approximate distance, and an amount only if that vehicle's fare is approved.
3. Submit with test details. On the review page, the pickup and destination lines should include a `google.com/maps/place/?q=place_id:…` link.
4. In the browser console there should be no `InvalidKey`, `RefererNotAllowedMapError` or `ApiNotActivatedMapError` warnings. If there are, fix the key restrictions in Google Cloud.
5. Confirm Google Cloud → APIs & Services → Metrics shows the requests, and that a budget alert is set.

## Admin dashboard

Go to `https://your-domain/admin`. There is no public sign-up. Create each administrator on the server:

```bash
php artisan h2cabs:create-admin owner@example.com --name="Owner"
# prompts for a password (min. 10 characters); re-running it for the same email resets the password
```

| Screen | What you can do |
|---|---|
| **Booking Requests** (`/admin/bookings`) | Every booking submitted on the website, newest first, with reference, customer, trip, travel date, fare (estimate or "To be confirmed") and status. Search by reference, name or mobile; filter by booking type, status and travel date range. The detail page (`/admin/bookings/{id}`) shows the full request (as sent on WhatsApp), the fare status and the WhatsApp state. You can set the status (New request → Customer contacted → Confirmed by H2 CABS → Trip completed, or Cancelled) and keep internal notes. A request is "confirmed" only when an admin sets that status. Booking records are never reachable from public URLs: customers see only their own request, through their browser session, on the review page. |
| **Members** | Add, edit and delete members; upload or replace a photo; switch **Active** and **Show on website**; move members up or down; preview the public card. Only active and visible members appear on `/about`. Deleting keeps a hidden record, so a later seed does not bring the member back. |
| **Vehicles** | Add categories such as Hatchback; set capacities only if verified; activate or deactivate; optional image. |
| **Fares** | One fare per vehicle for each of the four booking types (**Local, Rental, Outstation One Way, Outstation Round Trip**); none inherits another's pricing. Local and Outstation have a rate per km, minimum billable km, minimum km per day (outstation), minimum fare, round-trip billing (onward + return, or one way only), and a preview for sample distances. **Rental** is priced by **rental packages** (name, hours, included km, price) plus optional extra-km and extra-hour rates; without packages, customers pick a flexible duration and see "fare to be confirmed". All types support driver allowance per day, waiting charge (shown as a note), one optional fixed charge, tolls/parking extra or included, the **Approved tariff** flag, an active flag and an effective date. |
| **Settings** | Contact phone, **WhatsApp number**, email, address, hours; passenger/driver app store links (Google Play / App Store URLs only); Facebook/Instagram/YouTube links; logo mark upload (PNG/JPG/WebP, no SVG). A saved value overrides `.env`; leaving a field blank uses the `.env` value. Changes are live immediately. |
| **SEO** | Title, meta description and "allow search engines to index" for each public page (Home, About Us, Our Services, Destinations, Booking, Contact Us, Photo credits). Blank fields use the built-in defaults; a hidden page gets `noindex` and leaves the sitemap. |

Fares stay marked **proposed** until you tick **Approved tariff**. While proposed, customers see **no amount, rate or minimum**, only "Fare will be confirmed by H2 CABS" ("Fare confirmed on booking" on vehicle cards). The admin fare preview still calculates amounts and labels them "Admin preview only".

**Roles:** there is one level of access: every account created with `h2cabs:create-admin` can use every screen, including approving fares. There are no separate super-admin or editor roles yet, so give admin access only to people allowed to change prices and published details.

### First deployment of this release

```bash
php artisan migrate --force      # members fields, vehicles, fare_rules, users.is_admin, settings, booking_requests (all additive)
php artisan db:seed --force      # 15 reference members + Sedan/SUV proposed fares (safe to re-run)
php artisan storage:link         # needed for member and vehicle photo uploads
php artisan h2cabs:create-admin you@example.com
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

`db:seed` is idempotent. It only creates missing records and never overwrites admin edits, and it creates **no** user accounts.

Uploaded photos are stored in `storage/app/public/members` and `storage/app/public/vehicles` under random names (JPG, PNG or WebP, max 2 MB). On shared hosting where symlinks are not allowed, ask the host to enable them. Do not copy `storage/` into the web root.

### Photos

Website photos are licensed from Wikimedia Commons and credited on `/photo-credits` from `config/photos.php`. To replace one with your own photo, save 3:2 WebP files as `public/images/photos/{key}-{width}.webp` and update that key's entry, including the credit.

## Pending business decisions

These need the association's approval. Until then the website keeps them clearly marked, and nothing here is approved automatically.

| Decision | Current state on the site | Where to change it |
|---|---|---|
| Per-km rates: Sedan ₹13/km, SUV ₹14/km (Local and both Outstation types) | Seeded as **proposed**; customers see no rate or amount, only "Fare will be confirmed by H2 CABS" | Admin → Fares → tick **Approved tariff** once agreed |
| Minimum billable distance of 13 km | Proposed, on the same fares | Admin → Fares |
| Rental packages and prices; extra-km and extra-hour rates | **None configured**; Rental shows "Fare confirmed on booking" / "Fare will be confirmed by H2 CABS" | Admin → Fares → (vehicle) Rental → Rental packages |
| Driver allowance, waiting charges, minimum fare, fixed charges | Not set; customers are told these are "confirmed when you book" | Admin → Fares |
| Tolls and parking policy | Shown as "extra, if any" | Admin → Fares → Tolls and parking |
| Round trips billing onward + return distance | Configured as proposed policy ("both ways") | Admin → Fares → Round trip → Return journey billing |
| Premium and Larger Vehicle categories (₹16/₹18 placeholder rates) | Seeded **inactive** | Admin → Vehicles / Fares |
| Business WhatsApp number | **Not set**, so no WhatsApp links appear | `H2CABS_WHATSAPP` in `.env` |
| App store links | Not set; the app section says "coming soon" with no QR codes | `H2CABS_PASSENGER_*_URL` in `.env` |
| Association name and its relationship to H2 CABS | The reference site names it "Helping Hands Driver's Association – Puducherry" and calls it separate from H2 CABS; site copy follows the project brief. **Needs confirmation.** | `config/site.php` and the About page copy |
| Official logo | Placeholder SVG | `public/images/brand/` |

| Google location search keys | **Not set**, so location fields are disabled and **no booking can be submitted** | `GOOGLE_MAPS_BROWSER_KEY` (or `GOOGLE_PLACES_API_KEY`) in `.env` |

The Google integration has been tested only with simulated responses (a test double for `google.maps.importLibrary` in the browser, faked HTTP in the PHP tests) and, earlier, an invalid key. It has **not** been tested against live Google. Run **Verify Google after adding keys** with real keys before relying on autocomplete or distance-based estimates.

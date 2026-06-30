# Deployment Guide — INMACOM MIS V2.1

This guide covers production deployment to **Google Cloud Run** with **Cloud SQL (PostgreSQL)** and **Firebase Authentication**.

---

## Prerequisites

| Tool | Version | Purpose |
|---|---|---|
| Google Cloud SDK (`gcloud`) | Latest | Deploy to Cloud Run, manage secrets |
| Docker | 20+ | Build and push container image |
| Firebase CLI | Latest | Firebase project configuration |
| `psql` | 15+ | Database migrations |

---

## 1. Google Cloud Project Setup

```bash
# Set your project
gcloud config set project YOUR_PROJECT_ID

# Enable required APIs
gcloud services enable \
  run.googleapis.com \
  sqladmin.googleapis.com \
  secretmanager.googleapis.com \
  artifactregistry.googleapis.com
```

---

## 2. Cloud SQL (PostgreSQL)

### Create the instance

```bash
gcloud sql instances create inmacom-v2-instance \
  --database-version=POSTGRES_15 \
  --tier=db-g1-small \
  --region=YOUR_REGION \
  --storage-type=SSD \
  --storage-size=20GB
```

### Create the database and user

```bash
gcloud sql databases create inmacom --instance=inmacom-v2-instance

gcloud sql users create inmacom_user \
  --instance=inmacom-v2-instance \
  --******
```

---

## 3. Firebase Credentials

### Download the service account key

1. Open the [Firebase Console](https://console.firebase.google.com/) → Project Settings → Service Accounts.
2. Click **Generate new private key** — download the JSON file.

### Store in Secret Manager

```bash
gcloud secrets create firebase-service-account \
  --data-file=path/to/firebase-service-account.json

# Grant Cloud Run access to the secret
gcloud secrets add-iam-policy-binding firebase-service-account \
  --member="serviceAccount:YOUR_SERVICE_ACCOUNT@YOUR_PROJECT.iam.gserviceaccount.com" \
  --role="roles/secretmanager.secretAccessor"
```

> **Never** commit the service account JSON to version control.

---

## 4. Environment Variables in Secret Manager

Store all sensitive `.env` values as individual secrets or as a single `.env` secret:

```bash
# Example: store the entire .env file as a secret
gcloud secrets create inmacom-env --data-file=.env.production
```

Or store individual values:

```bash
echo -n "your-app-key" | gcloud secrets create app-key --data-file=-
echo -n "your-db-password" | gcloud secrets create db-password --data-file=-
```

---

## 5. Build and Push the Container Image

```bash
# Authenticate Docker with Artifact Registry
gcloud auth configure-docker YOUR_REGION-docker.pkg.dev

# Build the image (multi-stage: Node frontend → FrankenPHP backend)
docker build -t YOUR_REGION-docker.pkg.dev/YOUR_PROJECT_ID/inmacom/mis:latest .

# Push
docker push YOUR_REGION-docker.pkg.dev/YOUR_PROJECT_ID/inmacom/mis:latest
```

---

## 6. Deploy to Cloud Run

```bash
gcloud run deploy inmacom-mis \
  --image=YOUR_REGION-docker.pkg.dev/YOUR_PROJECT_ID/inmacom/mis:latest \
  --region=YOUR_REGION \
  --platform=managed \
  --allow-unauthenticated \
  --port=8080 \
  --memory=512Mi \
  --cpu=1 \
  --min-instances=1 \
  --add-cloudsql-instances=YOUR_PROJECT_ID:YOUR_REGION:inmacom-v2-instance \
  --set-secrets="/app/storage/app/firebase-service-account.json=firebase-service-account:latest" \
  --set-env-vars="APP_ENV=production,APP_DEBUG=false,DB_CONNECTION=pgsql,DB_SOCKET=/cloudsql/YOUR_PROJECT_ID:YOUR_REGION:inmacom-v2-instance,DB_DATABASE=inmacom,DB_USERNAME=inmacom_user,SESSION_DRIVER=database,QUEUE_CONNECTION=database,HASH_DRIVER=argon2id,SESSION_ENCRYPT=true" \
  --set-secrets="APP_KEY=app-key:latest,DB_PASSWORD=db-password:latest"
```

> Adjust `--memory`, `--cpu`, and `--min-instances` according to expected load.

---

## 7. First-Deploy Database Migration

After the first deploy, run migrations using Cloud SQL Auth Proxy locally or via a Cloud Run job:

### Option A — Cloud SQL Auth Proxy (local)

```bash
# Start the proxy
cloud-sql-proxy YOUR_PROJECT_ID:YOUR_REGION:inmacom-v2-instance &

# Run migrations
DB_HOST=127.0.0.1 DB_PORT=5432 php artisan migrate --force

# Run seeders (IIMA reference data — one time only)
DB_HOST=127.0.0.1 DB_PORT=5432 php artisan db:seed --class=Database\\Seeders\\DDRIteration2ReferenceDataSeeder --force
```

### Option B — Cloud Run Job

Create a one-off Cloud Run Job that runs `php artisan migrate --force` using the same container image.

---

## 8. ⚠️ Remove the Seeder Route Before Going Live

The route `/run-seed-staging-982347102` in `routes/web.php` is a development convenience that triggers a full database seed with **no authentication**. Remove or comment it out before any production deployment:

```php
// routes/web.php — DELETE THIS BLOCK FOR PRODUCTION
Route::get('/run-seed-staging-982347102', function () { ... });
```

---

## 9. Custom Domain & HTTPS

Cloud Run provides a default `*.run.app` HTTPS URL automatically. To use a custom domain:

```bash
gcloud run domain-mappings create \
  --service=inmacom-mis \
  --domain=mis.inmacom.co.za \
  --region=YOUR_REGION
```

Update your DNS with the provided records. TLS certificates are provisioned automatically by Google.

---

## 10. Queue Worker

INMACOM MIS uses Laravel's database queue driver. For production, run the queue worker as a separate Cloud Run service or a background Cloud Run Job triggered on a schedule. The worker command is:

```bash
php artisan queue:listen --tries=3 --timeout=90
```

---

## 11. Health Check

Cloud Run will route traffic to your container once it passes the startup health check. The default check hits the root `/` path. For readiness, ensure `APP_KEY` and database connectivity are available before the container starts — the `docker-entrypoint.sh` clears the config cache on startup.

---

## 12. Deployment Checklist

- [ ] `APP_ENV=production` and `APP_DEBUG=false`
- [ ] `SESSION_ENCRYPT=true`
- [ ] Firebase service account secret mounted at `FIREBASE_CREDENTIALS` path
- [ ] Database migrations run (`php artisan migrate --force`)
- [ ] Reference data seeded (one-time: `DDRIteration2ReferenceDataSeeder`)
- [ ] Seeder route removed from `routes/web.php`
- [ ] Custom domain DNS configured
- [ ] Queue worker deployed
- [ ] `composer audit` and `npm audit` run with no critical vulnerabilities

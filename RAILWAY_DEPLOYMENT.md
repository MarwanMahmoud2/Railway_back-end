# Railway Deployment Guide with Supabase

## Quick Fix for "Network is unreachable" Error

### Step 1: Configure Railway Settings

1. **Go to Railway Dashboard** → Your Project → Service
2. **Navigate to Settings tab**
3. **Set Start Command:**
   ```
   php artisan serve --host=0.0.0.0 --port=$PORT
   ```
4. **Clear any Build Command** (leave it empty or let Railway auto-detect)

### Step 2: Add Environment Variable

In Railway's Variables tab, add:
```
RAILWAY_RUN_MIGRATIONS=0
```

This prevents Railway from running migrations during build (when network isn't ready).

### Step 3: Verify Supabase Connection

Make sure these environment variables are set correctly in Railway:
```
DB_CONNECTION=pgsql
DB_HOST=db.bmdgoyjhbkorplxhbshf.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=your_supabase_password
DB_SSLMODE=require
```

### Step 4: Deploy

Push your code to Railway. The app should deploy successfully now.

### Step 5: Run Migrations After Deployment

After deployment completes:

1. **In Railway Dashboard**, click on your service
2. **Go to "Shell" or "Console" tab**
3. **Run this command:**
   ```bash
   php artisan app:setup-database
   ```
   
   Or run them separately:
   ```bash
   php artisan migrate --force
   php artisan db:seed --force
   ```

## Troubleshooting

### If you still get "Network is unreachable":

**Option 1: Check Supabase Network Restrictions**
1. Go to Supabase Dashboard
2. Navigate to Project Settings → Database
3. Check "Network Restrictions"
4. Make sure it's set to "Allow all traffic" or add Railway's IP range

**Option 2: Use Supabase Connection Pooler**
Instead of direct connection, use Supabase's connection pooler:
```
DB_HOST=aws-0-[your-region].pooler.supabase.com
DB_PORT=6543
DB_DATABASE=postgres
DB_USERNAME=postgres.[your-project-ref]
DB_PASSWORD=your_supabase_password
```

Find your pooler connection string in Supabase Dashboard → Project Settings → Database → Connection string → Pooler

**Option 3: Add Retry Logic**
In Railway's Start Command, use:
```
sh -c "sleep 5 && php artisan migrate --force || sleep 10 && php artisan migrate --force; php artisan serve --host=0.0.0.0 --port=$PORT"
```

## Files Created/Modified

- `railway.json` - Railway configuration
- `app/Console/Commands/SetupDatabase.php` - Easy database setup command
- `config/database.php` - Improved connection settings

## Environment Variables Needed

Make sure ALL of these are set in Railway:

### Database (Supabase)
```
DB_CONNECTION=pgsql
DB_HOST=db.bmdgoyjhbkorplxhbshf.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=<your_supabase_password>
DB_SSLMODE=require
```

### Application
```
APP_NAME="NBIS Backend"
APP_ENV=production
APP_KEY=base64:L7ZsmyyMmhd1/JtnEYCs1vfW9EpU15hmIPCu+NrVoYE=
APP_DEBUG=false
APP_URL=https://your-app.up.railway.app
```

### Supabase Storage
```
SUPABASE_URL=https://bmdgoyjhbkorplxhbshf.supabase.co
SUPABASE_KEY=<your_supabase_service_role_key>
```

### Seeder Credentials
```
ADMIN_EMAIL=admin@nbis.com
ADMIN_PASSWORD=Admin@1234
NURSE_EMAIL=nurse@nbis.com
NURSE_PASSWORD=Nurse@1234
POLICE_EMAIL=police@nbis.com
POLICE_PASSWORD=Police@1234
```

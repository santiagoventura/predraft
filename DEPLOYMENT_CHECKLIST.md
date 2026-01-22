# Deployment Checklist

Use this checklist when deploying the MLB Fantasy Draft Helper to production or a new environment.

## Pre-Deployment

### 1. Environment Setup
- [ ] Laravel 11.x installed
- [ ] PHP 8.3+ available
- [ ] MySQL/MariaDB 11.2+ available
- [ ] Composer installed
- [ ] Docker & Docker Compose installed (if using Docker)

### 2. API Keys & Credentials
- [ ] Google Gemini API key obtained
- [ ] Database credentials configured
- [ ] APP_KEY generated (`php artisan key:generate`)

### 3. Configuration Files
- [ ] `.env` file created from `.env.example`
- [ ] Database connection configured
- [ ] Gemini API settings configured
- [ ] APP_URL set correctly
- [ ] APP_ENV set to `production`
- [ ] APP_DEBUG set to `false`

## Deployment Steps

### 1. Code Deployment
```bash
# Clone repository
git clone <repository-url>
cd predraft

# Install dependencies
composer install --optimize-autoloader --no-dev

# Copy environment file
cp .env.example .env
# Edit .env with production values
```

### 2. Database Setup
```bash
# Run migrations
php artisan migrate --force

# Verify tables created
php artisan db:show
```

### 3. Data Import
```bash
# Import players
php artisan import:players-csv players.csv

# Import rankings
php artisan import:rankings-csv my_rank.csv --source=my_rankings
php artisan import:rankings-csv third_rank.csv --source=third_party
```

### 4. Optimization
```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

### 5. Docker Deployment (if applicable)
```bash
# Build and start containers
docker-compose up -d --build

# Verify containers running
docker-compose ps

# Check logs
docker-compose logs -f
```

## Post-Deployment Verification

### 1. Application Health
- [ ] Homepage loads (http://your-domain.com)
- [ ] No errors in logs
- [ ] Database connection working
- [ ] Static assets loading

### 2. Core Functionality
- [ ] Can create a league
- [ ] Can create a draft
- [ ] Can start a draft
- [ ] AI recommendations working
- [ ] Can make picks
- [ ] Draft advances correctly

### 3. Performance
- [ ] Page load times acceptable (<2s)
- [ ] Database queries optimized (check query log)
- [ ] No N+1 query issues
- [ ] Gemini API responses timely (<5s)

### 4. Security
- [ ] APP_DEBUG is `false`
- [ ] HTTPS enabled (if production)
- [ ] CSRF protection working
- [ ] API keys not exposed in client-side code
- [ ] Database credentials secure

## Monitoring Setup

### 1. Logging
```bash
# Configure log channel in .env
LOG_CHANNEL=stack

# Set up log rotation
# Add to crontab or use logrotate
```

### 2. Error Tracking
- [ ] Configure error reporting (Sentry, Bugsnag, etc.)
- [ ] Test error notifications
- [ ] Set up alerts for critical errors

### 3. Performance Monitoring
- [ ] Set up APM (New Relic, DataDog, etc.)
- [ ] Monitor database query performance
- [ ] Track Gemini API usage and costs
- [ ] Monitor server resources (CPU, memory, disk)

## Backup Strategy

### 1. Database Backups
```bash
# Set up automated backups
# Example cron job for daily backups:
0 2 * * * docker-compose exec -T db mysqldump -u root -p$DB_PASSWORD mlb_draft > /backups/mlb_draft_$(date +\%Y\%m\%d).sql
```

### 2. File Backups
- [ ] Backup `.env` file (securely)
- [ ] Backup uploaded files (if any)
- [ ] Backup custom configurations

### 3. Backup Testing
- [ ] Test database restore process
- [ ] Verify backup integrity
- [ ] Document restore procedures

## Scaling Considerations

### 1. Database
- [ ] Enable query caching
- [ ] Add database indexes if needed
- [ ] Consider read replicas for high traffic
- [ ] Monitor slow query log

### 2. Application
- [ ] Enable OPcache for PHP
- [ ] Use Redis for session/cache storage
- [ ] Consider horizontal scaling with load balancer
- [ ] Implement queue workers for background jobs

### 3. AI Service
- [ ] Monitor Gemini API quota
- [ ] Implement rate limiting
- [ ] Cache AI responses when appropriate
- [ ] Have fallback strategy ready

## Maintenance

### 1. Regular Updates
```bash
# Update dependencies
composer update

# Run migrations
php artisan migrate

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### 2. Data Refresh
```bash
# Update player data (schedule weekly/monthly)
php artisan import:players-csv latest_players.csv

# Update rankings (schedule as needed)
php artisan import:rankings-csv latest_rankings.csv --source=fantasypros
```

### 3. Health Checks
- [ ] Weekly: Check application logs
- [ ] Weekly: Verify database backups
- [ ] Monthly: Review API usage and costs
- [ ] Monthly: Update dependencies
- [ ] Quarterly: Security audit

## Rollback Plan

### 1. Code Rollback
```bash
# Revert to previous version
git checkout <previous-commit>
composer install
php artisan migrate:rollback
```

### 2. Database Rollback
```bash
# Restore from backup
mysql -u root -p mlb_draft < /backups/mlb_draft_YYYYMMDD.sql
```

### 3. Emergency Contacts
- [ ] Document who to contact for issues
- [ ] Have escalation procedures
- [ ] Keep vendor support contacts handy

## Production Environment Variables

Required `.env` settings for production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_PORT=3306
DB_DATABASE=mlb_draft
DB_USERNAME=your-db-user
DB_PASSWORD=your-secure-password

GEMINI_API_KEY=your-production-api-key
GEMINI_MODEL=gemini-1.5-pro

LOG_CHANNEL=stack
LOG_LEVEL=error

SESSION_DRIVER=database
CACHE_DRIVER=database
QUEUE_CONNECTION=database
```

## Final Checklist

- [ ] All environment variables set
- [ ] Database migrations run
- [ ] Player data imported
- [ ] Caches optimized
- [ ] Logs configured
- [ ] Backups scheduled
- [ ] Monitoring active
- [ ] SSL certificate installed (if production)
- [ ] Domain configured
- [ ] Performance tested
- [ ] Security reviewed
- [ ] Documentation updated
- [ ] Team trained on new system

## Support Resources

- **Documentation**: See README.md and IMPLEMENTATION.md
- **Quick Start**: See QUICKSTART.md
- **Laravel Docs**: https://laravel.com/docs/11.x
- **Gemini API Docs**: https://ai.google.dev/docs
- **Docker Docs**: https://docs.docker.com/

---

**Deployment Date**: _______________
**Deployed By**: _______________
**Version**: _______________
**Notes**: _______________


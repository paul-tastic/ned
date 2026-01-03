# Ned Gameplan

> "Excuse me, I believe you have my... server metrics."

## Phase 0: Foundation (This Week)

### Domain & Branding
- [ ] Secure domain (check: ned.dev, getneddy.com, nedmon.io, askned.dev)
- [ ] Simple logo (nerdy guy with glasses? red stapler? server rack?)
- [ ] GitHub repo: github.com/paulname/ned (or org)

### Repo Setup
- [ ] Laravel 11 project scaffolding
- [ ] Livewire + Flux UI setup
- [ ] Basic auth (Breeze)
- [ ] SQLite config
- [ ] GitHub Actions CI (tests)
- [ ] MIT LICENSE file

## Phase 1: Agent MVP (Week 1-2)

### Goal: Working bash agent that collects and sends metrics

**Files:**
```
agent/
├── ned-agent.sh        # Main agent script
├── install.sh          # One-line installer
└── config.example      # Sample config
```

**Agent Features:**
1. Collect system metrics
   - Load average (`cat /proc/loadavg`)
   - Memory (`free -m`)
   - Disk (`df -h`)
   - Uptime (`uptime -s`)

2. Detect services
   - Check common services via systemctl
   - nginx, apache, mysql, postgres, redis, php-fpm, lsws

3. Security metrics
   - SSH failed attempts (`journalctl` or `/var/log/auth.log`)
   - fail2ban status (`fail2ban-client status`)

4. POST to API
   - JSON payload
   - Bearer token auth
   - Handle failures gracefully

**Test on:** Your current VPS (the one with fail2ban)

## Phase 2: API + Dashboard MVP (Week 2-3)

### Goal: Receive metrics, store, display, alert on thresholds

**Database Schema:**

```sql
-- servers table
CREATE TABLE servers (
    id INTEGER PRIMARY KEY,
    name VARCHAR(255),
    hostname VARCHAR(255),
    token VARCHAR(64) UNIQUE,  -- API auth
    last_seen_at TIMESTAMP,
    status ENUM('online', 'warning', 'critical', 'offline'),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- metrics table (time-series-ish)
CREATE TABLE metrics (
    id INTEGER PRIMARY KEY,
    server_id INTEGER,
    recorded_at TIMESTAMP,
    cpu_load_1m DECIMAL(5,2),
    cpu_load_5m DECIMAL(5,2),
    cpu_load_15m DECIMAL(5,2),
    memory_used_mb INTEGER,
    memory_total_mb INTEGER,
    swap_used_mb INTEGER,
    disk_data JSON,  -- array of mount points
    services_data JSON,  -- array of service statuses
    security_data JSON,  -- ssh attempts, f2b stats
    FOREIGN KEY (server_id) REFERENCES servers(id)
);

-- thresholds table
CREATE TABLE thresholds (
    id INTEGER PRIMARY KEY,
    server_id INTEGER NULL,  -- null = global default
    metric VARCHAR(50),
    warning_value DECIMAL(10,2),
    critical_value DECIMAL(10,2)
);

-- alerts table
CREATE TABLE alerts (
    id INTEGER PRIMARY KEY,
    server_id INTEGER,
    metric VARCHAR(50),
    level ENUM('warning', 'critical'),
    value DECIMAL(10,2),
    threshold DECIMAL(10,2),
    message TEXT,
    notified_at TIMESTAMP NULL,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP
);

-- alert_channels table
CREATE TABLE alert_channels (
    id INTEGER PRIMARY KEY,
    user_id INTEGER,
    type ENUM('email', 'slack', 'discord', 'telegram'),
    config JSON,  -- webhook URL, email, etc.
    enabled BOOLEAN DEFAULT TRUE
);
```

**API Endpoints:**

```php
// routes/api.php

// Server-authenticated (token in header)
Route::middleware('auth:server')->group(function () {
    Route::post('/metrics', [MetricsController::class, 'store']);
});

// User-authenticated
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('servers', ServerController::class);
    Route::get('/servers/{server}/metrics', [MetricsController::class, 'index']);
    Route::put('/servers/{server}/thresholds', [ThresholdController::class, 'update']);
    Route::get('/alerts', [AlertController::class, 'index']);
});
```

**Dashboard Pages:**

1. `/dashboard` - Server grid, status at a glance
2. `/servers/{id}` - Single server details, live-ish metrics
3. `/alerts` - Alert history, acknowledge/resolve
4. `/settings` - Alert channels, global thresholds

## Phase 3: Alerts (Week 3-4)

### Goal: Email alerts when thresholds are breached

**Alert Logic:**
```php
// When metrics come in:
1. Compare each metric against thresholds
2. If breached and no active alert exists → create alert, send notification
3. If alert exists and still breached → do nothing (cooldown)
4. If alert exists and now OK → mark resolved
```

**Email Template:**
```
Subject: 🔴 CRITICAL: prod-web-1 disk usage at 92%

Ned here. We've got a problem.

Server: prod-web-1
Metric: Disk usage (/)
Current Value: 92%
Threshold: 90%
Time: 2025-01-03 14:30:00 UTC

View in Dashboard: https://your-ned.com/servers/1

---
You're receiving this because you have alerts enabled for this server.
Manage notifications: https://your-ned.com/settings
```

**Cooldown Logic:**
- Don't re-alert for same metric until resolved
- Or: 1 hour minimum between repeated alerts for same issue

## Phase 4: Polish & Launch (Week 4-5)

### Documentation
- [ ] README with clear install instructions
- [ ] One-line agent install that actually works
- [ ] Video walkthrough (Loom or similar)

### Testing
- [ ] Agent tests (bats or shunit2)
- [ ] API feature tests (PHPUnit)
- [ ] Load test with 10+ fake servers

### Launch Checklist
- [ ] GitHub repo public
- [ ] Landing page (can use the dashboard login page)
- [ ] Announce on:
  - [ ] Twitter/X
  - [ ] Reddit (r/selfhosted, r/homelab, r/laravel)
  - [ ] Hacker News (Show HN)
  - [ ] Laravel News
  - [ ] Dev.to article

## Phase 5+: Future Features

### v0.2 - More Channels
- Slack webhook integration
- Discord webhook integration
- Telegram bot

### v0.3 - Mobile
- Flutter app
- Push notifications (Firebase)
- Widget for home screen

### v0.4 - Advanced
- HTTP endpoint monitoring (external checks)
- SSL cert expiry monitoring
- Laravel Horizon/Queue integration
- Custom metrics API

### v0.5 - Scale
- PostgreSQL option for larger deployments
- Metric aggregation/rollups (hourly, daily)
- API rate limiting
- Multi-user / teams

## Decisions to Make

### 1. Hosted Demo?
- Offer a free hosted instance for trying it out?
- Pro: Lower barrier to entry
- Con: Costs money, support burden

**Recommendation:** Start self-hosted only, add hosted later if demand

### 2. Metric Retention?
- How long to keep raw metrics?
- Suggestion: 7 days raw, 30 days hourly, 1 year daily

### 3. Agent Language?
- Bash is zero-dependency but limited
- Go binary would be more robust
- **Start with Bash, rewrite in Go if needed**

### 4. Pricing (if ever)?
- Open source core forever
- Potential paid features:
  - Hosted version
  - Priority support
  - Advanced integrations
  - Multi-team features

**Recommendation:** Stay free/open source, accept GitHub sponsors

## Quick Start Dev Commands

```bash
# Create Laravel project
composer create-project laravel/laravel ned
cd ned

# Add Livewire + Flux
composer require livewire/livewire
composer require livewire/flux

# Configure SQLite
touch database/database.sqlite
# Update .env: DB_CONNECTION=sqlite

# Add Breeze for auth
composer require laravel/breeze --dev
php artisan breeze:install livewire

# Run it
npm install && npm run dev
php artisan serve
```

## Success Metrics

### Week 1
- [ ] Agent collecting metrics from your VPS
- [ ] API receiving and storing metrics

### Week 2
- [ ] Dashboard showing server status
- [ ] Basic graphs working

### Week 4
- [ ] Email alerts working
- [ ] README complete
- [ ] Repo public

### Month 1
- [ ] 10+ GitHub stars
- [ ] 1 external user (not you)

### Month 3
- [ ] 100+ GitHub stars
- [ ] 10+ active users
- [ ] Featured in a newsletter or blog

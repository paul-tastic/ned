# Ned

> "Excuse me, I believe you have my... server metrics."

Meet Ned - the basement-dwelling, bespectacled server watcher who keeps an eye on your infrastructure so you don't have to. Self-hosted monitoring and alerting for indie developers.

**N**ever-**E**nding **D**aemon. Or just Ned. He's cool with either.

## Why Ned?

- **Free & Open Source** - MIT licensed, self-host on your own infra
- **Dead Simple Setup** - One curl command to install the agent
- **Push-Based** - Agents push metrics to your dashboard (no firewall config needed)
- **Multi-Server** - Monitor all your boxes from one dashboard
- **Alert Channels** - Email, Slack, Discord, Telegram, push notifications

## What Ned Watches

### System Metrics
- CPU usage (1m, 5m, 15m load averages)
- Memory usage (used, available, swap)
- Disk usage (per mount point)
- Network I/O (bytes in/out)
- Uptime

### Services
- **Auto-detect running services** - No configuration needed, agent discovers what's running
- Supports systemd, OpenRC (Alpine), and SysVinit
- Baseline services (sshd, cron) always monitored if present
- Process monitoring (is X running?)

### Linux Distribution Support
- Automatic distro detection (Debian, Ubuntu, RHEL, CentOS, Rocky, Alma, Fedora, Arch, Alpine, openSUSE)
- Reports distro version and pretty name
- Agent adapts to init system (systemd, OpenRC, SysVinit)

### Security
- SSH login attempts (successful/failed)
- fail2ban stats (banned IPs, jail status)
- SSL certificate expiry

### Web
- HTTP endpoint checks (status code, response time)
- SSL certificate monitoring

### Application (Future)
- Laravel queue health
- Laravel failed jobs
- Custom application metrics via API

## Architecture

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   Server 1      │     │   Server 2      │     │   Server N      │
│   ┌─────────┐   │     │   ┌─────────┐   │     │   ┌─────────┐   │
│   │  Agent  │   │     │   │  Agent  │   │     │   │  Agent  │   │
│   └────┬────┘   │     │   └────┬────┘   │     │   └────┬────┘   │
└────────┼────────┘     └────────┼────────┘     └────────┼────────┘
         │                       │                       │
         └───────────────────────┼───────────────────────┘
                                 │ HTTPS POST (every 1-5 min)
                                 ▼
                    ┌────────────────────────┐
                    │        Ned API         │
                    │    (Laravel + SQLite)  │
                    ├────────────────────────┤
                    │  • Receives metrics    │
                    │  • Stores time-series  │
                    │  • Checks thresholds   │
                    │  • Sends alerts        │
                    └───────────┬────────────┘
                                │
              ┌─────────────────┼─────────────────┐
              ▼                 ▼                 ▼
      ┌──────────────┐  ┌──────────────┐  ┌──────────────┐
      │  Dashboard   │  │    Alerts    │  │  Flutter App │
      │  (Livewire)  │  │ Email/Slack  │  │  (Optional)  │
      └──────────────┘  └──────────────┘  └──────────────┘
```

## MVP Scope (v0.1)

### Agent (Bash Script)
- [ ] System metrics collection (CPU, RAM, disk)
- [ ] Service status detection
- [ ] JSON payload generation
- [ ] HTTPS POST to API with auth token
- [ ] Cron-based execution (every 1-5 min configurable)
- [ ] One-line install script

### API (Laravel)
- [ ] Server registration endpoint
- [ ] Metrics ingestion endpoint
- [ ] Authentication (API tokens per server)
- [ ] SQLite storage for simplicity
- [ ] Threshold configuration per metric
- [ ] Alert triggers

### Dashboard (Livewire)
- [ ] Server list with status indicators
- [ ] Individual server detail view
- [ ] Real-time-ish updates (polling)
- [ ] Metric graphs (last 24h, 7d, 30d)
- [ ] Alert history

### Alerts (v0.1)
- [ ] Email notifications
- [ ] Configurable thresholds (CPU > 90%, disk > 85%, etc.)
- [ ] Alert cooldown (don't spam)

## Future Versions

### v0.2
- Slack/Discord/Telegram integrations
- HTTP endpoint monitoring
- SSL certificate expiry alerts
- fail2ban integration

### v0.3
- Flutter mobile app
- Push notifications
- Multi-user support
- Team/org features

### v0.4
- Laravel-specific monitoring (queues, failed jobs, horizon)
- Custom metric API
- Webhooks

## Tech Stack

| Component | Technology | Why |
|-----------|------------|-----|
| Agent | Bash | Zero dependencies, runs anywhere |
| API | Laravel 11 | Fast to build, familiar |
| Database | SQLite | Simple, no setup, good enough for 10s of servers |
| Dashboard | Livewire + Flux | Reactive without JS complexity |
| Hosting | Single VPS | Self-hosted, $5-10/mo |

## Installation

### API/Dashboard (Self-Hosted)

```bash
# Clone the repo
git clone https://github.com/paul-tastic/ned.git
cd ned

# Install dependencies
composer install
npm install && npm run build

# Configure
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Create admin user
php artisan ned:create-user

# Serve (or configure nginx/apache)
php artisan serve
```

### Agent (On Each Server)

```bash
# One-line install
curl -sSL https://getneddy.com/install.sh | bash -s -- --token YOUR_SERVER_TOKEN

# Or manually
wget https://your-ned-instance.com/agent.sh -O /usr/local/bin/ned-agent
chmod +x /usr/local/bin/ned-agent
echo "*/5 * * * * root /usr/local/bin/ned-agent" > /etc/cron.d/ned
```

## Configuration

### Agent Config (`/etc/ned/config`)

```bash
NED_API_URL="https://getneddy.com/api"
NED_TOKEN="your-server-token"
# Services are auto-detected - no configuration needed!
```

The agent automatically detects:
- All running services via systemd/OpenRC/SysVinit
- Linux distribution (debian, ubuntu, rhel, centos, fedora, arch, alpine, opensuse)
- CPU cores, memory, disk mounts

### Alert Thresholds (Dashboard)

| Metric | Default Warning | Default Critical |
|--------|-----------------|------------------|
| CPU Load (1m) | 80% | 95% |
| Memory Used | 80% | 95% |
| Disk Used | 80% | 90% |
| Service Down | - | Immediate |

## API Endpoints

### Public
- `GET /install.sh` - Agent install script
- `GET /agent.sh` - Agent script download

### Authenticated (Server Token)
- `POST /api/metrics` - Submit metrics payload
- `GET /api/config` - Get server configuration

### Authenticated (User Token)
- `GET /api/servers` - List servers
- `GET /api/servers/{id}` - Server details
- `GET /api/servers/{id}/metrics` - Historical metrics
- `PUT /api/servers/{id}/thresholds` - Update alert thresholds
- `GET /api/alerts` - Alert history

## Metrics Payload Example

```json
{
  "timestamp": "2025-01-03T12:00:00Z",
  "hostname": "prod-web-1",
  "distro": {
    "distro": "ubuntu",
    "version": "24.04",
    "name": "Ubuntu 24.04.1 LTS"
  },
  "system": {
    "uptime": 864000,
    "load": {"1m": 0.5, "5m": 0.7, "15m": 0.6},
    "cpu_cores": 4
  },
  "memory": {
    "mem": {"total": 8192, "used": 4096, "available": 4096},
    "swap": {"total": 2048, "used": 128}
  },
  "disks": [
    {"mount": "/", "total_mb": 50000, "used_mb": 25000, "percent": 50},
    {"mount": "/home", "total_mb": 100000, "used_mb": 60000, "percent": 60}
  ],
  "services": [
    {"name": "nginx", "status": "running"},
    {"name": "mysql", "status": "running"},
    {"name": "php8.3-fpm", "status": "running"},
    {"name": "sshd", "status": "running"},
    {"name": "cron", "status": "running"}
  ],
  "security": {
    "ssh_failed_24h": 150,
    "f2b_currently_banned": 3,
    "f2b_total_banned": 47,
    "last_attack": "Jan  3 10:42:15"
  }
}
```

Services are auto-detected from the running system - no hardcoded list needed.

## Contributing

Contributions welcome! Please read [CONTRIBUTING.md](CONTRIBUTING.md) first.

## License

MIT License - see [LICENSE](LICENSE) for details.

---

Built with frustration at Datadog pricing and a fondness for basement dwellers everywhere.

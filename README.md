# ned

> "Excuse me, I believe you have my... server metrics."

Meet ned - the basement-dwelling, bespectacled server watcher who keeps an eye on your infrastructure so you don't have to. Self-hosted monitoring for indie developers.

**N**ever-**E**nding **D**aemon. Or just ned. He's cool with either.

## Why ned?

- **Free & Open Source** - MIT licensed, self-host on your own infra
- **Dead Simple Setup** - One curl command to install the agent
- **Push-Based** - Agents push metrics to your dashboard (no firewall config needed)
- **Multi-Server** - Monitor all your boxes from one dashboard
- **No Auto-Updates** - You control when to update (dashboard shows when agent/dashboard versions mismatch)

## What ned Watches

### System Metrics
- CPU usage (1m, 5m, 15m load averages, normalized by core count)
- Memory usage (used, available, swap)
- Disk usage (per mount point)
- Network I/O (bytes in/out per interface)
- Uptime

### Services
- **Auto-detect running services** - No configuration needed, agent discovers what's running
- Supports systemd, OpenRC (Alpine), and SysVinit
- Baseline services (sshd, cron) always monitored if present
- Visual status indicators (green=running, red=stopped)

### Linux Distribution Support
- Automatic distro detection (Debian, Ubuntu, RHEL, CentOS, Rocky, Alma, Fedora, Arch, Alpine, openSUSE)
- Reports distro version and pretty name
- Agent adapts to init system (systemd, OpenRC, SysVinit)

### Security
- SSH failed login attempts (last 24 hours)
- fail2ban stats (currently banned, total banned)
- Last attack timestamp

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

## Current Status (v0.3.0)

### Implemented
- [x] System metrics collection (CPU, RAM, disk, network)
- [x] Service auto-detection (systemd, OpenRC, SysVinit)
- [x] Security metrics (SSH failures, fail2ban stats, banned IPs with geo lookup)
- [x] One-line agent install script
- [x] Server registration with unique tokens
- [x] Metrics ingestion API with token auth
- [x] SQLite storage
- [x] Dashboard with status indicators (online/warning/critical)
- [x] Server detail view with all metrics
- [x] Historical charts (CPU, memory, disk, network, security - 24h)
- [x] Agent version tracking with mismatch warnings

### Coming Soon

See the [Issues tab](https://github.com/paul-tastic/ned/issues) for the full roadmap and feature requests.

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

#### DNS Setup (Optional but Recommended)

If you're hosting ned on its own server (or want a clean URL), set up a subdomain:

1. Create an A record pointing to your server's IP:
   - `ned.yourdomain.com` → `YOUR_SERVER_IP`
2. Set up SSL (Let's Encrypt works great)
3. Configure your web server (nginx/apache) to serve ned on that domain

You can also install ned on the same server you're monitoring - just use `localhost` or the server's IP.

#### Installation Steps

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

First, get your server token from the ned dashboard:
1. Log in to your ned dashboard
2. Click "Add Server" and give it a name
3. Copy the token shown (it's only displayed once!)

The `--api` URL should be where you installed ned. Examples:
- `https://ned.yourdomain.com` - Your own domain
- `https://192.168.1.100` - Direct IP (with SSL)
- `http://192.168.1.100:8000` - Local dev server

#### Option 1: One-Line Install (Recommended)

```bash
curl -fsSL https://ned.yourdomain.com/install.sh | sudo bash -s -- \
  --token YOUR_SERVER_TOKEN \
  --api https://ned.yourdomain.com
```

This will:
- Download the agent to `/usr/local/bin/ned-agent`
- Create config at `/etc/ned/config`
- Set up a cron job (every 5 minutes by default)
- Send first metrics immediately

#### Option 2: Manual Installation

If you prefer to see what's happening (or can't use curl piped to bash):

```bash
# 1. Download the agent
sudo curl -fsSL https://raw.githubusercontent.com/paul-tastic/ned/master/agent/ned-agent.sh \
  -o /usr/local/bin/ned-agent
sudo chmod +x /usr/local/bin/ned-agent

# 2. Create config directory
sudo mkdir -p /etc/ned

# 3. Create config file (use YOUR ned dashboard URL)
sudo tee /etc/ned/config > /dev/null <<EOF
NED_API_URL="https://ned.yourdomain.com"
NED_TOKEN="YOUR_SERVER_TOKEN"
EOF
sudo chmod 600 /etc/ned/config

# 4. Test the agent
sudo /usr/local/bin/ned-agent

# 5. Set up cron (every 5 minutes)
echo "*/5 * * * * root /usr/local/bin/ned-agent >> /var/log/ned.log 2>&1" | sudo tee /etc/cron.d/ned
```

#### Updating the Agent

When a new version is available, the dashboard will show an "Update Available" notice. To update:

```bash
sudo curl -fsSL https://raw.githubusercontent.com/paul-tastic/ned/master/agent/ned-agent.sh \
  -o /usr/local/bin/ned-agent
```

Your config at `/etc/ned/config` is preserved.

## Configuration

### Agent Config (`/etc/ned/config`)

```bash
NED_API_URL="https://ned.yourdomain.com"  # Your ned dashboard URL
NED_TOKEN="your-server-token"              # From the ned dashboard
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

## Metrics Payload Example

```json
{
  "timestamp": "2025-01-03T12:00:00Z",
  "agent_version": "0.2.0",
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
  "network": [
    {"interface": "eth0", "rx_bytes": 123456789, "tx_bytes": 987654321}
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
    "f2b_total_banned": 47
  }
}
```

Services are auto-detected from the running system - no hardcoded list needed.

## Contributing

Contributions welcome! Please read [CONTRIBUTING.md](CONTRIBUTING.md) first.

## License

MIT License - see [LICENSE](LICENSE) for details.

---

*Lightweight, nimble, and quietly obsessive - just like its namesake. Built for indie devs who want to know what's happening on their servers without the enterprise bloat.*

# Security Policy

Ned is designed with security as a first-class concern. This document outlines the security model and best practices.

## Authentication Model

Ned uses a layered authentication approach with different mechanisms for different use cases:

### 1. Agent → API Authentication (Bearer Token)

Agents authenticate using a **server token** - a 64-character random string.

**How it works:**
- When you register a server in the dashboard, Ned generates a random token
- The **plain token is shown once** and must be saved to the agent config
- The **hashed token** (SHA-256) is stored in the database
- Agents send the plain token as a Bearer token: `Authorization: Bearer <token>`
- The API hashes the incoming token and looks up the matching server

**Security properties:**
- Tokens are never stored in plain text on the server
- Compromised database doesn't expose usable tokens
- Each server has a unique token (no shared secrets)
- Tokens can be regenerated per-server without affecting others

**Agent config example:**
```bash
NED_API_URL="http://your-server:8080"
NED_TOKEN="your-64-character-token"
```

### 2. User → Dashboard Authentication (Session-based)

The web dashboard uses Laravel's session-based authentication via Breeze.

**Features:**
- Email/password login
- CSRF protection on all forms
- Secure, HTTP-only session cookies
- Optional "remember me" functionality

**Important:** Registration is **disabled by default**. The first user is created during initial setup via `php artisan ned:install`.

### 3. User → API Authentication (Sanctum Tokens)

For mobile apps or external API access, users can generate personal access tokens via Laravel Sanctum.

**Features:**
- Tokens are scoped (read-only, full access, etc.)
- Tokens can be revoked individually
- Tokens have optional expiration

**Usage:**
```bash
# API request with Sanctum token
curl -H "Authorization: Bearer <sanctum-token>" \
     http://your-server:8080/api/user/servers
```

## Access Control

### Server Ownership
- Each server belongs to one user
- Users can only view/manage their own servers
- No multi-tenancy sharing (yet)

### Sensitive Data Encryption
- Alert channel configs (webhook URLs, API keys) are **encrypted at rest** using Laravel's encryption
- Server tokens are hashed with SHA-256
- Database credentials are in `.env`, not in code

## Network Security

### Running Without a Domain

Ned is designed to work via IP address without requiring a domain name:

```
http://193.43.134.164:8080
```

**Recommendations for IP-based deployments:**

1. **Use a non-standard port** - Avoid 80/443 to reduce drive-by attacks
2. **Firewall the dashboard port** - Only allow access from trusted IPs:
   ```bash
   ufw allow from YOUR_IP to any port 8080
   ```
3. **Use a VPN** - Access the dashboard via WireGuard/Tailscale
4. **Consider reverse proxy** - Even without a domain, nginx can add TLS with self-signed certs

### With a Domain (Recommended)

If you have a domain:

1. Use Let's Encrypt for free TLS
2. Configure nginx/Apache as reverse proxy
3. Redirect HTTP → HTTPS
4. Enable HSTS headers

## Reporting a Vulnerability

If you discover a security vulnerability in Ned, please report it responsibly.

### How to Report

- **Email:** security@getneddy.com (or open a GitHub security advisory)
- **Do not** open a public issue for security vulnerabilities

### What to Include

- Description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if any)

### Response Timeline

- **Acknowledgment:** Within 48 hours
- **Assessment:** Within 1 week
- **Fix timeline:** Depends on severity (critical: ASAP, high: 1-2 weeks)

## Security Best Practices

### Agent Security

1. **Protect the config file:**
   ```bash
   chmod 600 /etc/ned/config
   ```
2. **Run as a dedicated user** (not root) when possible
3. **Firewall outbound** to only allow connections to your Ned server
4. **Rotate tokens** periodically via the dashboard

### API Security

1. **Use HTTPS in production** - Tokens in headers are visible on HTTP
2. **Rate limiting** is enabled by default (60 requests/minute per IP)
3. **Input validation** on all endpoints
4. **No sensitive data in URLs** - Tokens go in headers, not query strings

### Dashboard Security

1. **Strong passwords** - Minimum 8 characters recommended
2. **Secure your session** - Log out when done on shared machines
3. **Check active sessions** - Revoke unknown sessions in settings
4. **Firewall access** - Don't expose dashboard to the entire internet if possible

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |
| < 1.0   | :x: (pre-release)  |

## Security Headers

The dashboard includes these security headers by default:

- `X-Frame-Options: DENY` - Prevents clickjacking
- `X-Content-Type-Options: nosniff` - Prevents MIME sniffing
- `X-XSS-Protection: 1; mode=block` - Legacy XSS protection
- `Referrer-Policy: strict-origin-when-cross-origin`

## Database Security

Ned uses SQLite by default for simplicity. For production:

1. **Protect the database file:**
   ```bash
   chmod 600 database/database.sqlite
   ```
2. **Backup regularly** - The file can be copied while Ned is running
3. **Consider MySQL/PostgreSQL** for multi-user deployments with proper access controls

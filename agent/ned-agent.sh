#!/bin/bash
#
# Ned Agent - Server monitoring agent
# https://github.com/yourname/ned
#
# Collects system metrics and POSTs them to your Ned dashboard.
# "Excuse me, I believe you have my... server metrics."
#
# Designed to run via cron every 1-5 minutes.
#

set -e

# Load config
CONFIG_FILE="${NED_CONFIG:-/etc/ned/config}"
if [ -f "$CONFIG_FILE" ]; then
    source "$CONFIG_FILE"
fi

# Required config
NED_API_URL="${NED_API_URL:-}"
NED_TOKEN="${NED_TOKEN:-}"

if [ -z "$NED_API_URL" ] || [ -z "$NED_TOKEN" ]; then
    echo "Error: NED_API_URL and NED_TOKEN must be set" >&2
    echo "Set them in $CONFIG_FILE or as environment variables" >&2
    exit 1
fi

# Optional config
NED_HOSTNAME="${NED_HOSTNAME:-$(hostname -f 2>/dev/null || hostname)}"

# -----------------------------------------------------------------------------
# Helper Functions
# -----------------------------------------------------------------------------

json_escape() {
    printf '%s' "$1" | python3 -c 'import json,sys; print(json.dumps(sys.stdin.read()))' 2>/dev/null || echo "\"$1\""
}

# -----------------------------------------------------------------------------
# System Metrics
# -----------------------------------------------------------------------------

get_uptime() {
    if [ -f /proc/uptime ]; then
        awk '{print int($1)}' /proc/uptime
    else
        echo "0"
    fi
}

get_load_average() {
    if [ -f /proc/loadavg ]; then
        awk '{printf "{\"1m\": %s, \"5m\": %s, \"15m\": %s}", $1, $2, $3}' /proc/loadavg
    else
        echo '{"1m": 0, "5m": 0, "15m": 0}'
    fi
}

get_cpu_cores() {
    nproc 2>/dev/null || grep -c ^processor /proc/cpuinfo 2>/dev/null || echo "1"
}

get_memory() {
    if command -v free &> /dev/null; then
        free -m | awk '/^Mem:/ {
            printf "{\"total\": %d, \"used\": %d, \"available\": %d}", $2, $3, $7
        }'
        echo -n ", \"swap\": "
        free -m | awk '/^Swap:/ {
            printf "{\"total\": %d, \"used\": %d}", $2, $3
        }'
    else
        echo '"total": 0, "used": 0, "available": 0}, "swap": {"total": 0, "used": 0}'
    fi
}

get_disks() {
    echo "["
    df -P -x tmpfs -x devtmpfs -x squashfs 2>/dev/null | awk 'NR>1 {
        gsub(/%/, "", $5)
        if (NR > 2) printf ","
        printf "{\"mount\": \"%s\", \"total_mb\": %d, \"used_mb\": %d, \"percent\": %d}", $6, $2/1024, $3/1024, $5
    }'
    echo "]"
}

# -----------------------------------------------------------------------------
# Service Detection
# -----------------------------------------------------------------------------

check_service() {
    local service="$1"
    if command -v systemctl &> /dev/null; then
        if systemctl is-active --quiet "$service" 2>/dev/null; then
            echo "running"
        elif systemctl is-enabled --quiet "$service" 2>/dev/null; then
            echo "stopped"
        else
            echo "not_installed"
        fi
    elif command -v service &> /dev/null; then
        if service "$service" status &>/dev/null; then
            echo "running"
        else
            echo "stopped"
        fi
    else
        echo "unknown"
    fi
}

get_services() {
    # Common services to check
    local services=("nginx" "apache2" "httpd" "mysql" "mariadb" "postgresql" "redis" "php-fpm" "php8.4-fpm" "php8.3-fpm" "php8.2-fpm" "lsws" "docker" "fail2ban" "sshd")

    echo "["
    local first=true
    for svc in "${services[@]}"; do
        status=$(check_service "$svc")
        if [ "$status" != "not_installed" ]; then
            if [ "$first" = true ]; then
                first=false
            else
                echo -n ","
            fi
            printf '{"name": "%s", "status": "%s"}' "$svc" "$status"
        fi
    done
    echo "]"
}

# -----------------------------------------------------------------------------
# Security Metrics
# -----------------------------------------------------------------------------

get_security() {
    local ssh_failed=0
    local f2b_banned=0
    local f2b_total=0

    # SSH failed attempts (last 24h)
    if command -v journalctl &> /dev/null; then
        ssh_failed=$(journalctl -u sshd --since "24 hours ago" 2>/dev/null | grep -c "Failed password\|Invalid user" || echo "0")
    elif [ -f /var/log/auth.log ]; then
        ssh_failed=$(grep -c "Failed password\|Invalid user" /var/log/auth.log 2>/dev/null || echo "0")
    elif [ -f /var/log/secure ]; then
        ssh_failed=$(grep -c "Failed password\|Invalid user" /var/log/secure 2>/dev/null || echo "0")
    fi

    # fail2ban stats
    if command -v fail2ban-client &> /dev/null; then
        f2b_banned=$(fail2ban-client status sshd 2>/dev/null | grep "Currently banned" | awk '{print $NF}' || echo "0")
        f2b_total=$(fail2ban-client status sshd 2>/dev/null | grep "Total banned" | awk '{print $NF}' || echo "0")
    fi

    # Sanitize values
    ssh_failed="${ssh_failed:-0}"
    f2b_banned="${f2b_banned:-0}"
    f2b_total="${f2b_total:-0}"

    printf '{"ssh_failed_24h": %d, "f2b_currently_banned": %d, "f2b_total_banned": %d}' \
        "${ssh_failed//[^0-9]/}" "${f2b_banned//[^0-9]/}" "${f2b_total//[^0-9]/}"
}

# -----------------------------------------------------------------------------
# Build and Send Payload
# -----------------------------------------------------------------------------

build_payload() {
    local timestamp=$(date -u +"%Y-%m-%dT%H:%M:%SZ")

    cat <<EOF
{
    "timestamp": "$timestamp",
    "hostname": "$NED_HOSTNAME",
    "system": {
        "uptime": $(get_uptime),
        "load": $(get_load_average),
        "cpu_cores": $(get_cpu_cores)
    },
    "memory": {$(get_memory)},
    "disks": $(get_disks),
    "services": $(get_services),
    "security": $(get_security)
}
EOF
}

send_metrics() {
    local payload="$1"
    local response

    response=$(curl -s -w "\n%{http_code}" \
        -X POST \
        -H "Content-Type: application/json" \
        -H "Authorization: Bearer $NED_TOKEN" \
        -d "$payload" \
        "$NED_API_URL/metrics" 2>&1)

    local http_code=$(echo "$response" | tail -n1)
    local body=$(echo "$response" | sed '$d')

    if [ "$http_code" = "200" ] || [ "$http_code" = "201" ]; then
        echo "Metrics sent successfully"
        return 0
    else
        echo "Failed to send metrics. HTTP $http_code: $body" >&2
        return 1
    fi
}

# -----------------------------------------------------------------------------
# Main
# -----------------------------------------------------------------------------

main() {
    local payload=$(build_payload)

    # Debug mode: print payload instead of sending
    if [ "${NED_DEBUG:-0}" = "1" ]; then
        echo "$payload" | python3 -m json.tool 2>/dev/null || echo "$payload"
        exit 0
    fi

    send_metrics "$payload"
}

main "$@"

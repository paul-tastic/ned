#!/bin/bash
#
# Ned Agent - Server monitoring agent
# https://github.com/paul-tastic/ned
#
# Collects system metrics and POSTs them to your Ned dashboard.
# "Excuse me, I believe you have my... server metrics."
#
# Designed to run via cron every 1-5 minutes.
#

set -e

# Agent version
NED_AGENT_VERSION="0.1.0"

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
        local mem=$(free -m | awk '/^Mem:/ { printf "{\"total\": %d, \"used\": %d, \"available\": %d}", $2, $3, $7 }')
        local swap=$(free -m | awk '/^Swap:/ { printf "{\"total\": %d, \"used\": %d}", $2, $3 }')
        echo "{\"mem\": ${mem}, \"swap\": ${swap}}"
    else
        echo '{"mem": {"total": 0, "used": 0, "available": 0}, "swap": {"total": 0, "used": 0}}'
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
# Linux Distribution Detection
# -----------------------------------------------------------------------------

get_distro() {
    # Returns: debian, ubuntu, rhel, centos, fedora, arch, alpine, opensuse, or unknown
    if [ -f /etc/os-release ]; then
        . /etc/os-release
        case "$ID" in
            debian) echo "debian" ;;
            ubuntu) echo "ubuntu" ;;
            rhel|redhat) echo "rhel" ;;
            centos|rocky|almalinux) echo "centos" ;;
            fedora) echo "fedora" ;;
            arch|manjaro) echo "arch" ;;
            alpine) echo "alpine" ;;
            opensuse*|sles) echo "opensuse" ;;
            *) echo "$ID" ;;
        esac
    elif [ -f /etc/debian_version ]; then
        echo "debian"
    elif [ -f /etc/redhat-release ]; then
        echo "rhel"
    else
        echo "unknown"
    fi
}

get_distro_info() {
    local distro=$(get_distro)
    local version=""
    local name=""

    if [ -f /etc/os-release ]; then
        . /etc/os-release
        version="${VERSION_ID:-unknown}"
        name="${PRETTY_NAME:-$ID}"
    fi

    printf '{"distro": "%s", "version": "%s", "name": "%s"}' "$distro" "$version" "$name"
}

# -----------------------------------------------------------------------------
# Service Detection
# -----------------------------------------------------------------------------

# Baseline services we always want to monitor if they exist
BASELINE_SERVICES="sshd ssh cron crond"

check_service_status() {
    local service="$1"
    if command -v systemctl &> /dev/null; then
        if systemctl is-active --quiet "$service" 2>/dev/null; then
            echo "running"
        else
            echo "stopped"
        fi
    elif command -v service &> /dev/null; then
        if service "$service" status &>/dev/null; then
            echo "running"
        else
            echo "stopped"
        fi
    elif command -v rc-service &> /dev/null; then
        # Alpine Linux
        if rc-service "$service" status &>/dev/null; then
            echo "running"
        else
            echo "stopped"
        fi
    else
        echo "unknown"
    fi
}

service_exists() {
    local service="$1"
    if command -v systemctl &> /dev/null; then
        systemctl list-unit-files "${service}.service" 2>/dev/null | grep -q "$service"
    elif [ -f "/etc/init.d/$service" ]; then
        return 0
    else
        return 1
    fi
}

get_running_services() {
    # Auto-detect running services from systemd or init
    local services=()

    if command -v systemctl &> /dev/null; then
        # Get all running services from systemd
        # Filter to common daemon services, excluding system internals
        while IFS= read -r line; do
            local unit=$(echo "$line" | awk '{print $1}' | sed 's/\.service$//')
            # Skip internal systemd services
            case "$unit" in
                systemd-*|dbus*|polkit*|user@*|session-*|getty*|*.slice|*.target|*.mount|*.socket|-.*)
                    continue
                    ;;
            esac
            services+=("$unit")
        done < <(systemctl list-units --type=service --state=running --no-legend 2>/dev/null | head -50)
    elif command -v rc-status &> /dev/null; then
        # Alpine Linux with OpenRC
        while IFS= read -r line; do
            local svc=$(echo "$line" | awk '{print $1}')
            [ -n "$svc" ] && services+=("$svc")
        done < <(rc-status -s 2>/dev/null | grep started | head -50)
    elif command -v service &> /dev/null; then
        # SysVinit fallback - check init.d scripts
        for script in /etc/init.d/*; do
            [ -x "$script" ] || continue
            local svc=$(basename "$script")
            case "$svc" in
                README|skeleton|rc*|functions|halt|killall|single|*.dpkg-*)
                    continue
                    ;;
            esac
            if service "$svc" status &>/dev/null 2>&1; then
                services+=("$svc")
            fi
        done
    fi

    # Return unique services
    printf '%s\n' "${services[@]}" | sort -u
}

get_services() {
    echo "["
    local first=true
    local seen=()

    # First, auto-detect all running services
    while IFS= read -r svc; do
        [ -z "$svc" ] && continue

        # Skip if already seen
        for s in "${seen[@]}"; do
            [ "$s" = "$svc" ] && continue 2
        done
        seen+=("$svc")

        local status=$(check_service_status "$svc")

        if [ "$first" = true ]; then
            first=false
        else
            echo -n ","
        fi
        printf '{"name": "%s", "status": "%s"}' "$svc" "$status"
    done < <(get_running_services)

    # Also check baseline services that might be stopped
    for svc in $BASELINE_SERVICES; do
        # Skip if already seen
        for s in "${seen[@]}"; do
            [ "$s" = "$svc" ] && continue 2
        done

        # Only add if the service exists on this system
        if service_exists "$svc"; then
            seen+=("$svc")
            local status=$(check_service_status "$svc")

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
    local last_attack=""

    # SSH failed attempts (last 24h) and last attack timestamp
    if command -v journalctl &> /dev/null; then
        ssh_failed=$(journalctl -u sshd --since "24 hours ago" 2>/dev/null | grep -c "Failed password\|Invalid user" || echo "0")
        # Get timestamp of most recent failed attempt
        last_attack=$(journalctl -u sshd -n 100 2>/dev/null | grep "Failed password\|Invalid user" | tail -1 | awk '{print $1, $2, $3}' || echo "")
    elif [ -f /var/log/auth.log ]; then
        ssh_failed=$(grep -c "Failed password\|Invalid user" /var/log/auth.log 2>/dev/null || echo "0")
        last_attack=$(grep "Failed password\|Invalid user" /var/log/auth.log 2>/dev/null | tail -1 | awk '{print $1, $2, $3}' || echo "")
    elif [ -f /var/log/secure ]; then
        ssh_failed=$(grep -c "Failed password\|Invalid user" /var/log/secure 2>/dev/null || echo "0")
        last_attack=$(grep "Failed password\|Invalid user" /var/log/secure 2>/dev/null | tail -1 | awk '{print $1, $2, $3}' || echo "")
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

    # Build JSON with optional last_attack field
    if [ -n "$last_attack" ]; then
        printf '{"ssh_failed_24h": %d, "f2b_currently_banned": %d, "f2b_total_banned": %d, "last_attack": "%s"}' \
            "${ssh_failed//[^0-9]/}" "${f2b_banned//[^0-9]/}" "${f2b_total//[^0-9]/}" "$last_attack"
    else
        printf '{"ssh_failed_24h": %d, "f2b_currently_banned": %d, "f2b_total_banned": %d, "last_attack": null}' \
            "${ssh_failed//[^0-9]/}" "${f2b_banned//[^0-9]/}" "${f2b_total//[^0-9]/}"
    fi
}

# -----------------------------------------------------------------------------
# Build and Send Payload
# -----------------------------------------------------------------------------

build_payload() {
    local timestamp=$(date -u +"%Y-%m-%dT%H:%M:%SZ")

    cat <<EOF
{
    "timestamp": "$timestamp",
    "agent_version": "$NED_AGENT_VERSION",
    "hostname": "$NED_HOSTNAME",
    "distro": $(get_distro_info),
    "system": {
        "uptime": $(get_uptime),
        "load": $(get_load_average),
        "cpu_cores": $(get_cpu_cores)
    },
    "memory": $(get_memory),
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
        "${NED_API_URL}/api/metrics" 2>&1)

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

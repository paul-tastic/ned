#!/bin/bash
#
# Ned Agent Installer
# Usage: curl -sSL https://your-ned.com/install.sh | bash -s -- --token YOUR_TOKEN --api https://your-ned.com/api
#

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Defaults
INSTALL_DIR="/usr/local/bin"
CONFIG_DIR="/etc/ned"
AGENT_URL="https://raw.githubusercontent.com/paul-tastic/ned/master/agent/ned-agent.sh"
CRON_INTERVAL="5"  # minutes

# Parse arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --token)
            TOKEN="$2"
            shift 2
            ;;
        --api)
            API_URL="$2"
            shift 2
            ;;
        --interval)
            CRON_INTERVAL="$2"
            shift 2
            ;;
        --help)
            echo "Ned Agent Installer"
            echo ""
            echo "Usage: install.sh --token YOUR_TOKEN --api YOUR_API_URL"
            echo ""
            echo "Options:"
            echo "  --token     Server authentication token (required)"
            echo "  --api       Ned API URL (required)"
            echo "  --interval  Cron interval in minutes (default: 5)"
            echo "  --help      Show this help message"
            exit 0
            ;;
        *)
            echo "Unknown option: $1"
            exit 1
            ;;
    esac
done

# Validate required args
if [ -z "$TOKEN" ] || [ -z "$API_URL" ]; then
    echo -e "${RED}Error: --token and --api are required${NC}"
    echo "Usage: install.sh --token YOUR_TOKEN --api YOUR_API_URL"
    exit 1
fi

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo -e "${YELLOW}Warning: Not running as root. Will use sudo.${NC}"
    SUDO="sudo"
else
    SUDO=""
fi

echo -e "${GREEN}Installing Ned Agent...${NC}"
echo "\"Excuse me, I believe you have my... server metrics.\""
echo ""

# Create config directory
echo "Creating config directory..."
$SUDO mkdir -p "$CONFIG_DIR"

# Download agent script
echo "Downloading agent..."
$SUDO curl -sSL "$AGENT_URL" -o "$INSTALL_DIR/ned-agent"
$SUDO chmod +x "$INSTALL_DIR/ned-agent"

# Create config file
echo "Creating config..."
$SUDO tee "$CONFIG_DIR/config" > /dev/null <<EOF
# Ned Agent Configuration
NED_API_URL="$API_URL"
NED_TOKEN="$TOKEN"
EOF
$SUDO chmod 600 "$CONFIG_DIR/config"

# Set up cron job
echo "Setting up cron job (every $CRON_INTERVAL minutes)..."
CRON_LINE="*/$CRON_INTERVAL * * * * root $INSTALL_DIR/ned-agent >> /var/log/ned.log 2>&1"
echo "$CRON_LINE" | $SUDO tee /etc/cron.d/ned > /dev/null
$SUDO chmod 644 /etc/cron.d/ned

# Create log file
$SUDO touch /var/log/ned.log
$SUDO chmod 644 /var/log/ned.log

# Test the agent
echo "Testing agent..."
if $SUDO NED_DEBUG=1 "$INSTALL_DIR/ned-agent" > /dev/null 2>&1; then
    echo -e "${GREEN}Agent test passed!${NC}"
else
    echo -e "${YELLOW}Agent test produced warnings (this may be normal)${NC}"
fi

# Send first metrics (retry up to 3 times with backoff — token may not
# be fully committed to the server DB immediately after registration)
echo "Sending first metrics..."
METRICS_OK=false
for ATTEMPT in 1 2 3; do
    if $SUDO "$INSTALL_DIR/ned-agent" 2>/dev/null; then
        echo -e "${GREEN}First metrics sent successfully!${NC}"
        METRICS_OK=true
        break
    fi
    if [ "$ATTEMPT" -lt 3 ]; then
        echo -e "${YELLOW}Attempt $ATTEMPT failed, retrying in ${ATTEMPT}s...${NC}"
        sleep "$ATTEMPT"
    fi
done

if [ "$METRICS_OK" = false ]; then
    echo -e "${YELLOW}Initial metrics send failed — the cron job will retry in $CRON_INTERVAL minutes.${NC}"
    echo -e "${YELLOW}If metrics never appear, check your token and API URL.${NC}"
fi

echo ""
echo -e "${GREEN}Ned is now watching your server!${NC}"
echo ""
echo "Agent location: $INSTALL_DIR/ned-agent"
echo "Config file:    $CONFIG_DIR/config"
echo "Log file:       /var/log/ned.log"
echo "Cron job:       /etc/cron.d/ned (every $CRON_INTERVAL minutes)"
echo ""
echo "To test manually:  ned-agent"
echo "To view logs:      tail -f /var/log/ned.log"
echo "To uninstall:      rm $INSTALL_DIR/ned-agent $CONFIG_DIR/config /etc/cron.d/ned"

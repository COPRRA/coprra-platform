#!/bin/bash
# Mission 4: DKIM Configuration Script
# This script configures DKIM for coprra.com

set -e

DOMAIN="coprra.com"
SELECTOR="default"
PROJECT_PATH="/home/u990109832/domains/coprra.com/public_html"

echo "🔐 Mission 4: DKIM Configuration for $DOMAIN"
echo "============================================="

cd "$PROJECT_PATH"

# Check if OpenDKIM is installed
if ! command -v opendkim-genkey &> /dev/null; then
    echo "📦 Installing OpenDKIM tools..."
    sudo apt-get update
    sudo apt-get install -y opendkim-tools
fi

# Create DKIM directory if it doesn't exist
DKIM_DIR="/etc/opendkim/keys/$DOMAIN"
sudo mkdir -p "$DKIM_DIR"
cd "$DKIM_DIR"

# Generate DKIM key pair
echo "🔑 Generating DKIM key pair..."
sudo opendkim-genkey -t -s "$SELECTOR" -d "$DOMAIN"

# Set proper permissions
sudo chown opendkim:opendkim "$SELECTOR"*
sudo chmod 600 "$SELECTOR".private
sudo chmod 644 "$SELECTOR".txt

# Display public key for DNS
echo ""
echo "=========================================="
echo "📋 DKIM DNS Record to Add:"
echo "=========================================="
echo "Type: TXT"
echo "Name: ${SELECTOR}._domainkey.${DOMAIN}"
echo "Value:"
sudo cat "$SELECTOR".txt | grep -v "^#" | tr -d '\n' | sed 's/" "/"\n  "/g'
echo ""
echo "=========================================="

# Check mail server configuration
echo ""
echo "📧 Checking mail server configuration..."

if command -v exim &> /dev/null; then
    echo "Exim detected. DKIM configuration needed in /etc/exim4/exim4.conf"
elif command -v postfix &> /dev/null; then
    echo "Postfix detected. DKIM configuration needed in /etc/postfix/main.cf"
else
    echo "⚠️  Mail server type not detected. Manual configuration required."
fi

echo ""
echo "✅ DKIM key pair generated successfully!"
echo "📝 Next steps:"
echo "   1. Add the DNS TXT record shown above"
echo "   2. Configure your mail server to use the private key"
echo "   3. Test DKIM signing with: opendkim-testkey -d $DOMAIN -s $SELECTOR"


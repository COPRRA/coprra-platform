#!/bin/bash
# Generate DKIM keys locally using OpenSSL

DOMAIN="coprra.com"
SELECTOR="default"
OUTPUT_DIR="./dkim_keys"

mkdir -p "$OUTPUT_DIR/$DOMAIN"
cd "$OUTPUT_DIR/$DOMAIN"

echo "🔑 Generating DKIM key pair for $DOMAIN..."
echo "=========================================="

# Generate private key
openssl genrsa -out "${SELECTOR}.private" 2048

# Generate public key
openssl rsa -in "${SELECTOR}.private" -pubout -out "${SELECTOR}.public"

# Create DNS TXT record
PUBLIC_KEY=$(grep -v "BEGIN\|END" "${SELECTOR}.public" | tr -d '\n')

echo ""
echo "=========================================="
echo "📋 DKIM DNS Record to Add:"
echo "=========================================="
echo "Type: TXT"
echo "Name: ${SELECTOR}._domainkey.${DOMAIN}"
echo "TTL: 3600"
echo ""
echo "Value (format for DNS):"
echo "v=DKIM1; k=rsa; p=${PUBLIC_KEY}"
echo ""
echo "Full DNS Record:"
echo "${SELECTOR}._domainkey.${DOMAIN} IN TXT \"v=DKIM1; k=rsa; p=${PUBLIC_KEY}\""
echo ""
echo "=========================================="
echo "✅ Keys generated:"
echo "Private key: $OUTPUT_DIR/$DOMAIN/${SELECTOR}.private"
echo "Public key: $OUTPUT_DIR/$DOMAIN/${SELECTOR}.public"
echo ""
echo "⚠️  Next Steps:"
echo "1. Upload private key to server: /etc/opendkim/keys/$DOMAIN/${SELECTOR}.private"
echo "2. Add DNS TXT record shown above"
echo "3. Configure Exim to use DKIM"
echo "4. Restart Exim service"


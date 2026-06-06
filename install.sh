#!/bin/bash

# ============================================
# Devil AI Gateway — One-Click Installer
# Developer: David | Devil One Pvt Ltd
# ============================================

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}"
echo "  ____             _ _   ___     _    ___ "
echo " |  _ \  _____   _(_) | / _ \   / \  |_ _|"
echo " | | | |/ _ \ \ / / | || | | | / _ \  | | "
echo " | |_| |  __/\ V /| | || |_| |/ ___ \ | | "
echo " |____/ \___| \_/ |_|_| \___//_/   \_\___|"
echo ""
echo -e "${NC}  😈 Devil AI Gateway Installer v1.0"
echo -e "  Developer: David | Devil One Pvt Ltd"
echo ""

# Check PHP
if ! command -v php &> /dev/null; then
    echo -e "${RED}❌ PHP not found. Please install PHP 8.0+${NC}"
    exit 1
fi
echo -e "${GREEN}✅ PHP found: $(php -v | head -1)${NC}"

# Check MySQL
if ! command -v mysql &> /dev/null; then
    echo -e "${YELLOW}⚠️  MySQL not found. You will need to set up DB manually.${NC}"
else
    echo -e "${GREEN}✅ MySQL found${NC}"
fi

# Copy .env
if [ ! -f .env ]; then
    cp .env.example .env
    echo -e "${GREEN}✅ .env created from .env.example${NC}"
fi

# Prompt for config
echo ""
echo -e "${YELLOW}📝 Configure your gateway:${NC}"
read -p "  Ollama URL [https://aiapi.devilpvt.in]: " OLLAMA_URL
OLLAMA_URL=${OLLAMA_URL:-https://aiapi.devilpvt.in}

read -p "  DB Host [localhost]: " DB_HOST
DB_HOST=${DB_HOST:-localhost}

read -p "  DB Name [devil_ai_gateway]: " DB_NAME
DB_NAME=${DB_NAME:-devil_ai_gateway}

read -p "  DB User [root]: " DB_USER
DB_USER=${DB_USER:-root}

read -sp "  DB Password: " DB_PASS
echo ""

read -p "  Admin Password [admin123]: " ADMIN_PASS
ADMIN_PASS=${ADMIN_PASS:-admin123}

read -p "  Your Gateway Domain [https://api.devilone.in]: " GATEWAY_DOMAIN
GATEWAY_DOMAIN=${GATEWAY_DOMAIN:-https://api.devilone.in}

# Write .env
cat > .env << EOF
OLLAMA_URL=${OLLAMA_URL}
DB_HOST=${DB_HOST}
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASS=${DB_PASS}
ADMIN_PASSWORD=${ADMIN_PASS}
GATEWAY_DOMAIN=${GATEWAY_DOMAIN}
EOF

echo -e "${GREEN}✅ .env configured${NC}"

# Create DB and tables
echo ""
echo -e "${BLUE}🗄️  Setting up database...${NC}"

mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" << EOSQL 2>/dev/null
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\`;
USE \`${DB_NAME}\`;

CREATE TABLE IF NOT EXISTS api_keys (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  email VARCHAR(255),
  api_key VARCHAR(64) UNIQUE NOT NULL,
  requests_today INT DEFAULT 0,
  total_requests INT DEFAULT 0,
  limit_per_day INT DEFAULT 100,
  is_active TINYINT DEFAULT 1,
  last_reset DATE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS request_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  api_key VARCHAR(64),
  model VARCHAR(50),
  prompt TEXT,
  tools_used VARCHAR(255),
  response_time_ms INT,
  status VARCHAR(20),
  ip_address VARCHAR(45),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO api_keys (name, email, api_key, limit_per_day)
VALUES ('Admin Test Key', 'admin@devilone.in', 'dk_live_admin_test_key_001', 99999);
EOSQL

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Database and tables created${NC}"
else
    echo -e "${YELLOW}⚠️  DB setup skipped (check credentials or run setup/install.php via browser)${NC}"
fi

# Set permissions
chmod 755 api/ docs/ admin/ setup/ 2>/dev/null
echo -e "${GREEN}✅ Permissions set${NC}"

echo ""
echo -e "${GREEN}🎉 Installation complete!${NC}"
echo ""
echo -e "  📡 API Endpoint:    ${GATEWAY_DOMAIN}/api/v1/chat"
echo -e "  📚 Documentation:  ${GATEWAY_DOMAIN}/docs/"
echo -e "  🔑 Admin Panel:    ${GATEWAY_DOMAIN}/admin/"
echo -e "  🧪 Test Key:       dk_live_admin_test_key_001"
echo ""
echo -e "${YELLOW}  Deploy this folder to your PHP hosting root.${NC}"
echo ""

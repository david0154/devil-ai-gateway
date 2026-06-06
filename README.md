# 😈 Devil AI Gateway

> PHP-based API Gateway for Devil AI — with API key authentication, tool calling (weather, news, GitHub, web search, Reddit), rate limiting, and a full developer documentation portal.

**Developer:** David | **Company:** Devil One Pvt Ltd | **Website:** [devilone.in](https://devilone.in)

---

## 🚀 One-Click Install

```bash
bash <(curl -s https://raw.githubusercontent.com/david0154/devil-ai-gateway/main/install.sh)
```

Or manually:
```bash
git clone https://github.com/david0154/devil-ai-gateway.git
cd devil-ai-gateway
bash install.sh
```

---

## 📁 Project Structure

```
devil-ai-gateway/
├── install.sh           # One-click installer
├── .env.example         # Environment config template
├── api/
│   ├── index.php        # Main gateway router
│   ├── chat.php         # /v1/chat endpoint
│   ├── models.php       # /v1/models endpoint
│   ├── keys.php         # API key management
│   └── tools/
│       ├── weather.php
│       ├── news.php
│       ├── github.php
│       ├── search.php
│       ├── reddit.php
│       └── datetime.php
├── admin/
│   ├── index.php        # Admin dashboard
│   ├── keys.php         # Key management UI
│   └── logs.php         # Request logs viewer
├── docs/
│   └── index.php        # Developer documentation portal
├── setup/
│   └── install.php      # Web-based DB installer
└── .htaccess
```

---

## ⚡ API Usage

### Endpoint
```
POST https://your-domain.com/api/v1/chat
```

### Headers
```
Content-Type: application/json
X-API-Key: dk_live_your_key_here
```

### Request
```json
{
  "model": "devil-ai",
  "prompt": "What is the weather in Kolkata?",
  "tools": ["weather", "datetime"]
}
```

### Response
```json
{
  "response": "The current weather in Kolkata is 34°C, Partly Cloudy.",
  "model": "devil-ai",
  "tools_used": ["weather", "datetime"],
  "usage": {
    "requests_today": 5,
    "limit_per_day": 100
  }
}
```

---

## 🛠️ Available Tools

| Tool | Trigger Keywords | Source |
|------|-----------------|--------|
| `weather` | weather, temperature, rain, forecast | wttr.in |
| `news` | news, latest, breaking, today | Google News RSS |
| `github` | github, repo, code, library | GitHub Search API |
| `web_search` | search, find, who, what, how | DuckDuckGo |
| `reddit` | reddit, community, discussion | Reddit JSON API |
| `datetime` | date, time, day, year, now | PHP date() |

---

## 🔐 Environment Variables

Copy `.env.example` to `.env` and fill in:

```env
OLLAMA_URL=https://aiapi.devilpvt.in
DB_HOST=localhost
DB_NAME=devil_ai_gateway
DB_USER=root
DB_PASS=your_password
ADMIN_PASSWORD=admin123
GATEWAY_DOMAIN=https://api.devilone.in
```

---

## 📄 License

MIT License — © 2026 David, Devil One Pvt Ltd

# Caddy Stack

Haupt-Reverse-Proxy auf Basis von **Caddy Security** mit automatischen Let's Encrypt Zertifikaten, Auth-Portal, CaddyManager Web UI und PHP-Webseite.

## Dienste

| Service | Image | Port | Beschreibung |
|---------|-------|------|-------------|
| `caddy` | caddy-security-custom (build) | 80, 443 | Reverse Proxy + Auth |
| `php` | php:8.3-fpm-alpine | – | PHP FastCGI |
| `caddymanager-backend` | caddymanager/caddymanager-backend | – | CaddyManager API |
| `caddymanager-frontend` | caddymanager/caddymanager-frontend | 8011 | CaddyManager Web UI |

## Domains

| URL | Beschreibung |
|-----|-------------|
| `https://caddy.home.pfeiffer-privat.de/manager` | CaddyManager Web UI (Auth-geschützt) |
| `https://caddy.home.pfeiffer-privat.de/auth` | Auth-Portal |
| `https://infra.home.pfeiffer-privat.de` | PHP-Starter-Webseite |

## Struktur

```
caddy/
├── docker-compose.yml
├── Dockerfile
├── .env.example
├── config/
│   └── Caddyfile        ← Proxy-Konfiguration (alle Domains hier)
└── site/
    └── index.php        ← PHP-Webseite
```

---

## Setup

### 1. Stack in Portainer anlegen

Portainer → **Stacks → Add Stack → Repository**

| Feld | Wert |
|------|------|
| Name | `caddy` |
| Repository URL | `https://git.pfeiffer-privat.de/ppfeiffer/Infrastruktur.git` |
| Repository reference | `refs/heads/main` |
| Compose path | `caddy/docker-compose.yml` |
| Authentication | Forgejo-Token hinterlegen |

### 2. Environment Variables in Portainer

| Variable | Beschreibung | Beispiel |
|----------|-------------|---------|
| `CADDY_ACME_EMAIL` | E-Mail für Let's Encrypt | `deine@email.de` |
| `CADDY_JWT_SECRET` | JWT für Caddy Security Auth | *(openssl rand -base64 48)* |
| `CADDYMANAGER_JWT_SECRET` | JWT für CaddyManager Backend | *(openssl rand -base64 48)* |

### 3. Stack deployen → **Deploy the stack**

### 4. Caddy Security Admin-User anlegen (einmalig)

```bash
docker exec -it caddy caddy security local users add \
  --identity-store localdb \
  --username admin \
  --email admin@pfeiffer-privat.de \
  --password SICHERES_PASSWORT \
  --roles authp/admin
```

### 5. CaddyManager öffnen

URL: `https://caddy.home.pfeiffer-privat.de/manager`

- Zuerst Login über Caddy Security Auth-Portal
- Dann CaddyManager Login: `admin` / `caddyrocks` → **sofort Passwort ändern!**

### 6. Caddy-Server in CaddyManager hinzufügen

CaddyManager → **Servers → Add Server**

| Feld | Wert |
|------|------|
| Name | `Hauptserver` |
| URL | `http://caddy:2019` |

---

## Neue Domain hinzufügen

Im `Caddyfile` am Ende ergänzen:

```
meine-app.home.pfeiffer-privat.de {
    reverse_proxy localhost:PORT
}
```

Commit ins Repo → in Portainer Stack **Update** → Caddy lädt automatisch neu.

Alternativ direkt über **CaddyManager → Configurations** editieren.

---

## Webseite anpassen

PHP-Dateien liegen in `caddy/site/`. Änderungen committen → Stack updaten.

---

## Logs

```bash
docker logs -f caddy
docker logs -f caddymanager-backend
docker logs -f caddymanager-frontend
```

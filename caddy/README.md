# Caddy Stack

Haupt-Reverse-Proxy auf Basis von **Caddy Security** mit automatischen Let's Encrypt Zertifikaten, Auth-Portal und PHP-Webseite.

## Dienste

| Service | Image | Port | Beschreibung |
|---------|-------|------|-------------|
| `caddy` | caddy-security-custom (build) | 80, 443 | Reverse Proxy + Auth |
| `php` | php:8.3-fpm-alpine | – | PHP FastCGI für Webseite |

## Domains

| Domain | Beschreibung |
|--------|-------------|
| `caddy.home.pfeiffer-privat.de` | Caddy Admin UI + Auth-Portal |
| `infra.home.pfeiffer-privat.de` | PHP-Starter-Webseite |

## Struktur

```
caddy/
├── docker-compose.yml
├── Dockerfile
├── .env.example
├── config/
│   └── Caddyfile        ← Proxy-Konfiguration
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
| `CADDY_JWT_SECRET` | JWT Secret für Auth | *(openssl rand -base64 48)* |

### 3. Stack deployen → **Deploy the stack**

Caddy holt automatisch Let's Encrypt Zertifikate für alle konfigurierten Domains.

### 4. Admin-User anlegen (Caddy Security)

Nach dem ersten Start per Caddy Security CLI:

```bash
docker exec -it caddy caddy security local users add \
  --identity-store localdb \
  --username admin \
  --email admin@pfeiffer-privat.de \
  --password SICHERES_PASSWORT \
  --roles authp/admin
```

---

## Neue Domain hinzufügen

Im `Caddyfile` am Ende ergänzen:

```
meine-app.home.pfeiffer-privat.de {
    reverse_proxy localhost:PORT
}
```

Dann in Portainer den Stack **Update** auslösen – Caddy lädt die Konfiguration automatisch neu und holt ein Zertifikat.

---

## Webseite anpassen

PHP-Dateien liegen in `caddy/site/`. Änderungen direkt im Repo committen → in Portainer Stack updaten.

---

## Logs

```bash
docker logs -f caddy
docker logs -f caddy-php
```

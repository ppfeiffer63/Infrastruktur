# Architektur – Infrastruktur

Dieses Dokument beschreibt die Gesamtarchitektur der selbst gehosteten Dienste.

---

## Überblick

Alle Dienste laufen als Docker Compose Stacks auf einem einzelnen Linux-Host. Caddy übernimmt als zentraler Reverse-Proxy TLS-Terminierung, Authentication und Routing für alle anderen Stacks.

```
Internet
    │
    ▼
┌──────────────────────────────────────────────┐
│  Caddy (Port 80/443 – Let's Encrypt TLS)     │
│  caddy.home.pfeiffer-privat.de               │
│                                              │
│  Auth-Portal (Caddy Security)                │
│  CaddyManager Web UI                         │
└───┬───────────────┬────────────────┬─────────┘
    │               │                │
    ▼               ▼                ▼
┌────────┐   ┌─────────────┐   ┌──────────────┐
│  PHP   │   │   Matrix    │   │ MeshMonitor  │
│Webseite│   │  Synapse    │   │    :8080     │
│ :9000  │   │  Element    │   │              │
│        │   │Synapse Admin│   │  Serial-     │
└────────┘   └──────┬──────┘   │  Bridge      │
                    │           │  :4403       │
                    ▼           └──────┬───────┘
             ┌──────────┐             │
             │PostgreSQL│       ┌─────▼──────┐
             │ (Matrix) │       │PostgreSQL  │
             └──────────┘       │ (Mesh)     │
                                └────────────┘
```

---

## Stacks

### caddy – Reverse Proxy & Auth

**Zweck:** Zentraler Einstiegspunkt für alle HTTP/HTTPS-Anfragen. Stellt TLS-Zertifikate via Let's Encrypt bereit und schützt Dienste mit einem Auth-Portal.

| Service | Image | Intern | Extern |
|---------|-------|--------|--------|
| caddy | caddy-security-custom (build) | :2019 (Admin API) | 80, 443 |
| php | php:8.3-fpm-alpine | :9000 | – |
| caddymanager-backend | caddymanager/caddymanager-backend | :3000 | – |
| caddymanager-frontend | caddymanager/caddymanager-frontend | :80 | :8011 |

**Domains:**

| Domain | Ziel | Auth |
|--------|------|------|
| `infra.home.pfeiffer-privat.de` | PHP-Webseite | – |
| `caddy.home.pfeiffer-privat.de/auth` | Caddy Auth-Portal | – |
| `caddy.home.pfeiffer-privat.de/manager` | CaddyManager UI | ✅ |

**Besonderheiten:**
- Custom-Build mit `caddy-security`-Plugin
- JWT-basiertes Auth-Portal (cookie domain: `home.pfeiffer-privat.de`)
- CaddyManager verwaltet die Caddy-Konfiguration per Web UI über die Admin API

---

### matrix – Chat-Server

**Zweck:** Selbst gehosteter Matrix-Homeserver mit Element Web Client und Admin-Oberfläche.

| Service | Image | Intern | Extern |
|---------|-------|--------|--------|
| postgres | postgres:16-alpine | :5432 | – |
| synapse | matrix-synapse-custom (build) | :8008 | :8008 |
| synapse-init | curlimages/curl (einmalig) | – | – |
| element | vectorim/element-web | :80 | :8009 |
| synapse-admin | awesometechnologies/synapse-admin | :80 | :8010 |

**Domains (über Caddy geroutet):**

| Domain | Ziel |
|--------|------|
| `matrix.home.pfeiffer-privat.de` | Synapse :8008 |
| `element.home.pfeiffer-privat.de` | Element :8009 |
| `matrix.home.pfeiffer-privat.de/admin` | Synapse Admin :8010 |

**Besonderheiten:**
- Synapse mit Custom-Dockerfile: automatische `homeserver.yaml`-Generierung beim ersten Start
- `synapse-init`-Container legt Admin-User einmalig per Skript an (idempotent via Marker-Datei)
- Federation deaktiviert (privater Homeserver)
- Registrierung nur per Token

---

### meshmonitor – Meshtastic-Netzwerküberwachung

**Zweck:** Überwachung und Logging eines Meshtastic-Funk-Netzwerks via USB-angeschlossenem Node.

| Service | Image | Intern | Extern |
|---------|-------|--------|--------|
| postgres | postgres:16-alpine | :5432 | – |
| serial-bridge | ghcr.io/yeraze/meshtastic-serial-bridge | :4403 | :4403 |
| meshmonitor | ghcr.io/yeraze/meshmonitor | :3001, :4404 | :8080, :4404 |
| mqtt-proxy | ghcr.io/ln4cy/mqtt-proxy | – | – |

**Hardware-Voraussetzung:** Meshtastic-Node per USB (`/dev/ttyUSB0`)

**Datenfluss:**
```
[Meshtastic-Node via USB /dev/ttyUSB0]
         │
         ▼
[serial-bridge :4403]   ← TCP-Bridge für seriellen Port
         │
         ▼
[meshmonitor :8080/:4404] ─── [PostgreSQL]
         │
         ▼
[mqtt-proxy]   ← leitet MQTT-Traffic über MeshMonitor
```

---

## Deployment-Prozess

Alle Stacks werden über **Portainer CE** aus diesem Forgejo-Repository deployed.

```
Änderung in Repo (push)
         │
         ▼
    Forgejo Repo
         │
         │  (Portainer pull on deploy / update)
         ▼
  Portainer CE
    Stack → Repository
         │
         ▼
  docker compose up -d
```

**Ablauf für neuen Stack:**
1. Ordner im Repo anlegen: `<stackname>/docker-compose.yml`, `.env.example`, `README.md`
2. Push nach main
3. In Portainer: `Stacks → Add Stack → Repository` → Compose-Pfad angeben
4. Environment Variables aus `.env.example` befüllen
5. Deploy

**Stack updaten:**
```
Portainer → Stacks → [Stack] → Update the stack → Pull and redeploy
```

---

## Secrets & Sicherheit

| Regel | Detail |
|-------|--------|
| `.env`-Dateien | Lokal auf dem Host, **nie** im Repo |
| `.env.example` | Im Repo, ohne echte Werte |
| `.gitignore` | `**/.env` global ausgeschlossen |
| Passwörter | `openssl rand -base64 32` / `openssl rand -base64 48` |
| TLS | Let's Encrypt via Caddy (automatisch) |
| Auth | Caddy Security Auth-Portal (JWT, Rollen) |

---

## Netzwerk-Ports (Host)

| Port | Protokoll | Dienst |
|------|-----------|--------|
| 80 | TCP | Caddy HTTP (→ HTTPS Redirect) |
| 443 | TCP/UDP | Caddy HTTPS (inkl. HTTP/3) |
| 8008 | TCP | Matrix Synapse (intern) |
| 8009 | TCP | Element Web (intern) |
| 8010 | TCP | Synapse Admin (intern) |
| 8011 | TCP | CaddyManager (intern) |
| 8080 | TCP | MeshMonitor Web UI |
| 4403 | TCP | Meshtastic Serial Bridge |
| 4404 | TCP | MeshMonitor TCP |

> Ports 8008–8011 sind intern und werden von Caddy geroutet. Sie müssen nicht nach außen offen sein.

---

*Letzte Aktualisierung: automatisch durch Claude*

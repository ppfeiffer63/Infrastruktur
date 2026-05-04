# Claude-Projekthistorie – Infrastruktur

Dieses Dokument wird von Claude automatisch gepflegt und protokolliert alle relevanten Aktivitäten, Entscheidungen und Änderungen im Rahmen des Infrastruktur-Projekts.

---

## Projekt-Kontext

| Eigenschaft | Wert |
|-------------|------|
| Repository | https://git.pfeiffer-privat.de/ppfeiffer/Infrastruktur.git |
| Plattform | Forgejo (Self-hosted) |
| Benutzer | ppfeiffer |
| Hauptthemen | PHP, Python, C++, Docker, Forgejo, WordPress, Meshtastic/MeshCore |

---

## Changelog

### 2025-05-04

#### Initialisierung Claude-Zugriff
- Schreibzugriff auf das Forgejo-Repository erfolgreich eingerichtet (Token-Authentifizierung)
- Repository-Struktur analysiert

#### Bestehende Struktur beim ersten Zugriff
```
Infrastruktur/
├── README.md          (minimal)
└── MeshMonitor/
    ├── docker-compose.yml
    └── .env
```

#### MeshMonitor – Docker-Setup (bereits vorhanden)
- **Stack:** PostgreSQL 16 (alpine), meshtastic-serial-bridge, MeshMonitor, MQTT-Proxy
- **Ports:** 8080 (Web-UI), 4403 (Serial-Bridge TCP), 4404 (MeshMonitor TCP)
- **Gerät:** `/dev/ttyUSB0` (Meshtastic-Node via USB/Serial)
- **Datenbank:** PostgreSQL mit Health-Check
- **Besonderheit:** MQTT-Proxy leitet Traffic über MeshMonitor statt direkt über Node-WiFi
- Quellen: `ghcr.io/yeraze/meshmonitor`, `ghcr.io/yeraze/meshtastic-serial-bridge`, `ghcr.io/ln4cy/mqtt-proxy`

#### Dokumentation erstellt
- `CLAUDE.md` angelegt (diese Datei) – wird bei jeder Claude-Session aktualisiert
- `README.md` überarbeitet – Projektübersicht ergänzt

---

## Offene Punkte / TODOs
<!-- Claude trägt hier ein, was noch aussteht -->
- [ ] `.env`-Datei: `SESSION_SECRET` und `POSTGRES_PASSWORD` mit echten Werten befüllen
- [ ] MeshMonitor: Prüfen ob `/data/scripts` Volume-Mount für Auto-Responder genutzt wird
- [ ] Weitere Dienste/Projekte dokumentieren

---

## Architekturübersicht

```
[Meshtastic-Node via USB]
        │
        ▼
[serial-bridge :4403]
        │
        ▼
[meshmonitor :8080/:4404] ──── [PostgreSQL]
        │
        ▼
[mqtt-proxy] ──── (MQTT-Broker extern)
```


### 2025-05-04 (2)

#### Repo-Struktur reorganisiert
- Konzept: Repository als **Sammlung von Docker Compose Stacks**
- Neue Verzeichnisstruktur: `stacks/<stackname>/`
- `MeshMonitor/` → `stacks/meshmonitor/` verschoben
- `.env` → `.env.example` (Secrets nie ins Repo)
- `.gitignore` angelegt: `**/.env` global ausgeschlossen
- README.md neu strukturiert

#### Konventionen (ab jetzt gültig)
- Jeder Stack liegt in `stacks/<name>/`
- Pflichtdateien pro Stack: `docker-compose.yml`, `.env.example`, `README.md`
- Echte `.env`-Dateien bleiben lokal, nie im Repo


### 2025-05-04 (3)

#### Struktur vereinfacht
- `stacks/`-Zwischenordner entfernt
- Jeder Stack liegt direkt im Root: `<stackname>/`
- `meshmonitor/` entsprechend verschoben
- Konvention: `docker-compose.yml`, `.env.example`, `README.md` pro Ordner


### 2025-05-04 (4)

#### Stack: matrix – angelegt
- **Synapse** (matrixdotorg/synapse:latest) auf Port 8008
- **PostgreSQL 16** als Datenbank (Health-Check)
- **Element Web** (vectorim/element-web) auf Port 8009
- TLS/HTTPS übernimmt vorhandener NGinx Proxy Manager
- Domains: `matrix.home.pfeiffer-privat.de` → :8008, `element.home.pfeiffer-privat.de` → :8009
- `element-config.json` mit Dark-Theme und DE-Locale vorkonfiguriert
- Dateien: `docker-compose.yml`, `.env.example`, `element-config.json`, `README.md`


### 2025-05-04 (5)

#### Portainer-Integration für alle Stacks
- Beide Stacks (meshmonitor, matrix) auf Portainer CE umgestellt
- Änderungen:
  - Eigene `networks`-Blöcke entfernt (Portainer verwaltet Netzwerke)
  - Volume-Namen eindeutig pro Stack (Präfix: `matrix-*`, `meshmonitor-*`)
  - `env_file: .env` entfernt – Env-Variablen werden in Portainer UI gepflegt
  - `./scripts` Bind-Mount → named Volume `meshmonitor-scripts`
  - `.env.example` als Referenz für Portainer Environment Variables
- READMEs: Portainer-Setup-Anleitung mit Repository-Anbindung ergänzt
- Portainer-Workflow: Stacks → Add Stack → Repository → Forgejo-Token


### 2025-05-04 (6)

#### Matrix: Authentifizierung konfiguriert
- Entscheidung: nur lokale User, Admin legt an
- `enable_registration: false`, `allow_guest_access: false`
- Federation deaktiviert (`federation_domain_whitelist: []`)
- `homeserver-additions.yaml` angelegt – Vorlage für manuelle homeserver.yaml-Anpassungen
- README erweitert um vollständige Benutzerverwaltung:
  - User anlegen, auflisten, Passwort zurücksetzen, deaktivieren
  - Admin-API Beispiele mit Access Token


### 2025-05-04 (7)

#### Matrix: Umstellung auf Invitation-only per Registration Token
- `enable_registration: true` + `registration_requires_token: true`
- User können sich selbst registrieren, aber nur mit Admin-ausgestelltem Token
- `homeserver-additions.yaml` aktualisiert
- README erweitert um vollständige Token-Verwaltung (erstellen, auflisten, löschen)
- Registrierungsablauf aus User-Sicht dokumentiert


### 2025-05-04 (8)

#### Matrix: Synapse Admin UI hinzugefügt
- Image: `awesometechnologies/synapse-admin:latest`
- Port 8010, erreichbar unter `https://matrix.home.pfeiffer-privat.de/admin`
- Pfad-Routing via NGinx Proxy Manager Custom Nginx Configuration (location /admin)
- `REACT_APP_SERVER` auf Synapse-Domain gesetzt
- README: NGinx Custom Config für /admin-Pfad dokumentiert
- Token-Verwaltung jetzt primär über Admin UI möglich


### 2025-05-04 (9)

#### Matrix: Vollautomatische Initialisierung via Docker Entrypoint
- Custom Synapse-Image mit eigenem Entrypoint (`synapse/Dockerfile` + `synapse/entrypoint.sh`)
- Entrypoint erledigt beim ersten Start automatisch:
  - homeserver.yaml generieren
  - PostgreSQL konfigurieren (psycopg2)
  - Registration Token aktivieren
  - Federation deaktivieren
  - Init-Marker `/data/.initialized` verhindert Wiederholung
- Neuer `synapse-init`-Container legt Admin-User automatisch an (curl-basiert)
- Neue Env-Variablen: `ADMIN_USERNAME`, `ADMIN_PASSWORD`
- README vereinfacht: nur noch Portainer-Deploy + NGinx nötig


### 2025-05-04 (10)

#### Stack: caddy – angelegt (Haupt-Reverse-Proxy)
- **Caddy Security** (ghcr.io/greenpau/caddy-security) als Haupt-Proxy
- Ports 80/443 direkt am Host (kein vorgelagerter Proxy)
- Automatische Let's Encrypt Zertifikate für alle Domains
- **PHP 8.3-fpm-alpine** für PHP-Webseite
- Auth-Portal via Caddy Security (JWT, lokale User-DB)
- Domains:
  - `caddy.home.pfeiffer-privat.de` → Admin UI + Auth-Portal (geschützt)
  - `infra.home.pfeiffer-privat.de` → PHP-Starter-Webseite
- Struktur: `config/Caddyfile` (Proxy-Konfig), `site/` (PHP-Webseite)
- Neue Domains: einfach im Caddyfile ergänzen, Stack updaten
- Env-Variablen: `CADDY_ACME_EMAIL`, `CADDY_JWT_SECRET`


### 2025-05-04 (11)

#### Caddy Stack: CaddyManager Web UI integriert
- CaddyManager v0.0.2 (SQLite, kein MongoDB nötig)
- Backend: `caddymanager/caddymanager-backend` – Admin API Proxy zu Caddy :2019
- Frontend: `caddymanager/caddymanager-frontend` – Vue 3 Web UI auf Port 8011
- Caddy Admin API auf `0.0.0.0:2019` (für CaddyManager erreichbar)
- Routing: `/manager` → CaddyManager (Auth-geschützt via Caddy Security)
- Neue Env-Variable: `CADDYMANAGER_JWT_SECRET`
- Standard-Login CaddyManager: admin/caddyrocks → sofort ändern
- Server in CaddyManager: `http://caddy:2019` eintragen


### 2025-05-04 (12)

#### Installationsanleitung angelegt
- `docs/INSTALLATION.md` erstellt
- Enthält: Mermaid-Diagramme (Architektur, Ablaufdiagramme, Sequenzdiagramme)
- Alle 5 Phasen dokumentiert: Host, Portainer, Caddy, Matrix, MeshMonitor
- Fehlerbehebungstabelle und nützliche Befehle ergänzt
- Mermaid wird in Forgejo nativ gerendert


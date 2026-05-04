# Matrix Stack

Docker Compose Stack mit Synapse (Matrix-Server), PostgreSQL, Element Web und Synapse Admin.  
Verwaltung via **Portainer CE**, TLS via **NGinx Proxy Manager**.

## Funktionsweise

Beim ersten Start wird alles **vollautomatisch** eingerichtet:
1. `homeserver.yaml` wird generiert
2. PostgreSQL, Registration Token und Federation werden konfiguriert
3. Admin-User wird angelegt

Kein manueller Eingriff nötig.

## Dienste

| Service | Image | Port | Beschreibung |
|---------|-------|------|-------------|
| `postgres` | postgres:16-alpine | – | Datenbank |
| `synapse` | matrix-synapse-custom (build) | 8008 | Matrix-Homeserver |
| `synapse-init` | curlimages/curl | – | Einmaliger Init-Container (Admin anlegen) |
| `element` | vectorim/element-web | 8009 | Web-Client |
| `synapse-admin` | awesometechnologies/synapse-admin | 8010 | Admin-Webinterface |

## Domains / Pfade (via NGinx Proxy Manager)

| URL | Ziel | Beschreibung |
|-----|------|-------------|
| `https://matrix.home.pfeiffer-privat.de` | `localhost:8008` | Synapse API |
| `https://matrix.home.pfeiffer-privat.de/admin` | `localhost:8010` | Synapse Admin UI |
| `https://element.home.pfeiffer-privat.de` | `localhost:8009` | Element Web-Client |

---

## Setup

### 1. Stack in Portainer anlegen

Portainer → **Stacks → Add Stack → Repository**

| Feld | Wert |
|------|------|
| Name | `matrix` |
| Repository URL | `https://git.pfeiffer-privat.de/ppfeiffer/Infrastruktur.git` |
| Repository reference | `refs/heads/main` |
| Compose path | `matrix/docker-compose.yml` |
| Authentication | Forgejo-Token hinterlegen |

### 2. Environment Variables in Portainer

| Variable | Beschreibung | Beispiel |
|----------|-------------|---------|
| `MATRIX_DOMAIN` | Domain des Servers | `matrix.home.pfeiffer-privat.de` |
| `POSTGRES_PASSWORD` | Datenbankpasswort | *(openssl rand -base64 32)* |
| `ADMIN_USERNAME` | Admin-Benutzername | `admin` |
| `ADMIN_PASSWORD` | Admin-Passwort | *(sicheres Passwort)* |

### 3. Stack deployen → **Deploy the stack**

Fertig – alles weitere läuft automatisch.

### 4. NGinx Proxy Manager konfigurieren

**Proxy Host – Synapse + Admin UI:**
- Domain: `matrix.home.pfeiffer-privat.de`
- Forward: `localhost:8008`
- SSL: Let's Encrypt

Unter **Custom Nginx Configuration**:
```nginx
location /admin {
    proxy_pass http://localhost:8010/;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

**Proxy Host – Element:**
- Domain: `element.home.pfeiffer-privat.de`
- Forward: `localhost:8009`
- SSL: Let's Encrypt

### 5. Synapse Admin UI öffnen

URL: `https://matrix.home.pfeiffer-privat.de/admin`  
Login mit `ADMIN_USERNAME` / `ADMIN_PASSWORD`

---

## Benutzerverwaltung

Über **Synapse Admin UI** → `https://matrix.home.pfeiffer-privat.de/admin`:
- Registration Tokens erstellen & verwalten
- User anlegen, bearbeiten, deaktivieren
- Passwörter zurücksetzen
- Räume verwalten

## Logs

```bash
docker logs -f matrix-synapse
docker logs -f matrix-synapse-init
docker logs -f matrix-postgres
```

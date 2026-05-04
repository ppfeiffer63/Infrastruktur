# Matrix Stack

Docker Compose Stack mit Synapse (Matrix-Server), PostgreSQL und Element Web.  
Verwaltung via **Portainer CE**, TLS via **NGinx Proxy Manager**.

## Authentifizierung

- **Nur lokale User** – Registrierung deaktiviert
- Admin legt User per CLI an
- Kein Gastzugang, kein Self-Signup

## Dienste

| Service | Image | Port | Beschreibung |
|---------|-------|------|-------------|
| `postgres` | postgres:16-alpine | – | Datenbank |
| `synapse` | matrixdotorg/synapse | 8008 | Matrix-Homeserver |
| `element` | vectorim/element-web | 8009 | Web-Client |

## Domains (via NGinx Proxy Manager)

| Domain | Ziel |
|--------|------|
| `matrix.home.pfeiffer-privat.de` | `localhost:8008` |
| `element.home.pfeiffer-privat.de` | `localhost:8009` |

---

## Setup (Schritt für Schritt)

### 1. Synapse-Konfiguration generieren (einmalig auf dem Host)

```bash
docker run --rm \
  -e SYNAPSE_SERVER_NAME=matrix.home.pfeiffer-privat.de \
  -e SYNAPSE_REPORT_STATS=no \
  -v matrix-synapse-data:/data \
  matrixdotorg/synapse:latest generate
```

Die `homeserver.yaml` liegt danach im Volume `matrix-synapse-data`.

### 2. homeserver.yaml anpassen

Volume auf dem Host öffnen (Pfad je nach Docker-Installation):

```bash
nano /var/lib/docker/volumes/matrix-synapse-data/_data/homeserver.yaml
```

Folgende Einstellungen setzen (siehe auch `homeserver-additions.yaml` im Repo):

```yaml
# Registrierung sperren
enable_registration: false
allow_guest_access: false

# PostgreSQL
database:
  name: psycopg2
  args:
    user: synapse
    password: DEIN_POSTGRES_PASSWORD
    database: synapse
    host: postgres
    cp_min: 5
    cp_max: 10

# Federation deaktivieren (nur interner Server)
federation_domain_whitelist: []
```

### 3. Stack in Portainer anlegen

Portainer → **Stacks → Add Stack → Repository**

| Feld | Wert |
|------|------|
| Name | `matrix` |
| Repository URL | `https://git.pfeiffer-privat.de/ppfeiffer/Infrastruktur.git` |
| Repository reference | `refs/heads/main` |
| Compose path | `matrix/docker-compose.yml` |
| Authentication | Forgejo-Token hinterlegen |

**Environment Variables in Portainer:**

| Variable | Wert |
|----------|------|
| `MATRIX_DOMAIN` | `matrix.home.pfeiffer-privat.de` |
| `POSTGRES_PASSWORD` | *(openssl rand -base64 32)* |

### 4. Stack deployen

→ **Deploy the stack**

### 5. NGinx Proxy Manager

| Proxy Host | Forward | SSL |
|------------|---------|-----|
| `matrix.home.pfeiffer-privat.de` | `localhost:8008` | Let's Encrypt |
| `element.home.pfeiffer-privat.de` | `localhost:8009` | Let's Encrypt |

---

## Benutzerverwaltung

### User anlegen

```bash
docker exec -it matrix-synapse register_new_matrix_user \
  -c /data/homeserver.yaml \
  -u BENUTZERNAME \
  -p PASSWORT \
  -a \
  http://localhost:8008
```

Flag `-a` = Admin. Für normale User `-a` weglassen.

### User-Liste anzeigen (Admin-API)

```bash
curl -X GET \
  "http://localhost:8008/_synapse/admin/v2/users?from=0&limit=10" \
  -H "Authorization: Bearer ADMIN_ACCESS_TOKEN"
```

Den Admin-Token bekommt man nach dem Login in Element unter:  
*Einstellungen → Hilfe & Info → Zugriffstoken*

### Passwort zurücksetzen

```bash
docker exec -it matrix-synapse hash_password -p NEUES_PASSWORT
```

Den Hash dann per Admin-API setzen:

```bash
curl -X PUT \
  "http://localhost:8008/_synapse/admin/v2/users/@BENUTZERNAME:matrix.home.pfeiffer-privat.de" \
  -H "Authorization: Bearer ADMIN_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"password": "NEUES_PASSWORT"}'
```

### User deaktivieren

```bash
curl -X PUT \
  "http://localhost:8008/_synapse/admin/v2/users/@BENUTZERNAME:matrix.home.pfeiffer-privat.de" \
  -H "Authorization: Bearer ADMIN_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"deactivated": true}'
```

---

## Logs

```bash
docker logs -f matrix-synapse
docker logs -f matrix-postgres
```

# Matrix Stack

Docker Compose Stack mit Synapse (Matrix-Server), PostgreSQL und Element Web.

## Dienste

| Service | Image | Port | Beschreibung |
|---------|-------|------|-------------|
| `postgres` | postgres:16-alpine | – | Datenbank |
| `synapse` | matrixdotorg/synapse | 8008 | Matrix-Homeserver |
| `element` | vectorim/element-web | 8009 | Web-Client |

## Domains (via NGinx Proxy Manager)

| Domain | Ziel | Beschreibung |
|--------|------|-------------|
| `matrix.home.pfeiffer-privat.de` | `localhost:8008` | Synapse API |
| `element.home.pfeiffer-privat.de` | `localhost:8009` | Element Web-Client |

## Setup

### 1. Umgebungsvariablen

```bash
cp .env.example .env
nano .env   # POSTGRES_PASSWORD setzen
```

### 2. Synapse-Konfiguration generieren

```bash
docker compose run --rm synapse generate
```

Dies erzeugt `/var/lib/docker/volumes/matrix_synapse-data/_data/homeserver.yaml`.

### 3. homeserver.yaml anpassen

Die generierte Datei liegt im Docker Volume. Wichtigste Einstellungen:

```yaml
# Datenbank auf PostgreSQL umstellen
database:
  name: psycopg2
  args:
    user: synapse
    password: DEIN_POSTGRES_PASSWORD
    database: synapse
    host: postgres
    cp_min: 5
    cp_max: 10

# Registrierung (deaktivieren für privaten Server)
enable_registration: false
```

### 4. Stack starten

```bash
docker compose up -d
```

### 5. NGinx Proxy Manager einrichten

**Proxy Host 1 – Synapse:**
- Domain: `matrix.home.pfeiffer-privat.de`
- Forward Hostname: `localhost`
- Forward Port: `8008`
- SSL: Let's Encrypt aktivieren

**Proxy Host 2 – Element:**
- Domain: `element.home.pfeiffer-privat.de`
- Forward Hostname: `localhost`
- Forward Port: `8009`
- SSL: Let's Encrypt aktivieren

### 6. Admin-Benutzer anlegen

```bash
docker exec -it matrix-synapse register_new_matrix_user \
  -c /data/homeserver.yaml \
  -u admin -p PASSWORT -a \
  http://localhost:8008
```

## Logs

```bash
docker compose logs -f synapse
docker compose logs -f postgres
```

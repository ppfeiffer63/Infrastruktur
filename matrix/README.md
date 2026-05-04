# Matrix Stack

Docker Compose Stack mit Synapse (Matrix-Server), PostgreSQL und Element Web.  
Verwaltung via **Portainer CE**, TLS via **NGinx Proxy Manager**.

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

## Portainer Setup

### 1. Stack anlegen

Portainer → **Stacks → Add Stack → Repository**

| Feld | Wert |
|------|------|
| Name | `matrix` |
| Repository URL | `https://git.pfeiffer-privat.de/ppfeiffer/Infrastruktur.git` |
| Repository reference | `refs/heads/main` |
| Compose path | `matrix/docker-compose.yml` |
| Authentication | Forgejo-Token hinterlegen |

### 2. Environment Variables in Portainer

Unter **Environment Variables** folgende Werte eintragen:

| Variable | Wert |
|----------|------|
| `MATRIX_DOMAIN` | `matrix.home.pfeiffer-privat.de` |
| `POSTGRES_PASSWORD` | *(sicheres Passwort, z.B. `openssl rand -base64 32`)* |

### 3. Synapse-Konfiguration generieren (einmalig)

Vor dem ersten Stack-Start einmalig auf dem Host ausführen:

```bash
docker run --rm \
  -e SYNAPSE_SERVER_NAME=matrix.home.pfeiffer-privat.de \
  -e SYNAPSE_REPORT_STATS=no \
  -v matrix-synapse-data:/data \
  matrixdotorg/synapse:latest generate
```

Danach `homeserver.yaml` im Volume auf PostgreSQL umstellen:

```yaml
database:
  name: psycopg2
  args:
    user: synapse
    password: DEIN_POSTGRES_PASSWORD
    database: synapse
    host: postgres
    cp_min: 5
    cp_max: 10
```

### 4. Stack in Portainer deployen

→ **Deploy the stack**

### 5. NGinx Proxy Manager

**Proxy Host 1 – Synapse:**
- Domain: `matrix.home.pfeiffer-privat.de`
- Forward: `localhost:8008`
- SSL: Let's Encrypt

**Proxy Host 2 – Element:**
- Domain: `element.home.pfeiffer-privat.de`
- Forward: `localhost:8009`
- SSL: Let's Encrypt

### 6. Admin-Benutzer anlegen

```bash
docker exec -it matrix-synapse register_new_matrix_user \
  -c /data/homeserver.yaml \
  -u admin -p PASSWORT -a \
  http://localhost:8008
```

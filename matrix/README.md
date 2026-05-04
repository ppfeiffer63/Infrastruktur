# Matrix Stack

Docker Compose Stack mit Synapse (Matrix-Server), PostgreSQL, Element Web und Synapse Admin.  
Verwaltung via **Portainer CE**, TLS via **NGinx Proxy Manager**.

## Authentifizierung

- **Invitation-only per Registration Token**
- User registrieren sich in Element mit einem Admin-ausgestellten Token
- Kein Gastzugang

## Dienste

| Service | Image | Port | Beschreibung |
|---------|-------|------|-------------|
| `postgres` | postgres:16-alpine | – | Datenbank |
| `synapse` | matrixdotorg/synapse | 8008 | Matrix-Homeserver |
| `element` | vectorim/element-web | 8009 | Web-Client |
| `synapse-admin` | awesometechnologies/synapse-admin | 8010 | Admin-Webinterface |

## Domains / Pfade (via NGinx Proxy Manager)

| URL | Ziel | Beschreibung |
|-----|------|-------------|
| `https://matrix.home.pfeiffer-privat.de` | `localhost:8008` | Synapse API |
| `https://matrix.home.pfeiffer-privat.de/admin` | `localhost:8010` | Synapse Admin UI |
| `https://element.home.pfeiffer-privat.de` | `localhost:8009` | Element Web-Client |

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

### 2. homeserver.yaml anpassen

```bash
nano /var/lib/docker/volumes/matrix-synapse-data/_data/homeserver.yaml
```

Einstellungen (siehe auch `homeserver-additions.yaml`):

```yaml
enable_registration: true
registration_requires_token: true
allow_guest_access: false

database:
  name: psycopg2
  args:
    user: synapse
    password: DEIN_POSTGRES_PASSWORD
    database: synapse
    host: postgres
    cp_min: 5
    cp_max: 10

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

### 4. Stack deployen → **Deploy the stack**

### 5. NGinx Proxy Manager konfigurieren

**Proxy Host 1 – Synapse + Admin UI:**

- Domain: `matrix.home.pfeiffer-privat.de`
- Forward: `localhost:8008`
- SSL: Let's Encrypt aktivieren

Dann unter **Custom Nginx Configuration** (Tab im Proxy Host):

```nginx
# Synapse Admin UI unter /admin
location /admin {
    proxy_pass http://localhost:8010/;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

**Proxy Host 2 – Element:**

- Domain: `element.home.pfeiffer-privat.de`
- Forward: `localhost:8009`
- SSL: Let's Encrypt aktivieren

### 6. Admin-User anlegen (erster Login für Synapse Admin)

```bash
docker exec -it matrix-synapse register_new_matrix_user \
  -c /data/homeserver.yaml \
  -u admin -p PASSWORT -a \
  http://localhost:8008
```

### 7. Synapse Admin UI öffnen

URL: `https://matrix.home.pfeiffer-privat.de/admin`

- Homeserver: `https://matrix.home.pfeiffer-privat.de`
- Username + Passwort des Admin-Accounts eingeben

---

## Token-Verwaltung (alternativ per Admin UI)

Tokens können bequem über die Synapse Admin UI verwaltet werden:  
**Synapse Admin → Registration Tokens**

Oder per API:

```bash
# Token erstellen (einmalig nutzbar)
curl -X POST \
  "http://localhost:8008/_synapse/admin/v1/registration_tokens/new" \
  -H "Authorization: Bearer ADMIN_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"token": "einladung-max", "uses_allowed": 1}'

# Alle Tokens anzeigen
curl -X GET \
  "http://localhost:8008/_synapse/admin/v1/registration_tokens" \
  -H "Authorization: Bearer ADMIN_ACCESS_TOKEN"
```

---

## Benutzerverwaltung

Über die **Synapse Admin UI** unter `https://matrix.home.pfeiffer-privat.de/admin`:
- User anlegen, bearbeiten, deaktivieren
- Passwörter zurücksetzen
- Räume verwalten
- Registration Tokens erstellen

---

## Logs

```bash
docker logs -f matrix-synapse
docker logs -f matrix-synapse-admin
docker logs -f matrix-postgres
```

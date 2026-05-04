# Matrix Stack

Docker Compose Stack mit Synapse (Matrix-Server), PostgreSQL und Element Web.  
Verwaltung via **Portainer CE**, TLS via **NGinx Proxy Manager**.

## Authentifizierung

- **Invitation-only per Registration Token**
- User registrieren sich selbst in Element – aber nur mit gültigem Token
- Token wird vom Admin erstellt und individuell vergeben
- Kein Gastzugang

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

### 2. homeserver.yaml anpassen

```bash
nano /var/lib/docker/volumes/matrix-synapse-data/_data/homeserver.yaml
```

Folgende Einstellungen setzen (siehe auch `homeserver-additions.yaml`):

```yaml
# Invitation-only per Token
enable_registration: true
registration_requires_token: true
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

# Federation deaktivieren
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

### 5. NGinx Proxy Manager

| Proxy Host | Forward | SSL |
|------------|---------|-----|
| `matrix.home.pfeiffer-privat.de` | `localhost:8008` | Let's Encrypt |
| `element.home.pfeiffer-privat.de` | `localhost:8009` | Let's Encrypt |

---

## Token-Verwaltung

Tokens werden per Admin-API verwaltet. Dafür wird ein Admin-Zugriffstoken benötigt:  
Element → *Einstellungen → Hilfe & Info → Zugriffstoken*

### Token erstellen

```bash
# Einmaliger Token (nur ein User kann sich damit registrieren)
curl -X POST \
  "http://localhost:8008/_synapse/admin/v1/registration_tokens/new" \
  -H "Authorization: Bearer ADMIN_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"uses_allowed": 1}'

# Token mit Ablaufdatum (Unix-Timestamp)
curl -X POST \
  "http://localhost:8008/_synapse/admin/v1/registration_tokens/new" \
  -H "Authorization: Bearer ADMIN_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"uses_allowed": 1, "expiry_time": 1735689600000}'

# Eigenen Token-String festlegen
curl -X POST \
  "http://localhost:8008/_synapse/admin/v1/registration_tokens/new" \
  -H "Authorization: Bearer ADMIN_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"token": "einladung-max", "uses_allowed": 1}'
```

### Alle Tokens anzeigen

```bash
curl -X GET \
  "http://localhost:8008/_synapse/admin/v1/registration_tokens" \
  -H "Authorization: Bearer ADMIN_ACCESS_TOKEN"
```

### Token löschen

```bash
curl -X DELETE \
  "http://localhost:8008/_synapse/admin/v1/registration_tokens/TOKEN_STRING" \
  -H "Authorization: Bearer ADMIN_ACCESS_TOKEN"
```

---

## Benutzerverwaltung

### User-Liste anzeigen

```bash
curl -X GET \
  "http://localhost:8008/_synapse/admin/v2/users?from=0&limit=10" \
  -H "Authorization: Bearer ADMIN_ACCESS_TOKEN"
```

### Passwort zurücksetzen

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

## Registrierung (User-Sicht)

1. Element öffnen: `https://element.home.pfeiffer-privat.de`
2. *Konto erstellen* → Homeserver: `matrix.home.pfeiffer-privat.de`
3. Registrierungstoken eingeben (vom Admin erhalten)
4. Benutzername + Passwort wählen → fertig

---

## Logs

```bash
docker logs -f matrix-synapse
docker logs -f matrix-postgres
```

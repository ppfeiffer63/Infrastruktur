# MeshMonitor Stack

Docker Compose Stack zur Überwachung eines Meshtastic-Netzwerks.  
Verwaltung via **Portainer CE**.

## Dienste

| Service | Image | Port | Beschreibung |
|---------|-------|------|-------------|
| `postgres` | postgres:16-alpine | – | Datenbank |
| `serial-bridge` | ghcr.io/yeraze/meshtastic-serial-bridge | 4403 | Serial → TCP Bridge |
| `meshmonitor` | ghcr.io/yeraze/meshmonitor | 8080, 4404 | Web-UI & API |
| `mqtt-proxy` | ghcr.io/ln4cy/mqtt-proxy | – | MQTT-Proxy |

## Portainer Setup

### 1. Stack anlegen

Portainer → **Stacks → Add Stack → Repository**

| Feld | Wert |
|------|------|
| Name | `meshmonitor` |
| Repository URL | `https://git.pfeiffer-privat.de/ppfeiffer/Infrastruktur.git` |
| Repository reference | `refs/heads/main` |
| Compose path | `meshmonitor/docker-compose.yml` |
| Authentication | Forgejo-Token hinterlegen |

### 2. Environment Variables in Portainer

| Variable | Wert |
|----------|------|
| `SESSION_SECRET` | *(z.B. `openssl rand -base64 32`)* |
| `POSTGRES_USER` | `meshmonitor` |
| `POSTGRES_PASSWORD` | *(sicheres Passwort)* |

### 3. Voraussetzungen

- Meshtastic-Node per USB (`/dev/ttyUSB0`) am Host angeschlossen

### 4. Stack deployen

→ **Deploy the stack**

## Zugriff

- Web-UI: http://localhost:8080

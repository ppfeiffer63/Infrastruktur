# MeshMonitor Stack

Docker Compose Stack zur Überwachung eines Meshtastic-Netzwerks.

## Dienste

| Service | Image | Port | Beschreibung |
|---------|-------|------|-------------|
| `postgres` | postgres:16-alpine | – | Datenbank |
| `serial-bridge` | ghcr.io/yeraze/meshtastic-serial-bridge | 4403 | Serial → TCP Bridge |
| `meshmonitor` | ghcr.io/yeraze/meshmonitor | 8080, 4404 | Web-UI & API |
| `mqtt-proxy` | ghcr.io/ln4cy/mqtt-proxy | – | MQTT-Proxy |

## Setup

```bash
cp .env.example .env
# .env bearbeiten und Passwörter setzen
nano .env

docker compose up -d
```

## Voraussetzungen

- Meshtastic-Node per USB (`/dev/ttyUSB0`)
- Docker & Docker Compose

## Zugriff

- Web-UI: http://localhost:8080

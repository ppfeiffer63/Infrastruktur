# Infrastruktur – Server & Dienste

Übersicht aller Server, Environments und laufenden Stacks.  
Verwaltet über **Portainer CE** auf dem Hauptserver (Debian-Docker).

---

## Server-Übersicht

| Server | Status | IP / Socket | CPU | RAM | Rolle |
|--------|--------|-------------|-----|-----|-------|
| **Debian-Docker** | ✅ Up | lokal (`/var/run/docker.sock`) | 4 | 8.2 GB | Hauptserver, Portainer-Host |
| **ThinkCentre** | ✅ Up | 192.168.11.4:9001 | 4 | 8.2 GB | Meshtastic-Node |
| **Dev-Server** | ✅ Up | 192.168.11.11:9001 | 2 | 3.8 GB | Entwicklung / MeshCore |
| **Intel_Plate** | 🔵 Geplant/Offline | 192.168.11.6:9001 | 4 | 3.6 GB | Zukünftige Nutzung |

---

## Debian-Docker (Hauptserver)

Hauptserver der gesamten Infrastruktur. Portainer CE läuft hier direkt via Docker-Socket.

**Stacks:**

| Stack | Beschreibung |
|-------|-------------|
| `forgejo` | Selbst gehosteter Git-Server (Forgejo) – Quelle dieses Repos |
| `mct` | MeshCore Remote-Terminal |
| `wine` | Windows-Umgebung unter Linux (Wine) für Paketradio-Software |

---

## ThinkCentre

Dedizierter Host für den Meshtastic-Node (USB-angeschlossen).

**Stacks:**

| Stack | Beschreibung |
|-------|-------------|
| `mesh_monitor` | MeshMonitor-Stack – Überwachung des Meshtastic-Netzwerks (PostgreSQL, Serial-Bridge, MQTT-Proxy) |

---

## Dev-Server

Entwicklungsserver für MeshCore-Dienste.

**Stacks:**

| Stack | Status | Beschreibung |
|-------|--------|-------------|
| `meshcore-bot` | ✅ Aktiv | MeshCore Dashboard / Bot |
| `meshcore-meshdd` | ⚠️ Veraltet | MeshCore Daemon – wird nicht mehr aktiv genutzt |

---

## Intel_Plate

Aktuell offline. Soll perspektivisch in die Infrastruktur integriert werden.  
Nutzung noch nicht definiert – bleibt als Environment in Portainer hinterlegt.

---

## Gesamtübersicht

```
┌─────────────────────────────────────────────────────────┐
│  Heimnetz  192.168.11.0/24                              │
│                                                         │
│  ┌──────────────────────────────────────────────────┐  │
│  │  Debian-Docker  (Hauptserver, Portainer-Host)    │  │
│  │  /var/run/docker.sock                            │  │
│  │                                                  │  │
│  │  Stacks:                                         │  │
│  │  ├── forgejo      Git-Server                     │  │
│  │  ├── mct          MeshCore Remote-Terminal       │  │
│  │  └── wine         Windows/Paketradio (Wine)      │  │
│  └──────────────────────────────────────────────────┘  │
│                                                         │
│  ┌──────────────────────────────────────────────────┐  │
│  │  ThinkCentre  192.168.11.4                       │  │
│  │  Agent 2.39.1                                    │  │
│  │                                                  │  │
│  │  Stacks:                                         │  │
│  │  └── mesh_monitor  Meshtastic-Überwachung        │  │
│  │                    (+ USB /dev/ttyUSB0)          │  │
│  └──────────────────────────────────────────────────┘  │
│                                                         │
│  ┌──────────────────────────────────────────────────┐  │
│  │  Dev-Server  192.168.11.11                       │  │
│  │  Agent 2.39.1                                    │  │
│  │                                                  │  │
│  │  Stacks:                                         │  │
│  │  ├── meshcore-bot     MeshCore Dashboard         │  │
│  │  └── meshcore-meshdd  [veraltet]                 │  │
│  └──────────────────────────────────────────────────┘  │
│                                                         │
│  ┌──────────────────────────────────────────────────┐  │
│  │  Intel_Plate  192.168.11.6  [OFFLINE/GEPLANT]   │  │
│  │  Agent 2.39.1                                    │  │
│  └──────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
```

---

## Portainer-Verbindungstypen

| Typ | Beschreibung |
|-----|-------------|
| **Standalone (Socket)** | Portainer läuft auf demselben Host, Zugriff direkt via `/var/run/docker.sock` |
| **Agent** | Portainer-Agent läuft als Container auf dem Remote-Host, Verbindung via Port 9001 |

---

*Letzte Aktualisierung: automatisch durch Claude*

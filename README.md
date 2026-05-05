# Infrastruktur

Sammlung von Docker Compose Stacks für selbst gehostete Dienste.  
Verwaltung über Portainer CE, Quellcode in diesem Forgejo-Repository.

---

## Stacks

| Ordner | Beschreibung | Status |
|--------|-------------|--------|
| [`caddy/`](./caddy/README.md) | Reverse Proxy, TLS, Auth-Portal, CaddyManager | ✅ |
| [`matrix/`](./matrix/README.md) | Matrix Synapse, Element Web, Synapse Admin | ✅ |
| [`meshmonitor/`](./meshmonitor/README.md) | Meshtastic-Netzwerküberwachung | ✅ |

---

## Struktur

Jeder Stack liegt als eigener Ordner direkt im Root:

```
<stackname>/
├── docker-compose.yml   – Dienste & Konfiguration
├── .env.example         – Variablen-Vorlage (keine echten Secrets)
└── README.md            – Stack-Dokumentation
```

> ⚠️ Echte `.env`-Dateien mit Secrets sind per `.gitignore` ausgeschlossen und werden **nie** committed.

---

## Dokumentation

| Dokument | Inhalt |
|----------|--------|
| [`docs/INSTALLATION.md`](./docs/INSTALLATION.md) | Schritt-für-Schritt-Einrichtung aller Stacks |
| [`docs/ARCHITECTURE.md`](./docs/ARCHITECTURE.md) | Gesamtarchitektur, Dienste, Ports, Deployment |
| [`CLAUDE.md`](./CLAUDE.md) | Claude-Projekthistorie (automatisch gepflegt) |

---

## Deployment

Alle Stacks werden über **Portainer CE** aus diesem Repository deployed.  
Details siehe [Installationsanleitung](./docs/INSTALLATION.md).

---

## Themen

🐳 Docker · 🦊 Forgejo · 🔒 Caddy · 💬 Matrix · 📡 Meshtastic · 🐘 PHP

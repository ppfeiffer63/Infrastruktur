# Infrastruktur

Sammlung von Docker Compose Stacks für selbst gehostete Dienste.

## Struktur

```
<stackname>/
├── docker-compose.yml
├── .env.example       # Vorlage – kopieren nach .env
└── README.md
```

> ⚠️ `.env`-Dateien mit echten Secrets sind per `.gitignore` ausgeschlossen.

## Stacks

| Ordner | Beschreibung |
|--------|-------------|
| `meshmonitor/` | Meshtastic-Netzwerküberwachung |

## Claude-Historie

Alle Aktivitäten sind in [`CLAUDE.md`](./CLAUDE.md) protokolliert.

## Dokumentation

| Dokument | Beschreibung |
|----------|-------------|
| [docs/INSTALLATION.md](./docs/INSTALLATION.md) | Vollständige Installationsanleitung |
| [docs/GITHUB_MIRROR_UND_PORTAINER.md](./docs/GITHUB_MIRROR_UND_PORTAINER.md) | GitHub Mirror + Portainer |

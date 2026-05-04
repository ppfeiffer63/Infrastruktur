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

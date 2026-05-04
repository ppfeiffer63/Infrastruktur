# Infrastruktur

Sammlung von Docker Compose Stacks und Konfigurationen für selbst gehostete Dienste.

## Struktur

```
stacks/
└── meshmonitor/       # Meshtastic-Netzwerküberwachung
    ├── docker-compose.yml
    ├── .env.example   # Vorlage – kopieren nach .env
    └── README.md
```

## Verwendung

Jeden Stack findest du unter `stacks/<name>/`. Dort liegt immer:
- `docker-compose.yml` – der Stack
- `.env.example` – Vorlage für Umgebungsvariablen (→ kopieren nach `.env`)
- `README.md` – Stack-spezifische Doku

> ⚠️ `.env`-Dateien mit echten Secrets sind per `.gitignore` ausgeschlossen.

## Claude-Historie

Alle Aktivitäten sind in [`CLAUDE.md`](./CLAUDE.md) protokolliert.

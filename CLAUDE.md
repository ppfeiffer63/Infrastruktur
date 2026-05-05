# Claude-Projekthistorie – Infrastruktur

Dieses Dokument wird von Claude automatisch gepflegt und protokolliert alle relevanten Aktivitäten, Entscheidungen und Änderungen im Rahmen des Infrastruktur-Projekts.

---

## Projekt-Kontext

| Eigenschaft | Wert |
|-------------|------|
| Repository | https://git.pfeiffer-privat.de/ppfeiffer/Infrastruktur.git |
| Plattform | Forgejo (Self-hosted) |
| Benutzer | ppfeiffer |
| Deployment | Portainer CE |
| Hauptthemen | PHP, Python, C++, Docker, Forgejo, WordPress, Meshtastic/MeshCore |

---

## Konventionen

### Stack-Struktur (verbindlich)

Jeder Docker Stack liegt als **eigener Unterordner direkt im Repository-Root** (kein `stacks/`-Zwischenordner):

```
<stackname>/
├── docker-compose.yml   – Pflichtdatei
├── .env.example         – Pflichtdatei (keine echten Secrets)
└── README.md            – Pflichtdatei
```

### Secrets

- `.env`-Dateien werden **niemals** committed (global via `.gitignore` ausgeschlossen)
- Passwörter generieren mit `openssl rand -base64 32` bzw. `-base64 48`
- Secrets werden in Portainer als Environment Variables hinterlegt

---

## Changelog

### 2026-05-05

#### Dokumentation aufgebaut (dieses Thema)
- `docs/ARCHITECTURE.md` neu erstellt:
  - Gesamtarchitektur mit ASCII-Diagramm
  - Alle drei Stacks tabellarisch beschrieben (Services, Ports, Domains)
  - Deployment-Prozess dokumentiert
  - Secrets-Konzept festgehalten
  - Host-Port-Übersicht
- `README.md` (Root) neu strukturiert:
  - Stack-Übersichtstabelle mit Links
  - Struktur-Konvention dokumentiert
  - Dokumentations-Index
- `CLAUDE.md` neu gegliedert mit Konventionen und vollständigem Changelog

#### Aktueller Stand des Repos
```
Infrastruktur/
├── .gitignore
├── README.md
├── CLAUDE.md
├── docs/
│   ├── ARCHITECTURE.md   ← neu
│   └── INSTALLATION.md   ← bereits vorhanden
├── caddy/
│   ├── docker-compose.yml
│   ├── .env.example
│   ├── README.md
│   ├── Dockerfile
│   ├── config/Caddyfile
│   └── site/index.php
├── matrix/
│   ├── docker-compose.yml
│   ├── .env.example
│   ├── README.md
│   ├── element-config.json
│   ├── homeserver-additions.yaml
│   └── synapse/
│       ├── Dockerfile
│       └── entrypoint.sh
└── meshmonitor/
    ├── docker-compose.yml
    ├── .env.example
    └── README.md
```

---

### 2025-05-04

#### Session 1 – Projekt-Setup
- Schreibzugriff auf Forgejo-Repository eingerichtet (Token-Authentifizierung)
- Repository-Struktur analysiert (bestehender MeshMonitor-Stack)
- `CLAUDE.md` und `README.md` initial angelegt
- `stacks/`-Zwischenordner nach Rücksprache entfernt
- Konvention festgelegt: Stacks direkt im Root, keine Zwischenebene
- `.gitignore` mit `**/.env` angelegt
- MeshMonitor-Stack reorganisiert und mit `.env.example` + `README.md` ergänzt

---

## Offene Punkte / TODOs

- [ ] Server-/Hostinfrastruktur dokumentieren (welche Maschinen, Netzwerk, Hardware)
- [ ] MeshMonitor: Langfristig eigene Implementierung anstelle von yeraze-Images prüfen
- [ ] Weitere Stacks dokumentieren sobald hinzugefügt


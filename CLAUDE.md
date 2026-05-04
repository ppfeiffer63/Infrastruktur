# Claude-Projekthistorie – Infrastruktur

Dieses Dokument wird von Claude automatisch gepflegt und protokolliert alle relevanten Aktivitäten, Entscheidungen und Änderungen im Rahmen des Infrastruktur-Projekts.

---

## Projekt-Kontext

| Eigenschaft | Wert |
|-------------|------|
| Repository | https://git.pfeiffer-privat.de/ppfeiffer/Infrastruktur.git |
| Plattform | Forgejo (Self-hosted) |
| Benutzer | ppfeiffer |
| Hauptthemen | PHP, Python, C++, Docker, Forgejo, WordPress, Meshtastic/MeshCore |

---

## Changelog

### 2025-05-04

#### Initialisierung Claude-Zugriff
- Schreibzugriff auf das Forgejo-Repository erfolgreich eingerichtet (Token-Authentifizierung)
- Repository-Struktur analysiert

#### Bestehende Struktur beim ersten Zugriff
```
Infrastruktur/
├── README.md          (minimal)
└── MeshMonitor/
    ├── docker-compose.yml
    └── .env
```

#### MeshMonitor – Docker-Setup (bereits vorhanden)
- **Stack:** PostgreSQL 16 (alpine), meshtastic-serial-bridge, MeshMonitor, MQTT-Proxy
- **Ports:** 8080 (Web-UI), 4403 (Serial-Bridge TCP), 4404 (MeshMonitor TCP)
- **Gerät:** `/dev/ttyUSB0` (Meshtastic-Node via USB/Serial)
- **Datenbank:** PostgreSQL mit Health-Check
- **Besonderheit:** MQTT-Proxy leitet Traffic über MeshMonitor statt direkt über Node-WiFi
- Quellen: `ghcr.io/yeraze/meshmonitor`, `ghcr.io/yeraze/meshtastic-serial-bridge`, `ghcr.io/ln4cy/mqtt-proxy`

#### Dokumentation erstellt
- `CLAUDE.md` angelegt (diese Datei) – wird bei jeder Claude-Session aktualisiert
- `README.md` überarbeitet – Projektübersicht ergänzt

---

## Offene Punkte / TODOs
<!-- Claude trägt hier ein, was noch aussteht -->
- [ ] `.env`-Datei: `SESSION_SECRET` und `POSTGRES_PASSWORD` mit echten Werten befüllen
- [ ] MeshMonitor: Prüfen ob `/data/scripts` Volume-Mount für Auto-Responder genutzt wird
- [ ] Weitere Dienste/Projekte dokumentieren

---

## Architekturübersicht

```
[Meshtastic-Node via USB]
        │
        ▼
[serial-bridge :4403]
        │
        ▼
[meshmonitor :8080/:4404] ──── [PostgreSQL]
        │
        ▼
[mqtt-proxy] ──── (MQTT-Broker extern)
```


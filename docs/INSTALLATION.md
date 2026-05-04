# Installationsanleitung – Infrastruktur

> **Ziel:** Vollständige Einrichtung aller Docker-Stacks auf einem Heimserver  
> **Voraussetzung:** Linux-Server (Ubuntu/Debian empfohlen), Root-Zugang, Portainer CE läuft bereits

---

## Überblick

```mermaid
flowchart TD
    A([🖥️ Linux-Server]) --> B[Phase 1\nHost vorbereiten]
    B --> C[Phase 2\nPortainer prüfen]
    C --> D[Phase 3\nCaddy Stack]
    D --> E[Phase 4\nMatrix Stack]
    E --> F[Phase 5\nMeshMonitor Stack]
    F --> G([✅ Fertig])

    style A fill:#1e293b,color:#94a3b8
    style G fill:#0f6e56,color:#9fe1cb
    style D fill:#185fa5,color:#b5d4f4
    style E fill:#993556,color:#f4c0d1
    style F fill:#3b6d11,color:#c0dd97
```

---

## Architektur

```mermaid
graph TB
    internet([🌐 Internet]) --> caddy

    subgraph host[🖥️ Host-Server]
        caddy["🔒 Caddy\nPort 80 / 443\nLet's Encrypt"]

        caddy -->|infra.home.*| php[PHP 8.3\nWebseite]
        caddy -->|caddy.home.*/manager| caddymgr[CaddyManager\nWeb UI]
        caddy -->|matrix.home.*| synapse[Matrix\nSynapse :8008]
        caddy -->|element.home.*| element[Element\nWeb :8009]
        caddy -->|matrix.home.*/admin| synapse_admin[Synapse\nAdmin :8010]

        synapse --> pg_matrix[(PostgreSQL\nMatrix DB)]
        synapse_admin -.->|Admin API| synapse

        meshmonitor[MeshMonitor\n:8080] --> pg_mesh[(PostgreSQL\nMesh DB)]
        serial_bridge[Serial Bridge\n:4403] -->|TCP| meshmonitor
        usb[🔌 /dev/ttyUSB0] --> serial_bridge
    end

    style host fill:#0f172a,color:#e2e8f0
    style caddy fill:#185fa5,color:#b5d4f4
    style synapse fill:#993556,color:#f4c0d1
    style meshmonitor fill:#3b6d11,color:#c0dd97
```

---

## Phase 1 – Host vorbereiten

### 1.1 Docker installieren

```bash
# Paketlisten aktualisieren
apt update && apt upgrade -y

# Docker installieren
apt install -y docker.io docker-compose-plugin curl git

# Docker-Dienst starten und aktivieren
systemctl enable --now docker

# Prüfen ob Docker läuft
docker --version
docker compose version
```

### 1.2 Bestehende Dienste auf Port 80/443 stoppen

> ⚠️ **Wichtig:** Caddy belegt Port 80 und 443 direkt. Alle anderen Dienste auf diesen Ports müssen vorher gestoppt werden.

```bash
# NGinx Proxy Manager stoppen (falls vorhanden)
docker stop nginxproxymanager 2>/dev/null || true

# Prüfen ob Ports frei sind
ss -tlnp | grep -E ':80|:443'
# Ausgabe sollte leer sein
```

### 1.3 DNS-Einträge prüfen

Alle Domains müssen per A-Record auf die Server-IP zeigen:

```mermaid
graph LR
    subgraph DNS[📡 DNS-Einträge]
        d1[caddy.home.pfeiffer-privat.de]
        d2[infra.home.pfeiffer-privat.de]
        d3[matrix.home.pfeiffer-privat.de]
        d4[element.home.pfeiffer-privat.de]
    end

    d1 & d2 & d3 & d4 -->|A-Record| ip[🖥️ Server-IP]
```

```bash
# DNS prüfen
dig +short caddy.home.pfeiffer-privat.de
dig +short matrix.home.pfeiffer-privat.de
# Beide müssen die Server-IP zurückgeben
```

---

## Phase 2 – Portainer prüfen

> Portainer CE läuft bereits. Nur den Forgejo-Token hinterlegen.

### 2.1 Forgejo-Token in Portainer hinterlegen

```
Portainer → Settings → Credentials → Add credential
  Type:     Git
  Name:     forgejo
  Username: ppfeiffer
  Token:    (Forgejo Access Token)
```

```mermaid
sequenceDiagram
    participant U as 🧑 Admin
    participant P as Portainer
    participant G as Forgejo

    U->>P: Settings → Credentials → Add
    U->>P: Token eingeben
    P->>G: Test-Pull (verify)
    G-->>P: ✅ OK
    P-->>U: Credential gespeichert
```

---

## Phase 3 – Caddy Stack (Haupt-Proxy)

> **Zuerst installieren!** Caddy ist der Reverse-Proxy für alle anderen Dienste.

### 3.1 Secrets generieren

```bash
# JWT Secret für Caddy Security Auth-Portal
openssl rand -base64 48
# → Notieren als CADDY_JWT_SECRET

# JWT Secret für CaddyManager
openssl rand -base64 48
# → Notieren als CADDYMANAGER_JWT_SECRET
```

### 3.2 Stack in Portainer anlegen

```
Portainer → Stacks → Add Stack → Repository

  Name:               caddy
  Repository URL:     https://git.pfeiffer-privat.de/ppfeiffer/Infrastruktur.git
  Repository ref:     refs/heads/main
  Compose path:       caddy/docker-compose.yml
  Authentication:     Credential "forgejo" auswählen
```

**Environment Variables:**

| Variable | Wert |
|----------|------|
| `CADDY_ACME_EMAIL` | deine@email.de |
| `CADDY_JWT_SECRET` | *(generierter Wert aus 3.1)* |
| `CADDYMANAGER_JWT_SECRET` | *(generierter Wert aus 3.1)* |

```
→ Deploy the stack
```

### 3.3 Caddy Security Admin-User anlegen

> Einmaliger Schritt nach dem ersten Deploy:

```bash
docker exec -it caddy caddy security local users add \
  --identity-store localdb \
  --username admin \
  --email admin@pfeiffer-privat.de \
  --password SICHERES_PASSWORT \
  --roles authp/admin
```

### 3.4 CaddyManager einrichten

```
1. https://caddy.home.pfeiffer-privat.de/manager öffnen
2. Login über Caddy Security Auth-Portal (admin + Passwort aus 3.3)
3. CaddyManager Login: admin / caddyrocks
4. ⚠️ Passwort sofort ändern! (User Management → admin → Edit)
5. Servers → Add Server:
     Name: Hauptserver
     URL:  http://caddy:2019
```

```mermaid
sequenceDiagram
    participant U as 🧑 Admin
    participant A as Auth-Portal
    participant C as CaddyManager

    U->>A: caddy.home.*/manager aufrufen
    A-->>U: Login-Seite
    U->>A: admin + Passwort (aus 3.3)
    A-->>U: JWT-Token + Redirect
    U->>C: CaddyManager Login
    C-->>U: Dashboard
    U->>C: Servers → Add → http://caddy:2019
    C-->>U: ✅ Caddy verbunden
```

### 3.5 Ergebnis prüfen

```bash
# Caddy läuft?
docker ps | grep caddy

# Let's Encrypt Zertifikat vorhanden?
curl -I https://infra.home.pfeiffer-privat.de

# PHP-Webseite erreichbar?
curl -s https://infra.home.pfeiffer-privat.de | grep "pfeiffer"
```

**Erwartetes Ergebnis:**

| URL | Status |
|-----|--------|
| `https://infra.home.pfeiffer-privat.de` | ✅ PHP-Seite |
| `https://caddy.home.pfeiffer-privat.de/manager` | ✅ CaddyManager |

---

## Phase 4 – Matrix Stack

### 4.1 Secrets generieren

```bash
# PostgreSQL Passwort
openssl rand -base64 32
# → Notieren als POSTGRES_PASSWORD

# Admin-Passwort frei wählen
# → Notieren als ADMIN_PASSWORD
```

### 4.2 Stack in Portainer anlegen

```
Portainer → Stacks → Add Stack → Repository

  Name:           matrix
  Repository URL: https://git.pfeiffer-privat.de/ppfeiffer/Infrastruktur.git
  Repository ref: refs/heads/main
  Compose path:   matrix/docker-compose.yml
  Authentication: Credential "forgejo" auswählen
```

**Environment Variables:**

| Variable | Wert |
|----------|------|
| `MATRIX_DOMAIN` | `matrix.home.pfeiffer-privat.de` |
| `POSTGRES_PASSWORD` | *(generierter Wert aus 4.1)* |
| `ADMIN_USERNAME` | `admin` |
| `ADMIN_PASSWORD` | *(gewähltes Passwort aus 4.1)* |

```
→ Deploy the stack
```

### 4.3 Automatischer Initialisierungsablauf

```mermaid
sequenceDiagram
    participant P as Portainer
    participant S as Synapse
    participant DB as PostgreSQL
    participant I as synapse-init

    P->>DB: Container starten
    DB-->>P: Health OK
    P->>S: Container starten (custom entrypoint)
    S->>S: homeserver.yaml generieren
    S->>S: PostgreSQL konfigurieren
    S->>S: Registration Token aktivieren
    S->>S: Federation deaktivieren
    S-->>P: Synapse läuft ✅
    P->>I: Init-Container starten
    I->>S: Warte auf /_matrix/client/versions
    S-->>I: 200 OK
    I->>S: Admin-User anlegen
    S-->>I: ✅ Admin erstellt
    I->>I: Marker setzen (.admin_created)
    I-->>P: Init abgeschlossen ✅
```

### 4.4 Matrix-Domains in Caddy eintragen

Im CaddyManager → Configurations → Caddyfile bearbeiten und am Ende ergänzen:

```caddyfile
matrix.home.pfeiffer-privat.de {
    reverse_proxy localhost:8008
}

element.home.pfeiffer-privat.de {
    reverse_proxy localhost:8009
}
```

Alternativ direkt im Repo unter `caddy/config/Caddyfile` ergänzen und in Portainer Stack updaten.

### 4.5 Ergebnis prüfen

```bash
# Synapse läuft?
docker logs matrix-synapse | tail -20

# Matrix API erreichbar?
curl https://matrix.home.pfeiffer-privat.de/_matrix/client/versions
```

**Erwartetes Ergebnis:**

| URL | Status |
|-----|--------|
| `https://matrix.home.pfeiffer-privat.de/_matrix/client/versions` | ✅ JSON-Antwort |
| `https://element.home.pfeiffer-privat.de` | ✅ Element Web |
| `https://matrix.home.pfeiffer-privat.de/admin` | ✅ Synapse Admin |

### 4.6 Ersten Registration Token erstellen

```
Synapse Admin UI → https://matrix.home.pfeiffer-privat.de/admin
→ Login: ADMIN_USERNAME / ADMIN_PASSWORD
→ Registration Tokens → Create Token
     Uses allowed: 1
     Token: einladung-max (oder leer lassen für zufälligen Token)
```

---

## Phase 5 – MeshMonitor Stack (optional)

> Nur relevant wenn ein Meshtastic-Node per USB angeschlossen ist.

### 5.1 Voraussetzung prüfen

```bash
# USB-Device vorhanden?
ls -la /dev/ttyUSB0

# Benutzer zur dialout-Gruppe hinzufügen (falls nötig)
usermod -aG dialout $USER
```

### 5.2 Stack in Portainer anlegen

```
Portainer → Stacks → Add Stack → Repository

  Name:           meshmonitor
  Repository URL: https://git.pfeiffer-privat.de/ppfeiffer/Infrastruktur.git
  Repository ref: refs/heads/main
  Compose path:   meshmonitor/docker-compose.yml
  Authentication: Credential "forgejo" auswählen
```

**Environment Variables:**

| Variable | Wert |
|----------|------|
| `SESSION_SECRET` | *(openssl rand -base64 32)* |
| `POSTGRES_USER` | `meshmonitor` |
| `POSTGRES_PASSWORD` | *(openssl rand -base64 24)* |

```
→ Deploy the stack
```

### 5.3 MeshMonitor in Caddy eintragen

```caddyfile
mesh.home.pfeiffer-privat.de {
    reverse_proxy localhost:8080
}
```

---

## Übersicht aller Dienste nach der Installation

```mermaid
graph LR
    subgraph extern[🌐 Öffentlich erreichbar via HTTPS]
        u1[infra.home.pfeiffer-privat.de]
        u2[caddy.home.pfeiffer-privat.de/manager]
        u3[matrix.home.pfeiffer-privat.de]
        u4[element.home.pfeiffer-privat.de]
        u5[matrix.home.pfeiffer-privat.de/admin]
    end

    subgraph intern[🔒 Intern / Docker]
        c[Caddy :80/:443]
        php[PHP-Webseite]
        cm[CaddyManager]
        syn[Synapse]
        ele[Element]
        sa[Synapse Admin]
        mm[MeshMonitor]
    end

    u1 --> c --> php
    u2 --> c --> cm
    u3 --> c --> syn
    u4 --> c --> ele
    u5 --> c --> sa
```

---

## Nützliche Befehle

### Stack-Status prüfen

```bash
# Alle laufenden Container
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"

# Logs eines Containers
docker logs -f caddy
docker logs -f matrix-synapse
docker logs -f meshmonitor
```

### Stack updaten (nach Repo-Änderungen)

```
Portainer → Stacks → [Stack auswählen] → Update the stack → Pull and redeploy
```

### Caddy neu laden (nach Caddyfile-Änderung)

```bash
docker exec caddy caddy reload --config /etc/caddy/Caddyfile
```

### Matrix Admin-Token abrufen

```
Element → Einstellungen → Hilfe & Info → Zugriffstoken anzeigen
```

---

## Fehlerbehebung

```mermaid
flowchart TD
    E[❌ Problem] --> Q1{Was funktioniert nicht?}

    Q1 -->|Zertifikat fehlt| A1[DNS prüfen\ndig +short domain]
    Q1 -->|Container startet nicht| A2[Logs prüfen\ndocker logs container]
    Q1 -->|Matrix Init schlägt fehl| A3[Init-Log prüfen\ndocker logs matrix-synapse-init]
    Q1 -->|Caddy 502 Bad Gateway| A4[Zieldienst prüfen\ndocker ps]
    Q1 -->|Port bereits belegt| A5[Prozess finden\nss -tlnp | grep :80]

    A1 --> R[✅ Behoben]
    A2 --> R
    A3 --> R
    A4 --> R
    A5 --> R
```

| Problem | Lösung |
|---------|--------|
| `port is already allocated` | `docker ps` → alten Container stoppen |
| `certificate not found` | DNS-Eintrag prüfen, Caddy-Logs prüfen |
| Matrix Init hängt | `docker logs matrix-synapse-init -f` |
| 502 Bad Gateway | Zieldienst läuft? `docker ps` |
| Portainer kann nicht clonen | Forgejo-Token unter Credentials prüfen |

---

*Letzte Aktualisierung: 2025-05-04 – Claude*

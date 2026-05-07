# Installationsanleitung – Infrastruktur

> **Ziel:** Vollständige Einrichtung aller Docker-Stacks auf einem Heimserver  
> **Voraussetzung:** Linux-Server (Ubuntu/Debian empfohlen), Root-Zugang, Portainer CE läuft bereits

---

## Überblick

```mermaid
flowchart TD
    A([🖥️ Linux-Server]) --> B[Phase 1\nHost vorbereiten]
    B --> C[Phase 2\nPortainer + GitHub]
    C --> D[Phase 3\nTraefik Stack]
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
    internet([🌐 Internet]) --> traefik

    subgraph host[🖥️ Host-Server]
        traefik["🔀 Traefik v3\nPort 80 / 443\nLet's Encrypt\nDashboard"]

        traefik -->|traefik.home.*| dashboard[Traefik\nDashboard]
        traefik -->|infra.home.*| nginx[Nginx +\nPHP-Seite]
        traefik -->|matrix.home.*| synapse[Matrix\nSynapse]
        traefik -->|element.home.*| element[Element\nWeb]
        traefik -->|matrix.home.*/admin| synapse_admin[Synapse\nAdmin]
        traefik -->|mesh.home.*| meshmonitor[MeshMonitor]

        synapse --> pg_matrix[(PostgreSQL\nMatrix DB)]
        meshmonitor --> pg_mesh[(PostgreSQL\nMesh DB)]
    end

    subgraph github[🐙 GitHub]
        repo[Infrastruktur\nRepository]
    end

    subgraph forgejo[🦊 Forgejo]
        local_repo[Lokales Repo\nPush-Mirror]
    end

    local_repo -->|automatisch| repo
    repo -->|Git Pull| portainer[🐳 Portainer]
    portainer --> traefik

    style host fill:#0f172a,color:#e2e8f0
    style traefik fill:#185fa5,color:#b5d4f4
    style synapse fill:#993556,color:#f4c0d1
    style meshmonitor fill:#3b6d11,color:#c0dd97
```

---

## Phase 1 – Host vorbereiten

### 1.1 Docker installieren

```bash
apt update && apt upgrade -y
apt install -y docker.io docker-compose-plugin curl git apache2-utils
systemctl enable --now docker
docker --version && docker compose version
```

### 1.2 Port 80 und 443 freigeben

> ⚠️ Traefik belegt Port 80 und 443 direkt. Andere Dienste auf diesen Ports vorher stoppen.

```bash
# NGinx Proxy Manager stoppen (falls vorhanden)
docker stop nginxproxymanager 2>/dev/null || true

# Ports müssen frei sein (Ausgabe muss leer sein)
ss -tlnp | grep -E ':80|:443'
```

### 1.3 DNS-Einträge prüfen

```mermaid
graph LR
    subgraph DNS[📡 DNS A-Records]
        d1[traefik.home.pfeiffer-privat.de]
        d2[infra.home.pfeiffer-privat.de]
        d3[matrix.home.pfeiffer-privat.de]
        d4[element.home.pfeiffer-privat.de]
        d5[mesh.home.pfeiffer-privat.de]
    end
    d1 & d2 & d3 & d4 & d5 -->|A-Record| ip[🖥️ Server-IP]
```

```bash
dig +short traefik.home.pfeiffer-privat.de
dig +short matrix.home.pfeiffer-privat.de
```

### 1.4 Basic Auth Hash für Traefik-Dashboard generieren

```bash
# $ muss als $$ escaped werden – Pflicht für docker-compose!
echo $(htpasswd -nb admin DEIN_PASSWORT) | sed -e 's/\$/\$\$/g'

# Beispielausgabe:
# admin:$$apr1$$ruca84Hq$$mbjdMZBAG.KWn7vfN/SNK/
```

> 📋 Ausgabe kopieren – wird als `TRAEFIK_DASHBOARD_AUTH` in Portainer eingetragen.

---

## Phase 2 – Portainer + GitHub vorbereiten

### 2.1 GitHub Token in Portainer hinterlegen

```
Portainer → Settings → Credentials → Add credential
  Type:     Git
  Name:     github
  Username: DEIN-GITHUB-USERNAME
  Token:    (GitHub Personal Access Token)
```

> Noch kein GitHub Token oder Mirror? Siehe [GITHUB_MIRROR_UND_PORTAINER.md](./GITHUB_MIRROR_UND_PORTAINER.md)

---

## Phase 3 – Traefik Stack (zuerst installieren!)

> Traefik muss als erstes laufen – alle anderen Stacks registrieren sich bei Traefik.

### 3.1 Stack in Portainer anlegen

```
Portainer → Stacks → Add Stack → Repository

  Name:           traefik
  Repository URL: https://github.com/DEIN-USERNAME/Infrastruktur.git
  Repository ref: refs/heads/main
  Compose path:   traefik/docker-compose.yml
  Authentication: Credential "github" auswählen
```

### 3.2 Environment Variables

| Variable | Wert | Beschreibung |
|----------|------|-------------|
| `TRAEFIK_ACME_EMAIL` | `deine@email.de` | E-Mail für Let's Encrypt |
| `TRAEFIK_DASHBOARD_AUTH` | *(Hash aus Phase 1.4)* | Basic Auth für Dashboard |

```
→ Deploy the stack
```

### 3.3 Traefik-Ablauf beim Start

```mermaid
sequenceDiagram
    participant P as Portainer
    participant T as Traefik
    participant LE as Let's Encrypt
    participant D as Docker

    P->>T: Container starten
    T->>D: Docker-Events abonnieren
    D-->>T: Laufende Container + Labels
    T->>T: Routing-Regeln aufbauen
    T->>LE: Zertifikate anfordern
    LE-->>T: TLS-Zertifikate ✅
    T-->>P: Traefik läuft ✅
```

### 3.4 Ergebnis prüfen

| URL | Erwartetes Ergebnis |
|-----|-------------------|
| `https://traefik.home.pfeiffer-privat.de` | ✅ Dashboard (Login-Dialog) |
| `https://infra.home.pfeiffer-privat.de` | ✅ PHP-Startseite |

---

## Phase 4 – Matrix Stack

### 4.1 Secrets generieren

```bash
openssl rand -base64 32  # → POSTGRES_PASSWORD
```

### 4.2 Stack in Portainer anlegen

```
Portainer → Stacks → Add Stack → Repository

  Name:           matrix
  Repository URL: https://github.com/DEIN-USERNAME/Infrastruktur.git
  Repository ref: refs/heads/main
  Compose path:   matrix/docker-compose.yml
  Authentication: Credential "github" auswählen
```

### 4.3 Environment Variables

| Variable | Wert |
|----------|------|
| `MATRIX_DOMAIN` | `matrix.home.pfeiffer-privat.de` |
| `POSTGRES_PASSWORD` | *(openssl rand -base64 32)* |
| `ADMIN_USERNAME` | `admin` |
| `ADMIN_PASSWORD` | *(sicheres Passwort)* |

```
→ Deploy the stack
```

### 4.4 Automatischer Initialisierungsablauf

```mermaid
sequenceDiagram
    participant S as Synapse
    participant DB as PostgreSQL
    participant I as synapse-init
    participant T as Traefik

    DB-->>S: Health OK ✅
    S->>S: homeserver.yaml generieren
    S->>S: PostgreSQL + Token + Federation konfigurieren
    S-->>T: Labels registrieren → Zertifikat anfordern
    I->>S: Warte auf API...
    S-->>I: 200 OK
    I->>S: Admin-User anlegen ✅
```

### 4.5 Ergebnis prüfen

| URL | Erwartetes Ergebnis |
|-----|-------------------|
| `https://matrix.home.pfeiffer-privat.de/_matrix/client/versions` | ✅ JSON |
| `https://element.home.pfeiffer-privat.de` | ✅ Element Web |
| `https://matrix.home.pfeiffer-privat.de/admin` | ✅ Synapse Admin |

---

## Phase 5 – MeshMonitor Stack (optional)

### 5.1 Voraussetzung prüfen

```bash
ls -la /dev/ttyUSB0          # USB-Device vorhanden?
usermod -aG dialout $USER    # Benutzer zur dialout-Gruppe
```

### 5.2 Stack in Portainer anlegen

```
Compose path: meshmonitor/docker-compose.yml
```

### 5.3 Environment Variables

| Variable | Wert |
|----------|------|
| `SESSION_SECRET` | *(openssl rand -base64 32)* |
| `POSTGRES_USER` | `meshmonitor` |
| `POSTGRES_PASSWORD` | *(openssl rand -base64 24)* |

---

## Neuen Dienst hinzufügen (Traefik-Labels)

```yaml
services:
  mein-dienst:
    image: mein-image
    networks:
      - traefik-proxy
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.mein-dienst.rule=Host(`mein-dienst.home.pfeiffer-privat.de`)"
      - "traefik.http.routers.mein-dienst.entrypoints=websecure"
      - "traefik.http.routers.mein-dienst.tls.certresolver=letsencrypt"
      - "traefik.http.services.mein-dienst.loadbalancer.server.port=PORT"
      - "traefik.docker.network=traefik-proxy"

networks:
  traefik-proxy:
    external: true
```

Traefik erkennt den Container **automatisch** – kein Neustart nötig, Zertifikat wird sofort geholt.

---

## Update-Workflow

```mermaid
flowchart LR
    A[✏️ Änderung\nim Repo] -->|git push| B[🦊 Forgejo]
    B -->|Push-Mirror| C[🐙 GitHub]
    C --> D[Portainer]
    D -->|Stack → Update| E[✅ Aktualisiert]
```

```
Portainer → Stacks → [Stack] → Update the stack → Pull and redeploy
```

---

## Nützliche Befehle

```bash
# Alle laufenden Container
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"

# Traefik-Routen anzeigen
curl http://localhost:8080/api/http/routers | python3 -m json.tool

# Traefik-Netzwerk prüfen
docker network inspect traefik-proxy

# Logs
docker logs -f traefik
docker logs -f matrix-synapse
docker logs -f meshmonitor
```

---

## Fehlerbehebung

```mermaid
flowchart TD
    E[❌ Problem] --> Q1{Was funktioniert nicht?}
    Q1 -->|Zertifikat fehlt| A1[DNS prüfen\ndig +short domain]
    Q1 -->|Container startet nicht| A2[docker logs container]
    Q1 -->|Matrix Init schlägt fehl| A3[docker logs matrix-synapse-init]
    Q1 -->|502 Bad Gateway| A4[traefik-proxy Netzwerk?\nLabels korrekt?]
    Q1 -->|Port belegt| A5[ss -tlnp grep :80]
    Q1 -->|Dashboard 401| A6[Hash: $$ statt $?]
    A1 & A2 & A3 & A4 & A5 & A6 --> R[✅ Behoben]
```

| Problem | Lösung |
|---------|--------|
| `port is already allocated` | `docker ps` → alten Container stoppen |
| Zertifikat fehlt | DNS prüfen, `docker logs traefik` |
| 502 Bad Gateway | Container im `traefik-proxy` Netzwerk? Labels vorhanden? |
| Dashboard zeigt 401 | `$$` im Hash prüfen – `$` muss als `$$` escaped sein |
| Matrix Init hängt | `docker logs matrix-synapse-init -f` |
| Portainer kann nicht clonen | GitHub-Token unter Credentials prüfen |

---

*Letzte Aktualisierung: 2025-05-07 – Claude*

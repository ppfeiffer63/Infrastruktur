# GitHub Mirror & Stack-Installation via Portainer

> **Ziel:** Forgejo-Repository automatisch zu GitHub spiegeln und Docker-Stacks in Portainer direkt von GitHub installieren.  
> **Beispiel:** Caddy-Stack

---

## Übersicht

```mermaid
flowchart LR
    dev[👨‍💻 Entwicklung\nlokal / Claude] -->|git push| forgejo[🦊 Forgejo\ngit.pfeiffer-privat.de]
    forgejo -->|Push-Mirror\nautomatisch| github[🐙 GitHub\ngithub.com]
    github -->|Repository URL| portainer[🐳 Portainer\nStack-Installation]
    portainer -->|deploy| docker[🚀 Docker\nStack läuft]
```

---

## Teil 1 – GitHub Repository anlegen

### 1.1 Neues Repository auf GitHub erstellen

```
https://github.com/new

  Repository name:  Infrastruktur
  Visibility:       Private  ← empfohlen (enthält Stack-Strukturen)
  Initialize:       NEIN (kein README, kein .gitignore)

→ Create repository
```

> ⚠️ Das Repository muss **leer** bleiben – Forgejo befüllt es via Mirror.

### 1.2 GitHub Personal Access Token erstellen

```
GitHub → Settings → Developer settings
  → Personal access tokens → Tokens (classic)
  → Generate new token (classic)

  Note:       Forgejo Mirror
  Expiration: No expiration (oder 1 Jahr)
  Scopes:     ✅ repo  (alle Unteroptionen)

→ Generate token
→ Token kopieren und sicher speichern!
```

```mermaid
flowchart TD
    A[GitHub Settings] --> B[Developer settings]
    B --> C[Personal access tokens]
    C --> D[Tokens classic]
    D --> E[Generate new token]
    E --> F[Scope: repo ✅]
    F --> G[Token generieren]
    G --> H[Token kopieren ⚠️\nnur einmal sichtbar!]
```

---

## Teil 2 – Push-Mirror in Forgejo einrichten

### 2.1 Mirror-Einstellungen öffnen

```
https://git.pfeiffer-privat.de/ppfeiffer/Infrastruktur
  → Settings (Zahnrad oben rechts)
  → Repository
  → Mirrors (ganz unten scrollen)
  → Push Mirrors → Add Push Mirror
```

### 2.2 Mirror konfigurieren

```
Remote URL:       https://github.com/DEIN-GITHUB-USERNAME/Infrastruktur.git
Username:         DEIN-GITHUB-USERNAME
Password/Token:   (GitHub Token aus 1.2)
Sync on commit:   ✅ aktivieren  ← sofort bei jedem Push spiegeln

→ Add Push Mirror
```

```mermaid
sequenceDiagram
    participant F as 🦊 Forgejo
    participant G as 🐙 GitHub

    Note over F: Neuer Commit wird gepusht
    F->>F: Push-Mirror prüfen
    F->>G: HTTPS Push mit Token
    G-->>F: 200 OK
    Note over G: Repository aktualisiert ✅
```

### 2.3 Mirror testen

```
Forgejo → Infrastruktur → Settings → Mirrors
  → Push Mirrors → Synchronize Now (▶ Button)
```

Danach auf GitHub prüfen: `https://github.com/DEIN-USERNAME/Infrastruktur`  
→ Alle Dateien sollten sichtbar sein.

---

## Teil 3 – Portainer mit GitHub verbinden

### 3.1 GitHub Token in Portainer hinterlegen

```
Portainer → Settings → Credentials → Add credential

  Type:     Git
  Name:     github
  Username: DEIN-GITHUB-USERNAME
  Token:    (GitHub Token aus 1.2)  ← gleicher Token

→ Save credential
```

> 💡 Der gleiche GitHub-Token funktioniert für Mirror (Schreiben) und Portainer (Lesen).

### 3.2 Caddy-Stack in Portainer anlegen

```
Portainer → Stacks → Add Stack → Repository

  Name:               caddy
  Repository URL:     https://github.com/DEIN-USERNAME/Infrastruktur.git
  Repository ref:     refs/heads/main
  Compose path:       caddy/docker-compose.yml
  Authentication:     ✅ aktivieren → Credential "github" auswählen
```

```mermaid
sequenceDiagram
    participant U as 🧑 Admin
    participant P as 🐳 Portainer
    participant G as 🐙 GitHub

    U->>P: Add Stack → Repository
    U->>P: URL + Compose-Pfad + Credential
    P->>G: Git clone (mit Token)
    G-->>P: caddy/docker-compose.yml
    P->>P: Stack deployen
    P-->>U: ✅ Stack läuft
```

### 3.3 Environment Variables eintragen

Unter **Environment Variables** im selben Formular:

| Variable | Wert |
|----------|------|
| `CADDY_ACME_EMAIL` | `deine@email.de` |
| `CADDY_JWT_SECRET` | *(openssl rand -base64 48)* |
| `CADDYMANAGER_JWT_SECRET` | *(openssl rand -base64 48)* |

```bash
# Secrets lokal generieren:
openssl rand -base64 48  # → CADDY_JWT_SECRET
openssl rand -base64 48  # → CADDYMANAGER_JWT_SECRET
```

### 3.4 Stack deployen

```
→ Deploy the stack
```

Portainer clont das GitHub-Repository, liest `caddy/docker-compose.yml` und startet alle Container.

---

## Teil 4 – Update-Workflow

Wenn Änderungen am Stack vorgenommen werden:

```mermaid
flowchart LR
    A[✏️ Änderung\nim Repo] -->|git push| B[🦊 Forgejo]
    B -->|Push-Mirror\nautomatisch| C[🐙 GitHub]
    C --> D{Portainer\nUpdate}
    D -->|Manuell| E[Stacks → Stack\n→ Update]
    D -->|Automatisch| F[Webhook\noptional]
    E & F --> G[✅ Stack\naktualisiert]
```

### Manueller Update

```
Portainer → Stacks → caddy → Update the stack
  → ✅ Pull latest image
  → ✅ Re-pull image
  → Update the stack
```

### Automatischer Update via Webhook (optional)

```
Portainer → Stacks → caddy → Stack Webhook
  → Webhook URL kopieren

GitHub → Infrastruktur → Settings → Webhooks → Add webhook
  Payload URL:   (Portainer Webhook URL)
  Content type:  application/json
  Events:        ✅ Just the push event
```

---

## Übersicht: Alle Stacks via GitHub installieren

Gleicher Ablauf für alle Stacks – nur `Compose path` ändert sich:

| Stack | Compose path | Beschreibung |
|-------|-------------|-------------|
| `caddy` | `caddy/docker-compose.yml` | Haupt-Proxy *(zuerst installieren!)* |
| `matrix` | `matrix/docker-compose.yml` | Matrix/Synapse + Element |
| `meshmonitor` | `meshmonitor/docker-compose.yml` | Meshtastic-Überwachung |

---

## Fehlerbehebung

| Problem | Ursache | Lösung |
|---------|---------|--------|
| Mirror schlägt fehl | Token ungültig | GitHub → Token prüfen/erneuern |
| Portainer kann nicht clonen | Repo privat, kein Token | Credential in Portainer prüfen |
| `docker-compose.yml not found` | Falscher Compose-Pfad | Pfad prüfen: `caddy/docker-compose.yml` |
| Stack startet nicht | Env-Variable fehlt | Portainer → Stack → Env prüfen |
| GitHub zeigt alten Stand | Mirror noch nicht synchronisiert | Forgejo → Mirrors → Sync Now |

---

*Letzte Aktualisierung: 2025-05-04 – Claude*

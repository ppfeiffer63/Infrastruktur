# Traefik Stack – Einstellungen erklärt

Detaillierte Erklärung jeder Einstellung in `docker-compose.yml`.

---

## Container: `traefik`

### Image & Grundeinstellungen

```yaml
image: traefik:v3.3
```
Feste Version statt `latest` – verhindert unerwartete Breaking Changes bei Updates.  
Neue Version → hier manuell eintragen und Stack updaten.

```yaml
container_name: traefik
```
Fester Name statt zufälligem Docker-Namen. Wichtig damit andere Container  
den Traefik-Container per Hostname (`traefik`) ansprechen können.

```yaml
restart: unless-stopped
```
Container startet automatisch neu bei Absturz oder Server-Neustart.  
Stoppt nur wenn man ihn manuell stoppt (`docker stop traefik`).

---

### Ports

```yaml
ports:
  - "80:80"       # HTTP
  - "443:443"     # HTTPS (TCP)
  - "443:443/udp" # HTTPS (UDP = HTTP/3 / QUIC)
```

| Port | Protokoll | Funktion |
|------|-----------|---------|
| 80 | TCP | HTTP – wird sofort zu HTTPS weitergeleitet |
| 443 | TCP | HTTPS – normales TLS |
| 443 | UDP | HTTP/3 (QUIC) – modernes, schnelleres Protokoll |

> Traefik bindet diese Ports direkt am Host – deshalb darf kein anderer Dienst (NGinx etc.) auf 80/443 laufen.

---

### Umgebungsvariablen

```yaml
environment:
  TZ: Europe/Berlin
```
Zeitzone für korrekte Zeitstempel in Logs. Wichtig für Debugging.

---

### Volumes

```yaml
volumes:
  - /var/run/docker.sock:/var/run/docker.sock:ro
```
**Docker Socket** – Traefik liest hierüber alle laufenden Container und deren Labels.  
`:ro` = read-only, Traefik kann Docker nur lesen, nicht steuern. Sicherheitsmaßnahme.

```yaml
  - traefik-certs:/certs
```
**Zertifikate-Volume** – hier speichert Traefik alle Let's Encrypt Zertifikate (`acme.json`).  
Named Volume statt Bind-Mount → Portainer-kompatibel, überlebt Container-Neustarts.

```yaml
  - traefik-config-dynamic:/config
```
**Dynamische Konfiguration** – hier liegen YAML-Dateien für externe Dienste (File Provider).  
Traefik überwacht diesen Ordner und lädt Änderungen automatisch (hot-reload).

---

### Command – Traefik-Parameter

#### API & Dashboard

```yaml
- --api.dashboard=true
```
Aktiviert das Traefik Web-Dashboard (Routen, Dienste, Zertifikate einsehen).

```yaml
- --api.insecure=false
```
Dashboard ist **nicht** direkt über Port 8080 erreichbar.  
Zugriff nur über einen gesicherten Router (mit TLS + Auth) – sicherer für Produktivbetrieb.

---

#### Docker Provider

```yaml
- --providers.docker=true
```
Traefik liest Docker-Labels aller Container und erstellt daraus automatisch Routen.  
Neue Container werden sofort erkannt – kein Neustart nötig.

```yaml
- --providers.docker.exposedbydefault=false
```
**Wichtige Sicherheitseinstellung** – Container werden NICHT automatisch exposed.  
Nur Container mit dem Label `traefik.enable=true` werden von Traefik verwaltet.  
Ohne diese Einstellung wären alle Container öffentlich erreichbar!

```yaml
- --providers.docker.network=traefik-proxy
```
Traefik sucht Container nur im Netzwerk `traefik-proxy`.  
Container müssen in diesem Netzwerk sein um erreichbar zu sein.

---

#### File Provider

```yaml
- --providers.file.directory=/config
```
Traefik liest alle `.yml`-Dateien aus `/config` als zusätzliche Konfiguration.  
Hier werden externe Dienste definiert (z.B. Home Assistant auf 192.168.11.5).

```yaml
- --providers.file.watch=true
```
**Hot-reload** – Traefik überwacht den `/config`-Ordner auf Änderungen.  
Neue oder geänderte Dateien werden sofort übernommen ohne Neustart.

---

#### Entrypoints

```yaml
- --entrypoints.web.address=:80
```
Definiert Entrypoint namens `web` auf Port 80 (HTTP).

```yaml
- --entrypoints.web.http.redirections.entrypoint.to=websecure
- --entrypoints.web.http.redirections.entrypoint.scheme=https
```
Jede HTTP-Anfrage auf Port 80 wird automatisch zu HTTPS (Port 443) weitergeleitet.  
Gilt für **alle** Domains – kein manueller Redirect nötig.

```yaml
- --entrypoints.websecure.address=:443
```
Definiert Entrypoint namens `websecure` auf Port 443 (HTTPS).  
Dieser Name wird in Router-Labels referenziert: `entrypoints=websecure`.

```yaml
- --entrypoints.websecure.http3=true
```
Aktiviert HTTP/3 (QUIC) auf Port 443/UDP.  
Moderneres Protokoll – schnellere Verbindungsaufbau, besser bei schlechter Verbindung.

---

#### Let's Encrypt (ACME)

```yaml
- --certificatesresolvers.letsencrypt.acme.email=${TRAEFIK_ACME_EMAIL}
```
E-Mail für Let's Encrypt. Wird für Benachrichtigungen bei ablaufenden Zertifikaten genutzt.  
Kommt aus der Portainer Env-Variable `TRAEFIK_ACME_EMAIL`.

```yaml
- --certificatesresolvers.letsencrypt.acme.storage=/certs/acme.json
```
Speicherort aller Zertifikate. Liegt im Volume `traefik-certs` – bleibt bei Neustarts erhalten.

```yaml
- --certificatesresolvers.letsencrypt.acme.httpchallenge.entrypoint=web
```
**HTTP-Challenge** – Let's Encrypt verifiziert die Domain indem es Port 80 aufruft.  
Deshalb muss Port 80 öffentlich erreichbar sein.  
Alternative wäre DNS-Challenge (braucht API-Zugang zum DNS-Provider).

---

#### Logging

```yaml
- --log.level=INFO
```
Log-Level für Traefik-interne Meldungen.  
Mögliche Werte: `DEBUG` (sehr viel) → `INFO` → `WARN` → `ERROR` (nur Fehler).

```yaml
- --accesslog=true
```
Aktiviert Access-Logs – jede HTTP-Anfrage wird geloggt.  
Nützlich für Debugging und Monitoring.  
Kann bei hohem Traffic deaktiviert werden um Performance zu sparen.

---

### Labels – Dashboard & Middlewares

```yaml
- "traefik.enable=true"
```
Traefik verwaltet den eigenen Container (für das Dashboard).

```yaml
- "traefik.http.routers.traefik-dashboard.rule=Host(`traefik.home.pfeiffer-privat.de`)"
```
Anfragen an `traefik.home.pfeiffer-privat.de` werden an das Dashboard weitergeleitet.

```yaml
- "traefik.http.routers.traefik-dashboard.entrypoints=websecure"
```
Dashboard ist nur über HTTPS (Port 443) erreichbar.

```yaml
- "traefik.http.routers.traefik-dashboard.tls.certresolver=letsencrypt"
```
Let's Encrypt-Zertifikat für die Dashboard-Domain automatisch anfordern.

```yaml
- "traefik.http.routers.traefik-dashboard.service=api@internal"
```
Leitet an Traefiks interne API weiter – `api@internal` ist ein eingebauter Dienst.

```yaml
- "traefik.http.routers.traefik-dashboard.middlewares=traefik-auth"
```
Dashboard ist nur nach Authentifizierung zugänglich (Middleware `traefik-auth`).

```yaml
- "traefik.http.middlewares.traefik-auth.basicauth.users=${TRAEFIK_DASHBOARD_AUTH}"
```
**Basic Auth** – Benutzername/Passwort-Schutz für das Dashboard.  
Format: `user:$$hash` – `$` muss als `$$` escaped werden in docker-compose.  
Hash generieren: `echo $(htpasswd -nb admin PASSWORT) | sed -e 's/\$/\$\$/g'`

```yaml
- "traefik.http.middlewares.secure-headers.headers.stsSeconds=31536000"
- "traefik.http.middlewares.secure-headers.headers.stsIncludeSubdomains=true"
- "traefik.http.middlewares.secure-headers.headers.forceSTSHeader=true"
```
**HSTS** (HTTP Strict Transport Security) – Browser merken sich dass die Domain  
nur über HTTPS erreichbar ist. `31536000` = 1 Jahr in Sekunden.  
Verhindert Downgrade-Angriffe auf HTTP.

---

### Netzwerk & Abhängigkeiten

```yaml
networks:
  - traefik-proxy
```
Traefik ist im Netzwerk `traefik-proxy` – nur Container in diesem Netzwerk  
können von Traefik als Backend genutzt werden.

```yaml
depends_on:
  traefik-config-init:
    condition: service_completed_successfully
```
Traefik startet erst **nachdem** `traefik-config-init` erfolgreich abgeschlossen hat.  
Stellt sicher dass `external-services.yml` im Volume vorhanden ist bevor Traefik startet.

---

## Container: `traefik-config-init`

```yaml
image: python:3-alpine
restart: "no"
```
Einmaliger Init-Container – startet nur beim ersten Deploy, beendet sich dann.  
`restart: "no"` verhindert automatischen Neustart.

```yaml
command:
  - python3
  - -c
  - |
    import os
    path = "/config/external-services.yml"
    if os.path.exists(path):
        exit(0)   # Datei existiert bereits → nichts tun
    ...           # Sonst: Datei erstellen
```
Prüft ob die Konfigurationsdatei bereits vorhanden ist.  
Wenn ja → sofort beenden (idempotent – mehrfaches Ausführen schadet nicht).  
Wenn nein → `external-services.yml` mit Home Assistant Konfiguration erstellen.

---

## Container: `infra-web` + `php`

```yaml
image: nginx:alpine
volumes:
  - traefik-site:/usr/share/nginx/html:ro
```
Nginx als Webserver für die PHP-Seite. Liest Dateien aus dem `traefik-site` Volume.  
`:ro` = read-only, Nginx kann die Dateien nur lesen.

```yaml
image: php:8.3-fpm-alpine
volumes:
  - traefik-site:/srv/infra
```
PHP-FPM verarbeitet PHP-Dateien. Nginx leitet PHP-Anfragen per FastCGI weiter.  
Beide Container teilen das gleiche Volume mit den PHP-Dateien.

---

## Volumes

```yaml
volumes:
  traefik-certs:         # Let's Encrypt Zertifikate (acme.json)
  traefik-config-dynamic: # Externe Dienste (external-services.yml)
  traefik-site:          # PHP-Webseite (index.php)
```
Named Volumes – Docker verwaltet die Speicherorte selbst.  
Portainer-kompatibel (keine Bind-Mounts aus dem Git-Repo).  
Daten bleiben erhalten auch wenn Container neu gestartet oder geupdated werden.

---

## Netzwerk

```yaml
networks:
  traefik-proxy:
    name: traefik-proxy  # Fester Name – andere Stacks können es als external einbinden
    driver: bridge
```
Bridge-Netzwerk – Container im gleichen Netzwerk können sich per Name ansprechen.  
`name: traefik-proxy` sorgt dafür dass das Netzwerk immer gleich heißt,  
unabhängig vom Stack-Namen in Portainer.

Andere Stacks binden es so ein:
```yaml
networks:
  traefik-proxy:
    external: true  # Netzwerk existiert bereits, nicht neu erstellen
```

---

*Letzte Aktualisierung: 2025-05-07 – Claude*

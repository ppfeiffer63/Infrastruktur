# Traefik Stack

Haupt-Reverse-Proxy auf Basis von **Traefik v3** mit automatischen Let's Encrypt Zertifikaten und eingebautem Dashboard.  
Neue Dienste werden einfach per **Docker Labels** registriert – kein Neustart von Traefik nötig.

## Dienste

| Service | Image | Port | Beschreibung |
|---------|-------|------|-------------|
| `traefik` | traefik:v3.3 | 80, 443 | Reverse Proxy + Dashboard |
| `infra-web` | nginx:alpine | – | Webserver für PHP-Seite |
| `php` | php:8.3-fpm-alpine | – | PHP FastCGI |
| `site-init` | alpine | – | Init-Container (einmalig) |

## Domains

| URL | Beschreibung |
|-----|-------------|
| `https://traefik.home.pfeiffer-privat.de` | Traefik Dashboard (Basic Auth) |
| `https://infra.home.pfeiffer-privat.de` | PHP-Starter-Webseite |

## Netzwerk

Alle Traefik-verwalteten Container müssen im Netzwerk `traefik-proxy` sein und die entsprechenden Labels tragen.

---

## Setup

### 1. Basic Auth Hash generieren

```bash
# htpasswd installieren (falls nicht vorhanden)
apt install apache2-utils -y

# Hash generieren – $ muss als $$ escaped werden!
echo $(htpasswd -nb admin DEIN_PASSWORT) | sed -e 's/\$/\$\$/g'
# Ausgabe z.B.: admin:$$apr1$$xxxxx$$yyyyy
```

### 2. Stack in Portainer anlegen

```
Portainer → Stacks → Add Stack → Repository

  Name:           traefik
  Repository URL: https://github.com/DEIN-USERNAME/Infrastruktur.git
  Repository ref: refs/heads/main
  Compose path:   traefik/docker-compose.yml
  Authentication: Credential "github" auswählen
```

### 3. Environment Variables in Portainer

| Variable | Wert |
|----------|------|
| `TRAEFIK_ACME_EMAIL` | `deine@email.de` |
| `TRAEFIK_DASHBOARD_AUTH` | *(Hash aus Schritt 1)* |

### 4. Stack deployen → **Deploy the stack**

Traefik holt automatisch Let's Encrypt Zertifikate für alle konfigurierten Domains.

---

## Neuen Dienst hinzufügen

Jeden Docker-Container mit diesen Labels versehen:

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

networks:
  traefik-proxy:
    external: true
```

Traefik erkennt den Container **automatisch** und richtet das Routing + Zertifikat ein.

---

## Matrix-Stack via Traefik

Den Matrix-Stack mit Traefik-Labels erweitern:

```yaml
  synapse:
    networks:
      - traefik-proxy
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.synapse.rule=Host(`matrix.home.pfeiffer-privat.de`)"
      - "traefik.http.routers.synapse.entrypoints=websecure"
      - "traefik.http.routers.synapse.tls.certresolver=letsencrypt"
      - "traefik.http.services.synapse.loadbalancer.server.port=8008"

  element:
    networks:
      - traefik-proxy
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.element.rule=Host(`element.home.pfeiffer-privat.de`)"
      - "traefik.http.routers.element.entrypoints=websecure"
      - "traefik.http.routers.element.tls.certresolver=letsencrypt"
      - "traefik.http.services.element.loadbalancer.server.port=80"

networks:
  traefik-proxy:
    external: true
```

---

## Logs

```bash
docker logs -f traefik
docker logs -f infra-web
```

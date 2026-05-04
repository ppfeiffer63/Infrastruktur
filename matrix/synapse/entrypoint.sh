#!/bin/bash
set -e

CONFIG_FILE="/data/homeserver.yaml"
INIT_MARKER="/data/.initialized"

# -------------------------------------------------------
# Erster Start: Konfiguration generieren und anpassen
# -------------------------------------------------------
if [ ! -f "$INIT_MARKER" ]; then
    echo "==> Erster Start: Synapse wird initialisiert..."

    # 1. Basis-Konfiguration generieren
    echo "==> Generiere homeserver.yaml..."
    /start.py generate

    # 2. PostgreSQL-Konfiguration eintragen (SQLite ersetzen)
    echo "==> Konfiguriere PostgreSQL..."
    python3 - << PYEOF
import yaml

with open("$CONFIG_FILE", "r") as f:
    config = yaml.safe_load(f)

# PostgreSQL statt SQLite
config["database"] = {
    "name": "psycopg2",
    "args": {
        "user": "synapse",
        "password": "${POSTGRES_PASSWORD}",
        "database": "synapse",
        "host": "postgres",
        "cp_min": 5,
        "cp_max": 10,
    }
}

# Registrierung: Invitation-only per Token
config["enable_registration"] = True
config["registration_requires_token"] = True
config["allow_guest_access"] = False

# Federation deaktivieren
config["federation_domain_whitelist"] = []

# Trusted Key Server deaktivieren (kein externer Kontakt)
config["suppress_key_server_warning"] = True

with open("$CONFIG_FILE", "w") as f:
    yaml.dump(config, f, default_flow_style=False, allow_unicode=True)

print("==> homeserver.yaml erfolgreich konfiguriert.")
PYEOF

    # 3. Warten bis PostgreSQL bereit ist
    echo "==> Warte auf PostgreSQL..."
    until python3 -c "
import psycopg2, sys
try:
    psycopg2.connect(host='postgres', dbname='synapse', user='synapse', password='${POSTGRES_PASSWORD}')
    print('PostgreSQL bereit.')
except Exception as e:
    print(f'Warte... ({e})')
    sys.exit(1)
"; do
        sleep 2
    done

    # 4. Synapse kurz starten um DB zu initialisieren, dann Admin anlegen
    echo "==> Initialisiere Datenbank..."
    python3 -m synapse.app.homeserver \
        --config-path "$CONFIG_FILE" \
        --generate-keys 2>/dev/null || true

    # 5. Initialisierung abgeschlossen
    touch "$INIT_MARKER"
    echo "==> Initialisierung abgeschlossen."
else
    echo "==> Konfiguration bereits vorhanden, überspringe Initialisierung."
fi

# -------------------------------------------------------
# Synapse starten
# -------------------------------------------------------
echo "==> Starte Synapse..."
exec /start.py

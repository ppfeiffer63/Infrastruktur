FROM matrixdotorg/synapse:latest

# psycopg2 ist bereits im Synapse-Image enthalten
# PyYAML ebenfalls – kein zusätzlicher pip install nötig

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]

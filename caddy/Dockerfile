# Caddy mit caddy-security Plugin – selbst gebaut via xcaddy
# Basiert auf dem offiziellen Caddy-Builder-Image

FROM caddy:builder AS builder

RUN xcaddy build \
    --with github.com/greenpau/caddy-security

FROM caddy:latest

COPY --from=builder /usr/bin/caddy /usr/bin/caddy

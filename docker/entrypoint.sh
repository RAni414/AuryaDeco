#!/bin/sh
# AuryaDeco : démarrage du conteneur.
set -eu

UPLOADS=/var/www/html/uploads

if [ ! -w "$UPLOADS" ]; then
    echo "[auryadeco] WARNING: $UPLOADS is not writable by user $(id -un)." >&2
    echo "[auryadeco] Mount it as an EasyPanel *Volume* (not a bind mount), or chown it to uid $(id -u)." >&2
fi
# Le volume peut masquer le .htaccess de l'image : on le remet.
if [ -w "$UPLOADS" ] && [ ! -f "$UPLOADS/.htaccess" ]; then
    cp /opt/auryadeco/uploads.htaccess "$UPLOADS/.htaccess"
fi

php /opt/auryadeco/install-cli.php

exec "$@"

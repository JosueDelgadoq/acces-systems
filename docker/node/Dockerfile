FROM node:22-alpine

ARG WWWUSER=1000
ARG WWWGROUP=1000

WORKDIR /var/www/html

ENV HOME=/tmp
ENV NPM_CONFIG_CACHE=/tmp/npm-cache

RUN if ! grep -q "^[^:]*:[^:]*:${WWWGROUP}:" /etc/group; then addgroup -g "${WWWGROUP}" sail; fi \
    && GROUP_NAME="$(awk -F: -v gid="${WWWGROUP}" '$3 == gid { print $1; exit }' /etc/group)" \
    && if ! grep -q "^[^:]*:[^:]*:${WWWUSER}:" /etc/passwd; then adduser -D -G "${GROUP_NAME}" -u "${WWWUSER}" sail; fi \
    && mkdir -p /var/www/html/node_modules /tmp/npm-cache \
    && chown -R "${WWWUSER}:${WWWGROUP}" /var/www/html /tmp/npm-cache

USER ${WWWUSER}:${WWWGROUP}

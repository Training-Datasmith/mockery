#!/usr/bin/env bash
set -euo pipefail

export DEBIAN_FRONTEND=noninteractive
export COMPOSER_ALLOW_SUPERUSER=1
export COMPOSER_HOME=/tmp/composer
mkdir -p "${COMPOSER_HOME}"

COMPOSER_URL='https://getcomposer.org/download/2.8.10/composer.phar'
COMPOSER_SHA256='28dbb6bd8bef31479c7985b774c130a8bda37dbe63c35b56f6cb6bc377427573'
PHPREDIS_URL='https://pecl.php.net/get/redis-5.3.7.tgz'
PHPREDIS_SHA256='b958166ccda4f40bd17c6998f9e2239021ae644467cd8ad5c15def420aad65b0'

php -r '
if (version_compare(PHP_VERSION, "7.3.33", "!=")) {
    fwrite(STDERR, "Expected PHP 7.3.33, got ".PHP_VERSION.PHP_EOL);
    exit(1);
}
'

if ! command -v git >/dev/null 2>&1 || ! command -v unzip >/dev/null 2>&1; then
  export DEBIAN_FRONTEND=noninteractive
  # Bullseye security mirrors may 404 on this image; install git/unzip from main only.
  printf 'deb http://deb.debian.org/debian bullseye main\n' >/etc/apt/sources.list
  rm -f /etc/apt/sources.list.d/debian.sources 2>/dev/null || true
  apt-get update
  apt-get install -y --no-install-recommends --no-upgrade git unzip
fi

PHPREDIS_BUILT=0
PHPREDIS_REASON=""
if curl -fsSL -o /tmp/redis-5.3.7.tgz "${PHPREDIS_URL}"; then
  if echo "${PHPREDIS_SHA256}  /tmp/redis-5.3.7.tgz" | sha256sum -c -; then
    set +e
    printf '\n' | pecl install /tmp/redis-5.3.7.tgz >/tmp/pecl-redis.log 2>&1
    PECL_STATUS=$?
    set -e
    if [[ "${PECL_STATUS}" -eq 0 ]]; then
      docker-php-ext-enable redis 2>/dev/null || true
    fi
    if php -m | grep -qi '^redis$' && php -r 'if (!class_exists("Redis")) { exit(1);}'; then
      PHPREDIS_BUILT=1
      echo "phpredis: enabled"
    else
      PHPREDIS_REASON="pecl install failed or Redis class unavailable"
      tail -n 50 /tmp/pecl-redis.log >&2 || true
    fi
  else
    PHPREDIS_REASON="phpredis tarball checksum mismatch"
  fi
else
  PHPREDIS_REASON="could not download phpredis tarball"
fi

echo "PHPREDIS_BUILT=${PHPREDIS_BUILT}"
if [[ "${PHPREDIS_BUILT}" -ne 1 ]]; then
  echo "PHPREDIS_REASON=${PHPREDIS_REASON}"
fi

curl -fsSL -o /tmp/composer.phar "${COMPOSER_URL}"
echo "${COMPOSER_SHA256}  /tmp/composer.phar" | sha256sum -c -

git config --global --add safe.directory /app
cd /app

php /tmp/composer.phar update --no-interaction --prefer-dist --no-progress

echo "=== composer audit ==="
AUDIT_OUT="$(mktemp)"
set +e
php /tmp/composer.phar audit --no-interaction >"${AUDIT_OUT}" 2>&1
AUDIT_STATUS=$?
set -e
cat "${AUDIT_OUT}"
if [[ "${AUDIT_STATUS}" -ne 0 ]]; then
  exit "${AUDIT_STATUS}"
fi
if ! grep -Fxq 'No security vulnerability advisories found.' "${AUDIT_OUT}"; then
  echo "composer audit: missing exact success line" >&2
  exit 1
fi
rm -f "${AUDIT_OUT}"

php scripts/check-cluster-pins.php

vendor/bin/phpunit --do-not-cache-result --colors=never 2>&1 | tee /app/.system-test/phpunit.out

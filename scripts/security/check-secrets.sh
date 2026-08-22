#!/usr/bin/env sh
set -eu
if grep -RInE '(sk_live_|AKIA[0-9A-Z]{16}|-----BEGIN (RSA|EC|OPENSSH) PRIVATE KEY-----)' . --exclude-dir=.git --exclude='*.zip' --exclude='check-secrets.sh'; then
  echo 'Potential committed secret detected.' >&2; exit 1
fi
echo 'Basic secret scan passed.'

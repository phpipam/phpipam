#!/bin/bash

export VAULT_ADDR=${VAULT_ADDR:-"https://vault.umusic.net"}

export VAULT_TOKEN=$(vault login -token-only -method=aws role=network-engineering-phpipam-reader)

if [[ -z "$VAULT_TOKEN" ]]; then
  echo "VAULT_TOKEN required" >&2
  exit 1
fi

exportsecrets() {
  VAULT_KV=$(echo $1 | awk -F'/' '{print $1}')
  VAULT_KV_PATH=$(echo $1 | sed "s,$VAULT_KV/,,g")
  VAULT_OUTPUT=`curl -s -H "X-Vault-Token: ${VAULT_TOKEN}" -X GET $VAULT_ADDR/v1/${VAULT_KV}/data/${VAULT_KV_PATH}`
  if [[ "$(echo $VAULT_OUTPUT | jq -r '.errors')" != "null" ]]; then
    echo Error from Vault $VAULT_ADDR/v1/${VAULT_KV}/data/${VAULT_KV_PATH}
    echo $VAULT_OUTPUT
    exit 1
  fi
  export $(echo $VAULT_OUTPUT | jq -r '.data.data | to_entries|map("\(.key)=\(.value|tostring)")|.[]')
}

exportsecrets $VAULT_KV_PATH

/sbin/tini -- /bin/sh -c /start_crond

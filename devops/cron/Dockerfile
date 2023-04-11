FROM phpipam/phpipam-cron:latest

RUN apk add curl jq bash && curl -L -O https://releases.hashicorp.com/vault/1.5.4/vault_1.5.4_linux_amd64.zip && \
    unzip vault_1.5.4_linux_amd64.zip && rm vault_1.5.4_linux_amd64.zip && mv vault /usr/local/bin

COPY . /phpipam/
COPY devops/cron/cron-entrypoint.sh /opt/cron-entrypoint.sh

ENTRYPOINT [ "/opt/cron-entrypoint.sh" ]

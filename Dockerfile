FROM phpipam/phpipam-www:latest

RUN apk add curl jq bash php7-mcrypt && curl -L -O https://releases.hashicorp.com/vault/1.5.4/vault_1.5.4_linux_amd64.zip && \
    unzip vault_1.5.4_linux_amd64.zip && rm vault_1.5.4_linux_amd64.zip && mv vault /usr/local/bin

COPY . /phpipam/
COPY entrypoint.sh /opt/entrypoint.sh

ENTRYPOINT [ "/opt/entrypoint.sh" ]

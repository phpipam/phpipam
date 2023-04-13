FROM phpipam/phpipam-cron:latest

RUN apk add curl jq bash 

COPY . /phpipam/
COPY devops/cron/cron-entrypoint.sh /opt/cron-entrypoint.sh

ENTRYPOINT [ "/opt/cron-entrypoint.sh" ]

FROM phpipam/phpipam-www:1.5x

RUN apk add curl jq bash php7-mcrypt 

COPY . /phpipam/
COPY entrypoint.sh /opt/entrypoint.sh

ENTRYPOINT [ "/opt/entrypoint.sh" ]

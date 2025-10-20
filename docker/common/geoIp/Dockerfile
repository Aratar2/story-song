# syntax=docker/dockerfile:1
FROM alpine:3.15.0

RUN apk add --no-cache curl

ADD ./docker/common/geoIp/ /geoIp

RUN chmod +x /geoIp/update-geoip-db.sh \
    && echo '00 09 * * 1-5    sh /geoIp/update-geoip-db.sh' > /etc/crontabs/root

CMD ["sh", "/geoIp/start.sh"]

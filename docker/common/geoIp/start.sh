#!/bin/sh

crond -f -d 8 &

sh /geoIp/update-geoip-db.sh

/geoIp/GeoIpMaxmind
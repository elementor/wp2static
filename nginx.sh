set -e

echo "Starting NGINX up on localhost:7000"
nginx -p "$PWD" -c "nginx.conf" -e "stderr"

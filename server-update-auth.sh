#!/usr/bin/env bash
# Проверка auth после обновления на СЕРВЕРЕ (алиас к server-check.sh).
exec bash "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/server-check.sh"

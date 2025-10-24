#!/usr/bin/env bash
set -euo pipefail

BASE_URL="http://sphynx.local.mermaid:8081"
TARGETS=("/" "/adoption" "/sphynx")
LOG_DIR="logs"
mkdir -p "$LOG_DIR"
LOG_FILE="$LOG_DIR/perf.txt"
TIMESTAMP="$(date -Iseconds)"

format_line() {
  local path="$1"
  local namelookup="$2"
  local connect="$3"
  local starttransfer="$4"
  local total="$5"
  local cache_status="$6"
  printf "%s\t%s\tnamelookup=%.4fs\tconnect=%.4fs\tttfb=%.4fs\ttotal=%.4fs\tX-Cache=%s\n" \
    "$TIMESTAMP" "$path" "$namelookup" "$connect" "$starttransfer" "$total" "$cache_status"
}

for path in "${TARGETS[@]}"; do
  tmp_headers="$(mktemp)"
  read -r namelookup connect starttransfer total < <(curl -sS "$BASE_URL$path" \
    -o /dev/null \
    -D "$tmp_headers" \
    -w "%{time_namelookup} %{time_connect} %{time_starttransfer} %{time_total}\n")
  cache_status="$(grep -i "^X-Cache-Status:" "$tmp_headers" | awk '{print $2}' | tr -d '\r' || true)"
  rm -f "$tmp_headers"
  line="$(format_line "$path" "$namelookup" "$connect" "$starttransfer" "$total" "${cache_status:-MISS}")"
  echo "$line" | tee -a "$LOG_FILE"
done

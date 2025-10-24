#!/usr/bin/env bash
set -euo pipefail

BASE_URL=${BASE_URL:-"http://sphynx.local.mermaid:8081"}
TARGETS=("/" "/adoption" "/sphynx")
RUNS=${RUNS:-3}
LOG_DIR=${LOG_DIR:-logs}
OUTPUT_FILE=${OUTPUT_FILE:-"$LOG_DIR/perf.txt"}
LABEL=${LABEL:-$(date -Iseconds)}
mkdir -p "$LOG_DIR"

for path in "${TARGETS[@]}"; do
  metrics=""
  statuses=()
  for ((i=1; i<=RUNS; i++)); do
    tmp_headers=$(mktemp)
    line=$(curl -sS "$BASE_URL$path" \
      -o /dev/null \
      -D "$tmp_headers" \
      -w "%{time_namelookup} %{time_connect} %{time_starttransfer} %{time_total}\n")
    metrics+="$line"$'\n'
    status=$(grep -i "^X-Cache-Status:" "$tmp_headers" | awk '{print $2}' | tr -d '\r' || true)
    statuses+=("${status:-MISS}")
    rm -f "$tmp_headers"
  done
  averages=$(printf "%s" "$metrics" | awk -v runs="$RUNS" '
    NF==4 {nl+=$1; ct+=$2; ttfb+=$3; total+=$4; count++}
    END {
      if (count != runs) {
        printf "ERROR %d\n", count;
        exit 1;
      }
      printf "%.6f %.6f %.6f %.6f", nl/count, ct/count, ttfb/count, total/count;
    }')
  if [[ "$averages" == ERROR* ]]; then
    echo "Failed to capture expected samples for $path" >&2
    exit 1
  fi
  read -r avg_namelookup avg_connect avg_ttfb avg_total <<<"$averages"
  status_summary=$(IFS=','; echo "${statuses[*]}")
  printf "%s\t%s\tnamelookup=%.4fs\tconnect=%.4fs\tttfb=%.4fs\ttotal=%.4fs\tcache_runs=%s\n" \
    "$LABEL" "$path" "$avg_namelookup" "$avg_connect" "$avg_ttfb" "$avg_total" "$status_summary" | tee -a "$OUTPUT_FILE"
done

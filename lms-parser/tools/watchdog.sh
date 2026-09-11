#!/usr/bin/env bash
# Страховка от «тихой смерти» прогона.
#
# Прогон умеет выглядеть живым, ничего не делая: процесс на месте, лог пишется, а
# счётчик уроков и объём downloads не растут часами. Поэтому сторож смотрит не в лог,
# а на результат — число закрытых уроков в state.json и размер каталога загрузок.
#
# Использование: tools/watchdog.sh [PID-файл] [минут простоя] [интервал опроса, с]
# Печатает строки-события в stdout; при простое или смерти процесса выходит с кодом 1.
set -u

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
pid_file="${1:-$root/logs/run.pid}"
stall_minutes="${2:-15}"
interval="${3:-60}"
state="${LMS_STATE_FILE:-$root/state.json}"
downloads="${LMS_OUTPUT_DIR:-$root/downloads}"

progress() {
    local done_count bytes
    done_count=$(php -r '
        $f = $argv[1];
        $s = is_file($f) ? json_decode((string)file_get_contents($f), true) : null;
        $n = 0;
        foreach (($s["disciplines"] ?? []) as $d) {
            foreach (($d["lessons"] ?? []) as $l) { if (!empty($l["done"])) $n++; }
        }
        echo $n;
    ' "$state" 2>/dev/null || echo 0)
    bytes=$(du -sb "$downloads" 2>/dev/null | cut -f1)
    echo "${done_count:-0} ${bytes:-0}"
}

read -r last_done last_bytes <<<"$(progress)"
last_change=$(date +%s)
echo "watchdog: слежу за $state и $downloads, порог простоя ${stall_minutes} мин (старт: $last_done уроков, $last_bytes Б)"

while :; do
    sleep "$interval"

    if [ -f "$pid_file" ] && ! kill -0 "$(cat "$pid_file")" 2>/dev/null; then
        echo "watchdog: ПРОГОН УМЕР — процесс из $pid_file больше не существует"
        exit 1
    fi

    read -r cur_done cur_bytes <<<"$(progress)"
    now=$(date +%s)

    if [ "$cur_done" -gt "$last_done" ] || [ "$cur_bytes" -gt "$last_bytes" ]; then
        last_done=$cur_done
        last_bytes=$cur_bytes
        last_change=$now
        continue
    fi

    idle=$(( (now - last_change) / 60 ))
    if [ "$idle" -ge "$stall_minutes" ]; then
        echo "watchdog: ПРОСТОЙ ${idle} мин — ни уроков (${cur_done}), ни байтов (${cur_bytes}) не прибавилось"
        exit 1
    fi
done

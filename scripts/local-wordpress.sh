#!/usr/bin/env bash
# Persistent local WordPress for Siirtymä – Lohkareet.
# Do not use an anonymous Playground temp site for editorial work.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
LOCAL="$ROOT/.local"
WP="$LOCAL/wordpress"
RUN="$LOCAL/run"
BACKUPS="$LOCAL/backups"
THEME="$ROOT/theme"
DEV="$ROOT/dev"
BLUEPRINT="$DEV/playground-blueprint.json"
PIDFILE="$RUN/playground.pid"
LOG="$RUN/playground.log"
PORT="${PORT:-9400}"
URL="http://127.0.0.1:${PORT}"

mkdir -p "$WP" "$RUN" "$BACKUPS"

playground() {
	npx --yes @wp-playground/cli@latest "$@"
}

install_mode() {
	if [[ -f "$WP/wp-load.php" ]]; then
		printf '%s' "install-from-existing-files-if-needed"
	else
		printf '%s' "download-and-install"
	fi
}

pid_alive() {
	local pid="${1:-}"
	[[ -n "$pid" ]] && kill -0 "$pid" 2>/dev/null
}

read_pid() {
	if [[ -f "$PIDFILE" ]]; then
		tr -d '[:space:]' <"$PIDFILE"
	fi
}

kill_tree() {
	local pid="$1"
	local child
	for child in $(pgrep -P "$pid" 2>/dev/null || true); do
		kill_tree "$child"
	done
	kill "$pid" 2>/dev/null || true
}

port_pids() {
	lsof -nP -iTCP:"$PORT" -sTCP:LISTEN -t 2>/dev/null || true
}

cmd_status() {
	local pid
	pid="$(read_pid || true)"
	echo "Site URL:      $URL"
	echo "WordPress:     $WP"
	echo "Database:      $WP/wp-content/database"
	echo "Uploads:       $WP/wp-content/uploads"
	echo "Backups:       $BACKUPS"
	echo "Theme source:  $THEME"
	if pid_alive "${pid:-}"; then
		echo "Server:        running (pid $pid)"
	else
		echo "Server:        stopped"
	fi
	local listeners
	listeners="$(port_pids | tr '\n' ' ')"
	if [[ -n "${listeners// /}" ]]; then
		echo "Port $PORT:     listening ($listeners)"
	else
		echo "Port $PORT:     free"
	fi
}

wait_ready() {
	local i
	for i in $(seq 1 180); do
		if curl -fsS -o /dev/null --max-time 5 "$URL/" 2>/dev/null; then
			return 0
		fi
		if ! pid_alive "$(read_pid || true)"; then
			echo "Playground exited before the site was ready. See $LOG" >&2
			return 1
		fi
		sleep 2
	done
	echo "Timed out waiting for $URL. See $LOG" >&2
	return 1
}

cmd_start() {
	local pid listeners
	pid="$(read_pid || true)"
	if pid_alive "${pid:-}"; then
		echo "Already running (pid $pid) at $URL"
		return 0
	fi
	listeners="$(port_pids || true)"
	if [[ -n "${listeners// /}" ]]; then
		echo "Port $PORT is already in use (pid $listeners)." >&2
		echo "Stop that process before starting the persistent site." >&2
		echo "Do not start a second anonymous Playground server for editorial work." >&2
		return 1
	fi

	local mode
	mode="$(install_mode)"
	echo "Starting persistent WordPress ($mode)"
	echo "Stored at $WP"

	# A new session so the server survives after this script exits.
	python3 - "$LOG" "$ROOT" "$PORT" "$mode" "$BLUEPRINT" "$WP" "$THEME" >"$PIDFILE" <<'PY'
import subprocess, sys
log_path, root, port, mode, blueprint, wp, theme = sys.argv[1:]
log = open(log_path, "ab", buffering=0)
proc = subprocess.Popen(
    [
        "npx", "--yes", "@wp-playground/cli@latest", "server",
        "--php=8.3",
        f"--port={port}",
        "--login",
        "--verbosity=normal",
        f"--wordpress-install-mode={mode}",
        f"--blueprint={blueprint}",
        f"--mount-before-install={wp}:/wordpress",
        f"--mount={theme}:/wordpress/wp-content/themes/lohkareet",
    ],
    cwd=root,
    stdin=subprocess.DEVNULL,
    stdout=log,
    stderr=subprocess.STDOUT,
    start_new_session=True,
)
print(proc.pid)
PY

	wait_ready
	echo "Ready at $URL"
	echo "Log: $LOG"
}

cmd_stop() {
	local pid
	pid="$(read_pid || true)"
	if pid_alive "${pid:-}"; then
		echo "Stopping pid $pid"
		kill_tree "$pid"
		local i
		for i in $(seq 1 30); do
			if ! pid_alive "$pid"; then
				break
			fi
			sleep 1
		done
		if pid_alive "$pid"; then
			kill -9 "$pid" 2>/dev/null || true
		fi
	fi
	rm -f "$PIDFILE"
	local left
	left="$(port_pids || true)"
	if [[ -n "${left// /}" ]]; then
		echo "Port $PORT is still in use (pid $left)." >&2
		return 1
	fi
	echo "Stopped."
}

cmd_bootstrap() {
	if [[ ! -f "$WP/wp-load.php" ]]; then
		echo "WordPress is not installed yet. Run npm run wp:start first." >&2
		return 1
	fi
	echo "Applying the versioned content fixture."
	(
		cd "$ROOT"
		playground php \
			--php=8.3 \
			--site-url="$URL" \
			--verbosity=normal \
			--wordpress-install-mode=install-from-existing-files-if-needed \
			--mount-before-install="$WP:/wordpress" \
			--mount="$THEME:/wordpress/wp-content/themes/lohkareet" \
			--mount="$DEV:/bootstrap" \
			-- /bootstrap/bootstrap.php
	)
}

cmd_backup() {
	if [[ ! -d "$WP/wp-content/database" ]]; then
		echo "No database to back up yet." >&2
		return 1
	fi
	local stamp dest
	stamp="$(date +%Y%m%d-%H%M%S)"
	dest="$BACKUPS/$stamp"
	mkdir -p "$dest"
	cp -a "$WP/wp-content/database" "$dest/database"
	if [[ -d "$WP/wp-content/uploads" ]]; then
		cp -a "$WP/wp-content/uploads" "$dest/uploads"
	else
		mkdir -p "$dest/uploads"
	fi
	if [[ -f "$WP/wp-config.php" ]]; then
		cp -a "$WP/wp-config.php" "$dest/wp-config.php"
	fi
	echo "Backup: $dest"
}

cmd_restore() {
	local src="${1:-}"
	if [[ -z "$src" ]]; then
		echo "Usage: npm run wp:restore -- .local/backups/<timestamp>" >&2
		return 1
	fi
	if [[ ! -d "$src/database" ]]; then
		echo "No database directory in $src" >&2
		return 1
	fi
	local pid
	pid="$(read_pid || true)"
	if pid_alive "${pid:-}" || [[ -n "$(port_pids || true)" ]]; then
		echo "Stop the server before restoring." >&2
		return 1
	fi
	rm -rf "$WP/wp-content/database"
	cp -a "$src/database" "$WP/wp-content/database"
	rm -rf "$WP/wp-content/uploads"
	if [[ -d "$src/uploads" ]]; then
		cp -a "$src/uploads" "$WP/wp-content/uploads"
	else
		mkdir -p "$WP/wp-content/uploads"
	fi
	if [[ -f "$src/wp-config.php" ]]; then
		cp -a "$src/wp-config.php" "$WP/wp-config.php"
	fi
	echo "Restored from $src"
	echo "Start the site with npm run wp:start"
}

usage() {
	echo "Usage: scripts/local-wordpress.sh start|stop|restart|status|bootstrap|backup|restore [backup-dir]" >&2
	return 1
}

command="${1:-}"
shift || true

case "$command" in
	start) cmd_start ;;
	stop) cmd_stop ;;
	restart)
		cmd_stop || true
		cmd_start
		;;
	status) cmd_status ;;
	bootstrap) cmd_bootstrap ;;
	backup) cmd_backup ;;
	restore) cmd_restore "${1:-}" ;;
	*) usage ;;
esac

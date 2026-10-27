#!/usr/bin/env bash
set -euo pipefail

# Run from the iOS repository root. Source defaults to this extracted delivery.
PIANOLIT_SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
exec "${PIANOLIT_HANDOFF_PYTHON:-python3}" "$PIANOLIT_SCRIPT_DIR/sync_api_handoff.py" "$@"

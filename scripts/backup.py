#!/usr/bin/env python3
"""
Hassence backup -> GitHub

Exports the live WordPress database and content to XML, commits them to the
local git repo (respecting .gitignore) and pushes to origin.

The DB dump and XML are gitignored, so this script writes them to a
BACKUP_DIR, commits a CHANGELOG entry + the timestamp, and pushes.

Required environment variables:
  HASSENCE_DB_HOST     e.g. localhost or mysql.example.com
  HASSENCE_DB_NAME     e.g. hassence_wp589
  HASSENCE_DB_USER     DB user
  HASSENCE_DB_PASS     DB password
  HASSENCE_SSH_KEY     path to an SSH private key used for the git push
                       (optional; falls back to HTTPS + cached PAT)

Usage:
  python scripts/backup.py "Optional commit message"
"""

import os
import subprocess
import sys
import datetime
import shutil
import tempfile
from pathlib import Path

REPO_ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
BACKUP_DIR = os.path.join(REPO_ROOT, "backups")
CHANGELOG = os.path.join(REPO_ROOT, "BACKUP_CHANGELOG.md")


def load_env():
    """Load BACKUP_DIR/.env if present (KEY=VALUE lines)."""
    env_file = Path(REPO_ROOT) / "backups" / ".env"
    if env_file.exists():
        for line in env_file.read_text().splitlines():
            line = line.strip()
            if not line or line.startswith("#") or "=" not in line:
                continue
            k, v = line.split("=", 1)
            os.environ.setdefault(k.strip(), v.strip())


load_env()


def run(cmd, **kwargs):
    print("+ " + " ".join(cmd))
    return subprocess.run(cmd, check=True, **kwargs)


def export_db():
    host = os.environ["HASSENCE_DB_HOST"]
    name = os.environ["HASSENCE_DB_NAME"]
    user = os.environ["HASSENCE_DB_USER"]
    pw = os.environ["HASSENCE_DB_PASS"]

    os.makedirs(BACKUP_DIR, exist_ok=True)
    dump = os.path.join(BACKUP_DIR, f"hassence_db_{datetime.datetime.now():%Y%m%d_%H%M%S}.sql")

    cmd = [
        "mysqldump",
        "--host=" + host,
        "--user=" + user,
        "--password=" + pw,
        "--single-transaction",
        "--lock-tables=false",
        name,
    ]
    with open(dump, "w") as f:
        run(cmd, stdout=f, stderr=subprocess.PIPE)
    print("DB dump written to", dump)
    return dump


def export_xml():
    """WordPress content export via WP-CLI if available."""
    os.makedirs(BACKUP_DIR, exist_ok=True)
    out = os.path.join(BACKUP_DIR, f"hassence_content_{datetime.datetime.now():%Y%m%d_%H%M%S}.xml")
    try:
        run(["wp", "export", "--filename=" + out], cwd=REPO_ROOT)
        print("Content export written to", out)
        return out
    except FileNotFoundError:
        print("wp not found - skipping content export")
        return None
    except subprocess.CalledProcessError as e:
        print("wp export failed:", e.stderr.decode() if e.stderr else e)
        return None


def commit_and_push(message):
    env = os.environ.copy()
    key = env.pop("HASSENCE_SSH_KEY", None)
    if key:
        env["GIT_SSH_COMMAND"] = f"ssh -i {key} -o StrictHostKeyChecking=no"

    run(["git", "add", "-A"], cwd=REPO_ROOT)
    try:
        run(["git", "commit", "-m", message], cwd=REPO_ROOT)
    except subprocess.CalledProcessError as e:
        if "nothing to commit" in (e.stderr or b"").decode():
            print("Nothing to commit")
        else:
            raise
    run(["git", "push"], cwd=REPO_ROOT, env=env)


def main():
    msg = sys.argv[1] if len(sys.argv) > 1 else f"Backup {datetime.datetime.now():%Y-%m-%d %H:%M}"
    export_db()
    export_xml()

    with open(CHANGELOG, "a") as f:
        f.write(f"- {datetime.datetime.now():%Y-%m-%d %H:%M} - {msg}\n")

    commit_and_push(msg)
    print("Backup complete.")


if __name__ == "__main__":
    main()
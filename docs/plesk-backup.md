# Plesk Backup Task

To keep database and uploaded files safe, configure a nightly backup task in Plesk.

1. **Create a script** (e.g. `backup.sh`) on the server:
   ```bash
   #!/bin/bash
   TS=$(date +%F)
   BACKUP_DIR=/var/www/vhosts/example.com/backups
   mkdir -p "$BACKUP_DIR"
   mysqldump -u$MYSQL_USER -p$MYSQL_PASSWORD $MYSQL_DATABASE > "$BACKUP_DIR/db_$TS.sql"
   tar -czf "$BACKUP_DIR/files_$TS.tar.gz" /var/www/vhosts/example.com/backend/storage/app
   find "$BACKUP_DIR" -type f -mtime +30 -delete
   ```

2. **Add scheduled task** in Plesk:
   - Go to **Tools & Settings → Scheduled Tasks**.
   - Add a task running the script: `/bin/bash /path/to/backup.sh`.
   - Schedule it to run nightly (e.g. at 02:00).

3. Ensure the task user has access to the project directories and database credentials are set via environment variables.

This keeps daily backups of the database and uploaded files with a 30‑day retention.

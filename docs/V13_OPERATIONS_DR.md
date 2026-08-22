# V13 Production Operations, Alerting & Disaster Recovery

## Implemented
- Database, Redis and AI-service health monitoring.
- Persistent health-check history and incident lifecycle.
- Alert delivery through admin-configured webhook/email.
- Super Admin operations dashboard.
- PostgreSQL backup and restore scripts with retention and SHA-256 checksum generation.

## Required production setup
1. Configure **Operations & alerting** in Super Admin → Settings.
2. Configure Laravel's real transactional mail transport in the deployment secret store.
3. Run `php artisan migrate --force`.
4. Ensure scheduler and queue containers remain running.
5. Run `scripts/backup_postgres.sh` from a trusted backup worker with least-privilege DB credentials.
6. Copy encrypted backups to an independent, access-controlled storage account. Do not keep the only backup on the production host.
7. Test restore regularly using `scripts/restore_postgres.sh` against an isolated database.

## Recovery target
Document your RPO/RTO with your provider and business requirements. Verify restore drills; a backup is not considered valid until a restore has succeeded.

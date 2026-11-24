# Disaster Recovery & Business Continuity

## RTO / RPO
- RTO: 15 minutes (service restoration)
- RPO: 5 minutes (data loss tolerance)

## Recovery flow
```mermaid
flowchart TD
    Incident --> Detect[Monitoring Alert]
    Detect --> Failover[Failover to standby]
    Failover --> Restore[Restore from backup]
    Restore --> Verify[Health check]
    Verify --> Live[Service live]
```

## Backup strategy
- MongoDB: daily full + hourly incremental (see infra/docker/mongodb)
- Redis: RDB snapshots every 5 min
- Files: replicated to object storage

## References
- Infra: infra/docker/, infra/compose.yaml
- Monitoring: infra/docker/monitoring/
- CI/CD: .github/workflows/
- API: docs/API.md
- DB: docs/DB.md

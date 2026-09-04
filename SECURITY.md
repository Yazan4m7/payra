# Security checklist

This project handles payroll, national identifiers, SSC identifiers and bank details. Before production:

- enforce HTTPS and `SESSION_SECURE_COOKIE=true`;
- keep tenant domains on host-only cookies and retain `ScopeSessions`;
- use Redis for tenant-tagged cache or replace the cache bootstrapper with an isolation strategy you have tested;
- use least-privilege central and tenant DB credentials;
- encrypt backups and protect object/file storage;
- never log passwords, national IDs, IBANs, salary payloads or full payslip snapshots;
- run the tenant-isolation integration test against the same DB engine used in production;
- enable queue supervision and scheduler monitoring;
- review authorization whenever adding a new central or tenant route;
- create a new effective-dated compliance version for legal changes rather than editing historical versions.

The source cannot make a deployment secure by itself; reverse proxy, DNS, TLS, DB grants, Redis exposure, backups and observability must be configured securely as well.

# CHANGELOG

## [Unreleased]

### Added
- Complete E2E testnet validation evidence system
- Database schema with timestamptz conversion (41 columns)
- Orders table with idempotency unique constraints
- AI configuration with GPT-4o runtime enforcement
- Comprehensive code style compliance (Pint)
- Deploy guard with production safety checks
- Ubuntu 24.04 systemd service configuration

### Fixed
- All timestamp columns converted to timestamptz for UTC compliance
- AI model enforcement (GPT-4o) with runtime override
- Code style violations (StopCalculator, Factory files)
- Database idempotency infrastructure implementation

### Security
- ENV file integrity monitoring with SHA256
- Testnet URL enforcement across all external APIs
- Token masking in logs and reports
- Unique constraint enforcement at database level

### Changed
- Migration system enhanced with rollback capabilities
- Evidence verification system from NO-GO to GO status
- Quality gates now passing: PHPStan=0, Pint=PASS, TODO=0

## [Previous Releases]
See git history for detailed commit information.


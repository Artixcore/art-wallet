# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Package documentation: `README.md`, `CHANGELOG.md`, `UPGRADE.md`.
- Eloquent model factories for core catalog models (`Tenant`, `Team`, `Role`, `Permission`) under `database/factories/`.
- Unit smoke test `PackageModelFactoryTest` for factory wiring.

### Fixed

- PHPUnit 12 group filtering: security-related tests now declare `#[Group('security')]` so `composer test:security` executes all intended cases (including feature coverage).

### Notes

- Tag your first release when ready; replace `[Unreleased]` with a version section (e.g. `## [1.0.0] - YYYY-MM-DD`).

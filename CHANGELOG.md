# Changelog

All notable changes to this project will be documented in this file.

## [0.8.0]

Added
- Changed php version to 8.3 in docker container and removed opcache. 
- Continuous Integration.

Fixed
- Update dependency and change signature.
  from `Zjk\SqlTwig\Contract\SqlTwigInterface->transaction(\Closure $func, int $transactionIsolationLevel = TransactionIsolationLevel::READ_COMMITTED): ?Result` 
  to `Zjk\SqlTwig\Contract\SqlTwigInterface->transaction(\Closure $func, TransactionIsolationLevel $transactionIsolationLevel = TransactionIsolationLevel::READ_COMMITTED): ?Result`

## [0.7.1]
Added
- New functionality query execution via transaction Zjk\SqlTwig\Contract\SqlTwigInterface->transaction(\Closure $func, int $transactionIsolationLevel = TransactionIsolationLevel::READ_COMMITTED): ?Result

## [0.6.1]
Added
- Description in makefile 
- Psalm
- Phpmd
- Pdepend
- Phploc
 
Fixed
- Static analyzes
- Dependency
- Rules for php-cs-fixer

## [0.6.0]
Initial version
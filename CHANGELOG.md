# Changelog

All notable changes to this project will be documented in this file.

## [0.10.0]

Added
- the possibility to create and execute queries with temporary table has been added.

## [0.9.1]

Fixed
- Dependency for older version of Symfony.


## [0.9.0]

Fixed
- support for working with "doctrine/dbal" version 3 and 4 at the same time, due to the change of attribute signature `$transactionIsolationLevel`. 

## [0.8.1]
Added
- Optimize code for Continuous Integration in python.

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
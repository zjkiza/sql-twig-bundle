# Sql Twig Bundle

The bundle executes raw SQL queries with the flexibility to embed Twig extensions, enabling the dynamic creation of
queries using Twig syntax.

# About the bundle

- You can use Twig syntax when creating queries.
- You place queries in separate files. (Ex: all_media.sql.twig).
- Execute your queries using `Zjk\SqlTwig\Contract\SqlTwigInterface` service.
- Result of execution `Zjk\SqlTwig\Contract\SqlTwigInterface->executeQuery(..)` is instance of Doctrine\DBAL\Driver\Result, use their methods to get results.
- Query execution via transaction `Zjk\SqlTwig\Contract\SqlTwigInterface->transaction(..)`

# Installation

Add "zjkiza/sql-twig-bundle" to your composer.json file:

```
composer require zjkiza/sql-twig-bundle
```

## Symfony integration

Bundle wires up all classes together and provides method to easily setup.

1. Register bundle within your configuration (i.e: `bundles.php`).

   ```php
   <?php
   
   declare(strict_types=1);
   
   return [
       // other bundles
       Zjk\SqlTwig\ZJKizaSqlTwigBundle::class =>  ['all' => true],
   ];
   ```

# Working with the bundle

It is necessary to define which directory/directories will be used for storing files with sql queries.

```yaml
    twig:
      paths:
        '%kernel.project_dir%/src/sql/media': 'media'
        '%kernel.project_dir%/src/sql/expert': 'expert'
```

Create a sql query. Example (`all_media.sql.twig`):

```sql

   SELECT
       m.id
   {% if true == user  %}
       , u.name as user_name
   {% endif %}
   
   FROM media as m
   
       {% if true == user  %}
           INNER JOIN user as u ON m.user_id = u.id
       {% endif %}
   
   WHERE m.id in (:ids)
   
   ORDER BY m.id

```

Working in php, Example:

```php

namespace App\Example;

use Zjk\SqlTwig\Contract\SqlTwigInterface;

class MyRepository {
    
    private SqlTwigInterface $sqlTwig;
    
    public function __construct(SqlTwigInterface $sqlTwig) 
    {
        $this->sqlTwig = $sqlTwig;
    }
    
    public function allMedia(): array
    {
        return $this->sqlTwig->executeQuery('@media/all_media.sql.twig', [
                'user' => true,
                'ids' => ['60b16643-d5e0-468a-8823-499fcf07684a', '60b16643-d5e0-468a-8823-499fcf07684b'],
               ],[
                 'ids' => ArrayParameterType::STRING,
              ])->fetchAllAssociative()
    }
    
    public function withTransaction(): array
    {
        $result = $this->manager->transaction(
            static fn (SqlTwigInterface $manager): Result => $manager->executeQuery('@query/media_id_title.sql.twig'),
            TransactionIsolationLevel::READ_UNCOMMITTED
        );
        
        return $result->fetchAllAssociative();
    }
        
}

```


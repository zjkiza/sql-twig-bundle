container_php = 'php_bundle_2'
container_db = 'mysql_bundle_2'

waiting_db_connection = True
phpunit_code_error_bypass = False

containers = [
    container_php,
    container_db
]

container_work_dir = '/www'

docker_compose_files_list = [
    'docker-compose.yaml'
]

commands = {
    'composer install': 'composer install',
    'composer run phpunit': 'composer run phpunit-ci',
    'composer run phpstan': 'composer run phpstan',
    'composer run psalm': 'composer run psalm',
    'composer run phpmd': 'composer run phpmd',
}

from utility.dockerManager import DockerManager
from config import (container_db, docker_compose_files_list, containers)


def create_docker_manager(
        verbose: bool = True, waiting_db: bool = False, unit_code_error_bypass: bool = False) -> DockerManager:
    return DockerManager(
        verbose=verbose,
        docker_compose_files_list=docker_compose_files_list,
        containers=containers,
        container_db=container_db,
        waiting_db_connection=waiting_db,
        phpunit_code_error_bypass=unit_code_error_bypass
    )

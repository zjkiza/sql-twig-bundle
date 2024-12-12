#!/usr/bin/env python3

import click
from utility.utility import run_container
from config import container_db, containers, docker_compose_files_list


@click.command()
@click.option('--verbose/--no-verbose', default=False, help='Default is not verbose.', type=bool)
@click.option('--waiting_db_connection/--no-waiting_db_connection', default=False,
              help='Default is not waiting db connection.', type=bool)
def run(verbose, waiting_db_connection):
    run_container(verbose, waiting_db_connection, docker_compose_files_list, containers, container_db)


if __name__ == '__main__':
    run()

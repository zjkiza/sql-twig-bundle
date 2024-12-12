#!/usr/bin/env python3

import click
from utility.utility import down_container
from config import docker_compose_files_list


@click.command()
@click.option('--verbose/--no-verbose', default=False, help='Default is not verbose.', type=bool)
def run(verbose):
    down_container(verbose, docker_compose_files_list)


if __name__ == '__main__':
    run()

#!/usr/bin/env python3

import click
import os
from rich.console import Console
from utility.utility import is_containers_running
from config import container_php, container_work_dir


@click.command()
@click.option('--command', default='"/bin/bash"', help='A command to execute when attaching to container.')
def attach(command):
    is_containers_running([container_php])

    Console().print(
        'Leaving host environment and attaching to service {}...'.format(container_php),
        width=80,
        style="yellow"
    )

    os.system('docker exec -w {} -it {} bash -c "{}"'.format(container_work_dir, container_php, command))


if __name__ == '__main__':
    attach()

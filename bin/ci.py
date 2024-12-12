#!/usr/bin/env python3

import time
from rich.console import Console
from rich.markdown import Markdown
import subprocess
import click
from utility.utility import run_container
from utility.utility import process_test_result
from utility.utility import down_container
from config import container_php, container_db, waiting_db_connection, containers, docker_compose_files_list, \
    commands, container_work_dir, phpunit_code_error_bypass


@click.command()
@click.option('--verbose/--no-verbose', default=False,
              help='Default is not verbose. Do you want verbose output of each check.', type=bool)
def run(verbose):
    start = time.time()

    run_container(verbose, waiting_db_connection, docker_compose_files_list, containers, container_db)

    result_of_tests = 0

    for command_name, command in commands.items():
        Console().print('[blue]Running `{}`...[/blue]'.format(command_name), width=120)

        output_process = subprocess.run(
            'docker exec -w {} -it {} sh -c "{}"'.format(container_work_dir, container_php, command),
            shell=True,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True
        )

        result_of_tests = process_test_result(output_process, command_name, verbose, result_of_tests,
                                              phpunit_code_error_bypass)

    total_time = round(time.time() - start)

    Console().print(Markdown('***'), width=120)

    if 0 != result_of_tests:
        Console().print('[red]ERROR! Not all checks passed, execution time was {} seconds.[/red]'.format(total_time),
                        width=80)
        Console().print(Markdown('***'), width=120)
        exit(1)

    Console().print(Markdown('### SUCCESS! All checks pass, execution time was {} seconds.'.format(total_time)),
                    width=120, style="green")
    Console().print(Markdown('***'), width=120)

    down_container(verbose, docker_compose_files_list)

    Console().print(Markdown('***'), width=120)
    Console().print(Markdown('### End continuous integration.'), width=120, style="green")
    Console().print(Markdown('***'), width=120)
    exit(0)


if __name__ == '__main__':
    Console().print(Markdown('***'), width=120)
    Console().print(Markdown('### Running continuous integration.'), width=120, style="green")
    Console().print(Markdown('***'), width=120)
    run()

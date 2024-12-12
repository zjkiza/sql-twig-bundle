import re
import os
import time
import subprocess
from dotenv import dotenv_values
from rich.console import Console
from rich.markdown import Markdown


def run_container(verbose, waiting_db_connection, docker_compose_files_list, containers, container_db):
    script_directory = os.path.dirname(__file__)
    working_directory = os.path.abspath(os.path.join(script_directory, '..', '..'))
    os.chdir(working_directory)

    is_environment_appropriate()

    docker_compose_files = ' -f '.join(docker_compose_files_list)

    Console().print(Markdown('# Running docker containers ...'), width=120, style="green")

    result = subprocess.run(
        'docker-compose -f {} up -d --build {}'.format(docker_compose_files, ' > /dev/null' if not verbose else ''),
        shell=True,
        stdout=subprocess.PIPE
    )

    if 0 != result.returncode:
        Console().print('[bold red]ERROR container "{}" failed to start![/bold red]'.format(docker_compose_files))
        exit(1)

    is_containers_running(containers)

    if container_db is not None and waiting_db_connection:
        waiting_database_connection(container_db)


def down_container(verbose, docker_compose_files_list):
    docker_compose_files = ' -f '.join(docker_compose_files_list)

    result = subprocess.run(
        'docker-compose -f {} down {}'.format(docker_compose_files, ' > /dev/null' if not verbose else ''),
        shell=True,
        stdout=subprocess.PIPE
    )

    if 0 != result.returncode:
        Console().print('[bold red]ERROR container "{}" failed to down![/bold red]'.format(docker_compose_files))
        exit(1)

    Console().print(Markdown('***'), width=120)
    Console().print(Markdown('### Tearing down docker containers.'), width=120, style="green")
    Console().print(Markdown('***'), width=120)


def process_test_result(output_process, command_name, verbose, result_of_tests, phpunit_code_error_bypass):

    if phpunit_code_error_bypass and 'composer run phpunit' == command_name:
        output = output_process.stdout
        if "FAILURES!" in output or "Failures:" in output:
            print_result('Failed.', 'red', verbose, output_process, command_name)
            return result_of_tests + 1
        else:
            print_result('Pass.', 'green', verbose, output_process, command_name)
            return result_of_tests

    if output_process.returncode:
        print_result('Failed.', 'red', verbose, output_process, command_name)
        return result_of_tests + 1
    else:
        print_result('Pass.', 'green', verbose, output_process, command_name)
        return result_of_tests


def display_output(output_process, name):
    Console().print('[yellow]Displaying the contents of the started test "{}":[/yellow]'.format(name), width=120)

    encoded_text = output_process.stdout.encode('utf-8')
    decoded_text = encoded_text.decode("utf-8")
    ansi_escape = re.compile(r'\x1B(?:[@-Z\\-_]|\[[0-?]*[ -/]*[@-~])')
    clean_text = ansi_escape.sub('', decoded_text)

    Console().print(clean_text)

    Console().print('[yellow]End of display "{}".[/yellow]'.format(name), width=120)


def print_result(message, style, verbose, output_process, name):
    Console().print(message, width=80, style=style)
    if verbose:
        display_output(output_process, name)


def waiting_database_connection(container_db):
    Console().print('[yellow]Waiting for the database "{}" to be ready......[/yellow]'.format(container_db))

    for i in range(3):
        try:
            subprocess.run(
                'docker exec {} sh -c "mysqladmin ping -h localhost --silent"'.format(container_db),
                shell=True,
                check=True
            )
            Console().print('[yellow]The database is ready![/yellow]')
            break
        except subprocess.CalledProcessError:
            Console().print(f"Attempt {i + 1}: The database is not ready yet...")
            time.sleep(30)
    else:
        Console().print('[bold red]Database not ready after 90 seconds. Check the configuration![/bold red]')
        exit(1)


def is_environment_appropriate():
    configuration = dotenv_values('.env')

    if 'prod' == configuration.get('APP_ENV'):
        Console().print(
            '[bold red]ERROR: This script can be executed only in developer and test environment![/bold red]')
        exit(1)


def is_containers_running(containers):
    for container_name in containers:

        if container_name is None:
            continue

        if __is_container_running(container_name):
            Console().print('[yellow]Container "{}" has been successfully uploaded.[/yellow]'.format(container_name))
        else:
            Console().print('[bold red]Container "{}" not mounted or not found.[/bold red]'.format(container_name))
            exit(1)


def __is_container_running(container_name):
    try:
        result = subprocess.run(
            ['docker', 'ps'],
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True
        )

        return True if container_name in result.stdout else False

    except Exception as e:
        Console().print('[bold red]Error while checking container status: {}[/bold red]'.format(e))
        exit(1)

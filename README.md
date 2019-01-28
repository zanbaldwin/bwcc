# Xero Importer

Imports accounts and vendors from Xero API.

Uses Docker to ensure a known environment state, not using Docker is also supported but undocumented.

## Prerequisites

- [Docker](https://docs.docker.com/install/)
- [Docker Compose](https://docs.docker.com/compose/install/) (1.20+)
- [GNU Make](https://www.gnu.org/software/make/)

This import tool uses an OAuth Public Application called **ZanBWCC**. To use a different application, copy the file
`.env` to `.env.local` and modify the values for the environment variables `XERO_CONSUMER_KEY` and
`XERO_CONSUMER_SECRET`.

## Setup

- `make build`: to build the image used for the Dockerized environment.
- `make install`: to install the required third-party dependencies.

## Running

### Web Application

To start the Docker containers and run the web application, execute `make run`.

By default the web application listens on port 2095; to use a different port add a variable called `APP_PORT` in `.env`
to specify a different port and rerun the `make run` command.

### Command Line

> The executable `bin/docker` is a shortcut for running commands inside the Dockerized environment.

To run the importer as a console command, execute `bin/docker bin/console xero:import <format>` where `<format>`
specifies the desired output format; currently accepted values are: `csv`, `json`, `sqlite`, and `yaml`.

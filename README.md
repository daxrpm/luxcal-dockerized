
# LuxCal Dockerized

This repository provides a Dockerized version of the LuxCal Web Calendar.

## Features

- LuxCal calendar app (PHP)
- Runs in an Apache + PHP 8.2 container
- SQLite and MySQL support
- Security and performance best practices applied

## Quick Start

1. **Clone this repository**
2. **Build the Docker image:**

	```sh
	docker build -t luxcal .
	```
3. **Run the container:**

	```sh
	docker run -d -p 8080:80 luxcal
	```
4. **Access the calendar:**  
	Open [http://localhost:8080](http://localhost:8080) in your browser.

## Project Structure

- `luxcal_src/` - Main calendar source code
- `Dockerfile` - Container setup
- `unzip.bash` - Script to extract calendar files

## Best Practices

- Do not expose sensitive files or directories.
- Use environment variables for secrets in production.
- Keep your Docker image up to date.
- Review and adjust PHP/Apache settings for your needs.

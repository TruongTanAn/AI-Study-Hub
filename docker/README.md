# AI Study Hub - Docker

## Quick Start

1. Copy env file:
   ```bash
   cp .env.example .env
   ```

2. Start the stack:
   ```bash
   docker compose up -d --build
   ```

3. Access:
   - Website: http://localhost:8080
   - phpMyAdmin: http://localhost:8081
   - MySQL: localhost:3306

## Default credentials

- MySQL root: `root` / `root_password`
- MySQL user: `ai_user` / `ai_password`
- Database: `ai_study_hub`

## Volumes

- `mysql_data` — persistent MySQL data
- `./uploads` — uploaded documents
- `./logs` — application logs
- `./cache` — cache directory

## Stop & Remove

```bash
docker compose down           # stop, keep volumes
docker compose down -v        # stop, remove volumes (deletes DB)
```

## Rebuild after changes

```bash
docker compose up -d --build
```

# Running Farkle Ten with Docker

This guide explains how to run the Farkle Ten game locally using Docker.

## Prerequisites

- Docker Desktop installed ([Get Docker](https://www.docker.com/products/docker-desktop))
- Docker Compose (included with Docker Desktop)

## Quick Start

### Option 1: Using Helper Scripts (Recommended)

1. **Start the application with logging**
   ```bash
   ./start.sh
   ```
   This will:
   - Start Docker containers
   - Create a tmux session with live logs
   - Write logs to `logs/docker-server.log`

2. **Stop the application**
   ```bash
   ./stop.sh
   ```

3. **View logs**
   ```bash
   # Attach to tmux session
   tmux attach -t farkle-server

   # Or tail the log file
   tail -f logs/docker-server.log
   ```

### Option 2: Using Docker Compose Directly

1. **Start the application**
   ```bash
   docker-compose up -d
   ```

2. **Access the application**
   - Game: http://localhost:8080
   - phpMyAdmin: http://localhost:8081 (for database management)

3. **Login**
   - Username: `testuser`
   - Password: `test123`

### Option 3: Using Make Commands

```bash
make start    # Start services
make stop     # Stop services
make logs     # View logs
make help     # See all commands
```

## Services

The Docker setup includes three services:

### Web Server (PHP 5.6 + Apache)
- Runs on port 8080
- PHP 5.6 with deprecated mysql_* functions support
- Smarty template engine pre-installed

### MySQL Database (5.7)
- Runs on port 3306
- Database: `mikeschm_db`
- User: `farkle_user`
- Password: `farkle_pass`
- Root password: `rootpassword`

### phpMyAdmin
- Runs on port 8081
- Useful for database inspection and management

## Common Commands

### Using Helper Scripts

```bash
./start.sh                      # Start with tmux logging
./stop.sh                       # Stop server and tmux session
tmux attach -t farkle-server    # Attach to log session
tail -f logs/docker-server.log  # View log file
```

### Using Make

```bash
make start     # Start services
make stop      # Stop services
make logs      # Follow logs
make restart   # Restart services
make help      # See all commands
```

### Using Docker Compose Directly

```bash
# Start services
docker-compose up -d

# Stop services
docker-compose down

# View logs
docker-compose logs -f           # All services
docker-compose logs -f web       # Web server only
docker-compose logs -f db        # Database only
```

### Rebuild after code changes
```bash
docker-compose up -d --build
```

### Reset database (WARNING: deletes all data)
```bash
docker-compose down -v
docker-compose up -d
```

### Access MySQL CLI
```bash
docker-compose exec db mysql -u farkle_user -pfarkle_pass mikeschm_db
```

### Access web container shell
```bash
docker-compose exec web bash
```

## Configuration

### Database Configuration
Edit `docker/siteconfig.ini` to change database settings or site configuration.

### Environment Variables
Modify `docker-compose.yml` to change:
- Database credentials
- Ports
- MySQL version

### Initial Database
The database is initialized with tables and a test user. See `docker/init.sql` for details.

## Troubleshooting

### Port Already in Use
If port 8080 or 3306 is already in use, edit `docker-compose.yml`:
```yaml
ports:
  - "8081:80"  # Change 8080 to another port
```

### Permission Issues
If you encounter permission errors:
```bash
docker-compose exec web chown -R www-data:www-data /var/www/backbone /var/www/html/logs
```

### Database Connection Errors
1. Ensure database container is running: `docker-compose ps`
2. Check database logs: `docker-compose logs db`
3. Verify configuration in `docker/siteconfig.ini`

### Clear Smarty Cache
```bash
docker-compose exec web rm -rf /var/www/backbone/templates_c/* /var/www/backbone/cache/*
```

## Development Workflow

1. Make code changes in your local directory
2. Changes are automatically reflected (volume mount)
3. For template changes, clear Smarty cache if needed
4. For database schema changes, update `docker/init.sql`

## Production Notes

This Docker setup is for **local development only**. For production:

- Use PHP 7.4+ and upgrade to mysqli/PDO
- Use stronger passwords
- Configure proper security headers
- Use HTTPS
- Set up proper logging and monitoring
- Consider using environment variables for sensitive data
- Disable phpMyAdmin or restrict access

## File Structure

```
farkledice/
├── docker/
│   ├── siteconfig.ini    # Site configuration
│   ├── init.sql          # Database initialization
│   ├── backbone/         # Smarty directories (auto-created)
│   └── configs/          # Config directory (auto-created)
├── logs/
│   └── docker-server.log # Server logs (created by start.sh)
├── Dockerfile            # PHP/Apache container definition
├── docker-compose.yml    # Multi-container orchestration
├── Makefile             # Make commands for convenience
├── start.sh             # Start server with tmux logging
├── stop.sh              # Stop server and tmux session
└── .dockerignore        # Files to exclude from Docker build
```

## Tmux Session

The `start.sh` script creates a tmux session named `farkle-server` that streams all Docker logs.

**Tmux Commands:**
- Attach to session: `tmux attach -t farkle-server`
- Detach from session: Press `Ctrl+B`, then `D`
- Kill session: `tmux kill-session -t farkle-server`
- List sessions: `tmux ls`

**Reading Logs:**
- Live in tmux: `tmux attach -t farkle-server`
- From file: `tail -f logs/docker-server.log`
- From file (Claude): Claude can read `logs/docker-server.log` to see server output

## Useful Links

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [PHP 5.6 Documentation](https://www.php.net/manual/en/)
- [MySQL 5.7 Documentation](https://dev.mysql.com/doc/refman/5.7/en/)

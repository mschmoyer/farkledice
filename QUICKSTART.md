# Quick Start Guide

Get Farkle Ten running on your machine in under 2 minutes!

## Prerequisites Check

1. **Docker Desktop** - [Download here](https://www.docker.com/products/docker-desktop)
   ```bash
   docker --version
   # Should output: Docker version XX.X.X
   ```

2. **tmux** (optional, for logging)
   ```bash
   # macOS
   brew install tmux

   # Linux
   sudo apt-get install tmux
   ```

## Start the Game

Run one command:

```bash
./start.sh
```

That's it! 🎉

## Access the Game

Open your browser to:
- **Game**: http://localhost:8080
- **Database Admin**: http://localhost:8081

## Login

Use these test credentials:
- Username: `testuser`
- Password: `test123`

## Stop the Game

```bash
./stop.sh
```

## Troubleshooting

### "Permission denied" when running ./start.sh

```bash
chmod +x start.sh stop.sh
./start.sh
```

### Ports already in use

Edit `docker-compose.yml` and change the ports:
```yaml
ports:
  - "8082:80"  # Change 8080 to 8082
```

### Docker not running

Start Docker Desktop, then try again.

### Want to see what's happening?

```bash
# View live logs in tmux
tmux attach -t farkle-server

# Or view the log file
tail -f logs/docker-server.log

# Detach from tmux: Ctrl+B, then D
```

## What's Next?

- Read [DOCKER.md](DOCKER.md) for detailed Docker commands
- Read [CLAUDE.md](CLAUDE.md) to understand the codebase
- Read [README.md](README.md) for project information

## Alternative Start Methods

If you prefer not to use the scripts:

```bash
# Using Make
make start

# Using Docker Compose directly
docker-compose up -d
docker-compose logs -f
```

## Need Help?

Check the full documentation in [DOCKER.md](DOCKER.md)

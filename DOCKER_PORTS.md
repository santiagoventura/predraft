# Docker Configuration - Port Information

## Updated Ports to Avoid Conflicts

To avoid conflicts with other Docker containers on your system, this project uses the following ports:

### Port Mappings

| Service | Host Port | Container Port | URL |
|---------|-----------|----------------|-----|
| **Nginx (Web)** | 8090 | 80 | http://localhost:8090 |
| **MariaDB** | 3308 | 3306 | localhost:3308 |

### Container Names

All containers use unique names with the `mlb_draft_helper_` prefix:

- `mlb_draft_helper_app` - PHP-FPM application container
- `mlb_draft_helper_nginx` - Nginx web server
- `mlb_draft_helper_db` - MariaDB database

### Network Name

- **Network**: `mlb_draft_helper_network`
- **Volume**: `mlb_draft_helper_dbdata`

## Changing Ports

If you need to use different ports, edit `docker-compose.yml`:

### Change Web Port (default: 8090)

```yaml
nginx:
  ports:
    - "YOUR_PORT:80"  # Change 8090 to your preferred port
```

### Change Database Port (default: 3308)

```yaml
db:
  ports:
    - "YOUR_PORT:3306"  # Change 3308 to your preferred port
```

**Important**: If you change the database port, also update your `.env` file:

```env
DB_PORT=YOUR_PORT
```

## Checking for Port Conflicts

Before starting Docker, check if ports are available:

```bash
# Check web port
lsof -i :8090

# Check database port
lsof -i :3307

# Or use netstat
netstat -tuln | grep 8090
netstat -tuln | grep 3307
```

If a port is in use, you'll see output showing the process. If nothing is returned, the port is available.

## Connecting to the Database

### From Host Machine

```bash
mysql -h 127.0.0.1 -P 3307 -u mlb_draft_user -p
# Password: secret (or your custom password from .env)
```

### From Application Container

The application connects using the service name `db` on the internal network:

```env
DB_HOST=db
DB_PORT=3306  # Internal container port, not host port
```

### From External Tools (e.g., TablePlus, MySQL Workbench)

- **Host**: localhost or 127.0.0.1
- **Port**: 3307
- **Username**: mlb_draft_user
- **Password**: secret (or your custom password)
- **Database**: mlb_draft

## Troubleshooting

### Port Already in Use

If you get an error like:

```
Error starting userland proxy: listen tcp4 0.0.0.0:8090: bind: address already in use
```

**Solutions**:

1. **Find what's using the port**:
   ```bash
   lsof -i :8090
   ```

2. **Stop the conflicting service** or **change the port** in `docker-compose.yml`

3. **Restart Docker**:
   ```bash
   docker-compose down
   docker-compose up -d
   ```

### Can't Connect to Database

If the application can't connect to the database:

1. **Check containers are running**:
   ```bash
   docker-compose ps
   ```

2. **Check database logs**:
   ```bash
   docker-compose logs db
   ```

3. **Verify .env settings**:
   ```env
   DB_HOST=db          # Use service name, not localhost
   DB_PORT=3306        # Internal port, not 3307
   DB_DATABASE=mlb_draft
   DB_USERNAME=mlb_draft_user
   DB_PASSWORD=secret
   ```

### Network Issues

If containers can't communicate:

```bash
# Recreate network
docker-compose down
docker network prune
docker-compose up -d
```

## Multiple Environments

If you want to run multiple instances of this project:

1. **Copy the project to a new directory**
2. **Change ports in docker-compose.yml**:
   - Web: 8091, 8092, etc.
   - DB: 3308, 3309, etc.
3. **Change container names** to avoid conflicts:
   - `mlb_draft_helper_2_app`
   - `mlb_draft_helper_2_nginx`
   - `mlb_draft_helper_2_db`
4. **Change network name**:
   - `mlb_draft_helper_2_network`

## Default Configuration Summary

```yaml
# Web Access
http://localhost:8090

# Database Access (from host)
mysql -h 127.0.0.1 -P 3307 -u mlb_draft_user -p

# Container Names
mlb_draft_helper_app
mlb_draft_helper_nginx
mlb_draft_helper_db

# Network
mlb_draft_helper_network

# Volume
mlb_draft_helper_dbdata
```

## Quick Reference Commands

```bash
# Start containers
docker-compose up -d

# Stop containers
docker-compose down

# View logs
docker-compose logs -f

# Access app container
docker-compose exec app bash

# Access database
docker-compose exec db mysql -u root -p

# Restart specific service
docker-compose restart nginx

# View port mappings
docker-compose ps
```


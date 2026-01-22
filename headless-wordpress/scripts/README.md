# Scripts Directory

This directory contains utility scripts for managing your headless WordPress installation.

## Available Scripts

### `setup.sh`

Main setup script that installs WordPress core and essential plugins for headless operation.

**Usage:**

```bash
./scripts/setup.sh
```

**What it does:**

- Installs WordPress core
- Installs and activates WPGraphQL
- Installs Advanced Custom Fields
- Installs WPGraphQL for ACF
- Sets up permalinks
- Creates sample content

---

### `setup-jwt.sh`

Configures JWT authentication for the WordPress REST API.

**Usage:**

```bash
./scripts/setup-jwt.sh
```

**What it does:**

- Installs JWT authentication plugin
- Configures JWT secret key
- Sets up CORS headers

---

### `configure-cors.sh`

Configures CORS headers in wp-config.php for cross-origin requests.

**Usage:**

```bash
./scripts/configure-cors.sh
```

---

### `test-setup.sh`

Tests your WordPress installation to verify all endpoints are working.

**Usage:**

```bash
./scripts/test-setup.sh
```

**What it tests:**

- GraphQL endpoint
- REST API endpoint
- WordPress version
- Active plugins

---

## Making Scripts Executable

If you need to make scripts executable:

```bash
chmod +x scripts/*.sh
```

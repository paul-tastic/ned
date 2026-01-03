# Contributing to Ned

Thanks for your interest in contributing to Ned! This document outlines the process for contributing to this project.

## How to Contribute

### Reporting Bugs

1. **Check existing issues** - Search [GitHub Issues](https://github.com/paul-tastic/ned/issues) to avoid duplicates
2. **Create a new issue** with:
   - Clear, descriptive title
   - Steps to reproduce
   - Expected vs actual behavior
   - Environment details (OS, PHP version, etc.)
   - Relevant logs or screenshots

### Suggesting Features

1. Open an issue with the `enhancement` label
2. Describe the use case - what problem does it solve?
3. Propose a solution if you have one in mind
4. Be open to discussion - there may be alternative approaches

### Pull Requests

1. **Fork the repository** and create your branch from `master`
2. **Follow the branch naming convention:**
   - `feature/short-description` - New features
   - `fix/short-description` - Bug fixes
   - `docs/short-description` - Documentation updates
3. **Write clear commit messages** - Focus on "why" not just "what"
4. **Include tests** for new functionality
5. **Update documentation** if you change behavior
6. **Open a PR** with a clear description of changes

## Development Setup

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+ and npm
- SQLite (or MySQL/PostgreSQL for production-like testing)

### Local Development

```bash
# Clone your fork
git clone https://github.com/YOUR_USERNAME/ned.git
cd ned

# Server setup
cd server
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate

# Frontend assets
npm install
npm run dev

# Start development server
php artisan serve
```

### Running the Agent Locally

```bash
# Copy and configure
cp agent/config.example agent/config
# Edit agent/config with your local API URL and a test token

# Run once
./agent/ned-agent.sh

# Or test the output
./agent/ned-agent.sh | jq .
```

## Coding Standards

### PHP (Server)

- Follow PSR-12 coding style
- Use type hints for parameters and return types
- Use Laravel conventions for controllers, models, etc.
- Run `./vendor/bin/pint` before committing (Laravel Pint for formatting)

### Bash (Agent)

- Use `shellcheck` to lint scripts
- Quote variables: `"${VAR}"` not `$VAR`
- Use `[[` for conditionals, not `[`
- Keep functions small and focused

### General

- No trailing whitespace
- Files end with a newline
- Use meaningful variable/function names
- Comment "why", not "what"

## Testing

### Server Tests

```bash
cd server

# Run all tests
php artisan test

# Run with coverage
XDEBUG_MODE=coverage php artisan test --coverage

# Run specific test
php artisan test --filter=AuthenticationTest
```

### Agent Testing

```bash
# Validate JSON output
./agent/ned-agent.sh | jq .

# Check with shellcheck
shellcheck agent/ned-agent.sh agent/install.sh
```

## Project Structure

```
ned/
├── agent/           # Bash monitoring agent
│   ├── ned-agent.sh # Main agent script
│   ├── install.sh   # One-line installer
│   └── config.example
├── server/          # Laravel API + Dashboard
│   ├── app/
│   │   ├── Models/  # Eloquent models
│   │   └── Http/    # Controllers, middleware
│   ├── database/
│   │   └── migrations/
│   └── resources/
│       └── views/   # Livewire/Blade templates
├── CONTRIBUTING.md
├── DEPLOYMENT.md
├── SECURITY.md
└── README.md
```

## Questions?

- Open a [Discussion](https://github.com/paul-tastic/ned/discussions) for general questions
- Check [SECURITY.md](SECURITY.md) for security-related concerns

## Code of Conduct

Please read our [Code of Conduct](CODE_OF_CONDUCT.md) before contributing.

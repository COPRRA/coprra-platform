# COPRRA Project - One-Command Setup Makefile
# Usage: make setup (or make help for all commands)

.PHONY: help setup install dev test clean docker health docs

.DEFAULT_GOAL := help

# Colors for output
BLUE := \033[0;34m
GREEN := \033[0;32m
YELLOW := \033[1;33m
RED := \033[0;31m
NC := \033[0m # No Color

##@ General

help: ## Display this help message
	@echo "$(BLUE)═══════════════════════════════════════════════════════════════$(NC)"
	@echo "$(BLUE)  COPRRA Project - Available Commands$(NC)"
	@echo "$(BLUE)═══════════════════════════════════════════════════════════════$(NC)"
	@awk 'BEGIN {FS = ":.*##"; printf "\n"} /^[a-zA-Z_-]+:.*?##/ { printf "  $(GREEN)%-20s$(NC) %s\n", $$1, $$2 } /^##@/ { printf "\n$(YELLOW)%s$(NC)\n", substr($$0, 5) } ' $(MAKEFILE_LIST)
	@echo ""

##@ Setup & Installation

setup: ## 🚀 ONE-COMMAND SETUP - Run complete project setup (< 5 minutes)
	@echo "$(BLUE)═══════════════════════════════════════════════════════════════$(NC)"
	@echo "$(BLUE)  Starting COPRRA One-Command Setup$(NC)"
	@echo "$(BLUE)═══════════════════════════════════════════════════════════════$(NC)"
	@echo ""
	@echo "$(GREEN)Step 1/8: Checking prerequisites...$(NC)"
	@$(MAKE) check-prerequisites
	@echo "$(GREEN)Step 2/8: Installing dependencies...$(NC)"
	@$(MAKE) install
	@echo "$(GREEN)Step 3/8: Setting up environment...$(NC)"
	@$(MAKE) env-setup
	@echo "$(GREEN)Step 4/8: Setting up database...$(NC)"
	@$(MAKE) db-setup
	@echo "$(GREEN)Step 5/8: Building frontend assets...$(NC)"
	@$(MAKE) assets-build
	@echo "$(GREEN)Step 6/8: Setting up storage...$(NC)"
	@$(MAKE) storage-setup
	@echo "$(GREEN)Step 7/8: Running health check...$(NC)"
	@$(MAKE) health
	@echo "$(GREEN)Step 8/8: Running tests...$(NC)"
	@$(MAKE) test-quick
	@echo ""
	@echo "$(GREEN)✅ Setup completed successfully!$(NC)"
	@echo ""
	@echo "$(BLUE)Next steps:$(NC)"
	@echo "  1. $(YELLOW)make dev$(NC)      - Start development server"
	@echo "  2. $(YELLOW)make test$(NC)     - Run test suite"
	@echo "  3. Visit: $(YELLOW)http://localhost:8000$(NC)"
	@echo ""

setup-docker: ## 🐳 ONE-COMMAND DOCKER SETUP - Run complete Docker setup
	@echo "$(BLUE)═══════════════════════════════════════════════════════════════$(NC)"
	@echo "$(BLUE)  Starting COPRRA Docker Setup$(NC)"
	@echo "$(BLUE)═══════════════════════════════════════════════════════════════$(NC)"
	@echo ""
	@echo "$(GREEN)Step 1/5: Copying Docker environment...$(NC)"
	@test -f .env || cp .env.example .env
	@echo "$(GREEN)Step 2/5: Building Docker containers...$(NC)"
	@docker-compose build
	@echo "$(GREEN)Step 3/5: Starting Docker containers...$(NC)"
	@docker-compose up -d
	@echo "$(GREEN)Step 4/5: Installing dependencies in container...$(NC)"
	@docker-compose exec app composer install
	@docker-compose exec app npm install
	@echo "$(GREEN)Step 5/5: Running migrations...$(NC)"
	@docker-compose exec app php artisan migrate --seed
	@echo ""
	@echo "$(GREEN)✅ Docker setup completed!$(NC)"
	@echo "  Visit: $(YELLOW)http://localhost:8000$(NC)"

check-prerequisites: ## Check if all prerequisites are installed
	@echo "$(BLUE)Checking prerequisites...$(NC)"
	@command -v php >/dev/null 2>&1 || { echo "$(RED)❌ PHP is not installed$(NC)"; exit 1; }
	@php -r 'exit(version_compare(PHP_VERSION, "8.2.0", ">=") ? 0 : 1);' || { echo "$(RED)❌ PHP 8.2+ required$(NC)"; exit 1; }
	@echo "$(GREEN)✅ PHP $(shell php -r 'echo PHP_VERSION;')$(NC)"
	@command -v composer >/dev/null 2>&1 || { echo "$(RED)❌ Composer is not installed$(NC)"; exit 1; }
	@echo "$(GREEN)✅ Composer $(shell composer --version --no-ansi | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -1)$(NC)"
	@command -v node >/dev/null 2>&1 || { echo "$(RED)❌ Node.js is not installed$(NC)"; exit 1; }
	@echo "$(GREEN)✅ Node.js $(shell node --version)$(NC)"
	@command -v npm >/dev/null 2>&1 || { echo "$(RED)❌ NPM is not installed$(NC)"; exit 1; }
	@echo "$(GREEN)✅ NPM $(shell npm --version)$(NC)"
	@echo "$(GREEN)✅ All prerequisites installed$(NC)"

install: ## Install all dependencies (Composer + NPM)
	@echo "$(BLUE)Installing PHP dependencies...$(NC)"
	@composer install --no-interaction --prefer-dist --optimize-autoloader
	@echo "$(GREEN)✅ PHP dependencies installed$(NC)"
	@echo "$(BLUE)Installing JavaScript dependencies...$(NC)"
	@npm ci
	@echo "$(GREEN)✅ JavaScript dependencies installed$(NC)"

install-prod: ## Install production dependencies (optimized)
	@composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
	@npm ci --production
	@echo "$(GREEN)✅ Production dependencies installed$(NC)"

##@ Environment

env-setup: ## Setup environment file and generate key
	@if [ ! -f .env ]; then \
		cp .env.example .env; \
		echo "$(GREEN)✅ Created .env file$(NC)"; \
	else \
		echo "$(YELLOW)⚠ .env already exists$(NC)"; \
	fi
	@php artisan key:generate --ansi
	@echo "$(GREEN)✅ Application key generated$(NC)"

env-check: ## Check environment configuration
	@php artisan config:show app --env=production
	@echo "$(GREEN)✅ Environment configuration valid$(NC)"

##@ Database

db-setup: ## Setup database (migrate + seed)
	@php artisan migrate --force --seed
	@echo "$(GREEN)✅ Database setup completed$(NC)"

db-migrate: ## Run database migrations
	@php artisan migrate --force
	@echo "$(GREEN)✅ Migrations completed$(NC)"

db-seed: ## Seed database with data
	@php artisan db:seed
	@echo "$(GREEN)✅ Database seeded$(NC)"

db-fresh: ## Fresh database (drop all + migrate + seed)
	@php artisan migrate:fresh --seed
	@echo "$(GREEN)✅ Fresh database created$(NC)"

db-reset: ## Reset database (rollback + migrate + seed)
	@php artisan migrate:reset
	@php artisan migrate --seed
	@echo "$(GREEN)✅ Database reset completed$(NC)"

##@ Frontend Assets

assets-build: ## Build frontend assets for production
	@npm run build
	@echo "$(GREEN)✅ Frontend assets built$(NC)"

assets-dev: ## Build frontend assets for development (with watch)
	@npm run dev

assets-clean: ## Clean frontend build artifacts
	@rm -rf public/build
	@npm run clean
	@echo "$(GREEN)✅ Frontend assets cleaned$(NC)"

##@ Storage & Cache

storage-setup: ## Setup storage directories and symlink
	@php artisan storage:link
	@chmod -R 775 storage bootstrap/cache
	@echo "$(GREEN)✅ Storage setup completed$(NC)"

cache-clear: ## Clear all caches
	@php artisan cache:clear
	@php artisan config:clear
	@php artisan route:clear
	@php artisan view:clear
	@echo "$(GREEN)✅ All caches cleared$(NC)"

cache-optimize: ## Optimize caches for production
	@php artisan config:cache
	@php artisan route:cache
	@php artisan view:cache
	@echo "$(GREEN)✅ Caches optimized$(NC)"

##@ Development

dev: ## Start development server
	@echo "$(BLUE)Starting development server...$(NC)"
	@echo "$(GREEN)Visit: http://localhost:8000$(NC)"
	@php artisan serve

dev-watch: ## Start development server with asset watching
	@echo "$(BLUE)Starting development server with asset watching...$(NC)"
	@echo "$(GREEN)Visit: http://localhost:8000$(NC)"
	@trap 'kill 0' INT; \
	php artisan serve & \
	npm run dev

tinker: ## Open Laravel Tinker REPL
	@php artisan tinker

routes: ## List all application routes
	@php artisan route:list

ide-helper: ## Generate IDE helper files
	@php artisan ide-helper:generate
	@php artisan ide-helper:models --nowrite
	@php artisan ide-helper:meta
	@echo "$(GREEN)✅ IDE helper files generated$(NC)"

##@ Testing

test: ## Run all tests
	@php artisan test --parallel

test-quick: ## Run quick test suite (no coverage)
	@php artisan test --compact

test-coverage: ## Run tests with coverage report
	@php artisan test --coverage --min=80

test-unit: ## Run unit tests only
	@php artisan test --testsuite=Unit

test-feature: ## Run feature tests only
	@php artisan test --testsuite=Feature

test-watch: ## Run tests in watch mode
	@php artisan test --parallel --watch

test-frontend: ## Run frontend tests
	@npm run test:run

##@ Code Quality

lint: ## Run all linters (PHP + JavaScript)
	@echo "$(BLUE)Running PHP linter...$(NC)"
	@./vendor/bin/pint --test
	@echo "$(BLUE)Running JavaScript linter...$(NC)"
	@npm run lint
	@echo "$(GREEN)✅ All linters passed$(NC)"

lint-fix: ## Fix all linting issues
	@echo "$(BLUE)Fixing PHP issues...$(NC)"
	@./vendor/bin/pint
	@echo "$(BLUE)Fixing JavaScript issues...$(NC)"
	@npm run lint:fix
	@echo "$(GREEN)✅ All issues fixed$(NC)"

phpstan: ## Run PHPStan static analysis
	@./vendor/bin/phpstan analyse --memory-limit=2G

psalm: ## Run Psalm static analysis
	@./vendor/bin/psalm

quality: ## Run all quality checks
	@$(MAKE) lint
	@$(MAKE) phpstan
	@$(MAKE) test-quick
	@echo "$(GREEN)✅ All quality checks passed$(NC)"

##@ Docker

docker-up: ## Start Docker containers
	@docker-compose up -d
	@echo "$(GREEN)✅ Docker containers started$(NC)"

docker-down: ## Stop Docker containers
	@docker-compose down
	@echo "$(GREEN)✅ Docker containers stopped$(NC)"

docker-build: ## Build Docker images
	@docker-compose build --no-cache
	@echo "$(GREEN)✅ Docker images built$(NC)"

docker-logs: ## View Docker logs
	@docker-compose logs -f

docker-shell: ## Open shell in app container
	@docker-compose exec app bash

docker-mysql: ## Open MySQL shell in database container
	@docker-compose exec mysql mysql -u coprra -p coprra

docker-redis: ## Open Redis CLI
	@docker-compose exec redis redis-cli

docker-clean: ## Clean Docker (remove containers and volumes)
	@docker-compose down -v
	@echo "$(GREEN)✅ Docker cleaned$(NC)"

##@ Health & Monitoring

health: ## Run comprehensive health check
	@bash scripts/health-check.sh

health-db: ## Check database health
	@php artisan migrate:status
	@echo "$(GREEN)✅ Database is healthy$(NC)"

health-redis: ## Check Redis health
	@php artisan tinker --execute="Cache::store('redis')->put('test', 'ok', 10); echo Cache::get('test');"
	@echo "$(GREEN)✅ Redis is healthy$(NC)"

##@ Maintenance

clean: ## Clean temporary files and caches
	@rm -rf storage/logs/*.log
	@rm -rf storage/framework/cache/*
	@rm -rf storage/framework/sessions/*
	@rm -rf storage/framework/views/*
	@$(MAKE) cache-clear
	@echo "$(GREEN)✅ Cleanup completed$(NC)"

clean-all: ## Deep clean (dependencies + caches + logs)
	@rm -rf vendor node_modules public/build
	@$(MAKE) clean
	@echo "$(GREEN)✅ Deep cleanup completed$(NC)"

optimize: ## Optimize for production
	@$(MAKE) cache-optimize
	@php artisan optimize
	@composer dump-autoload --optimize
	@echo "$(GREEN)✅ Optimization completed$(NC)"

##@ Deployment

deploy-prepare: ## Prepare for deployment
	@$(MAKE) install-prod
	@$(MAKE) env-check
	@$(MAKE) db-migrate
	@$(MAKE) assets-build
	@$(MAKE) optimize
	@echo "$(GREEN)✅ Deployment preparation completed$(NC)"

deploy-rollback: ## Rollback database migrations
	@php artisan migrate:rollback
	@echo "$(GREEN)✅ Rollback completed$(NC)"

##@ Security

security-check: ## Run security audit
	@composer audit
	@npm audit
	@echo "$(GREEN)✅ Security audit completed$(NC)"

security-update: ## Update dependencies to fix vulnerabilities
	@composer update --with-all-dependencies
	@npm audit fix
	@echo "$(GREEN)✅ Security updates applied$(NC)"

##@ Documentation

docs: ## View documentation
	@echo "$(BLUE)Available documentation:$(NC)"
	@echo "  📖 README.md - Main documentation"
	@echo "  📖 SETUP_GUIDE.md - Setup guide"
	@echo "  📖 TROUBLESHOOTING.md - Troubleshooting guide"
	@echo "  📖 docs/ - Additional documentation"

docs-api: ## Generate API documentation
	@php artisan l5-swagger:generate
	@echo "$(GREEN)✅ API documentation generated$(NC)"
	@echo "  Visit: $(YELLOW)http://localhost:8000/api/documentation$(NC)"

##@ Quick Actions

quick-start: setup dev ## 🚀 Quick start (setup + dev server)

first-pr: ## 👨‍💻 Make first PR (create feature branch + test)
	@echo "$(BLUE)Creating feature branch...$(NC)"
	@git checkout -b feature/my-first-contribution
	@echo "$(GREEN)✅ Branch created: feature/my-first-contribution$(NC)"
	@echo ""
	@echo "$(BLUE)Next steps:$(NC)"
	@echo "  1. Make your changes"
	@echo "  2. $(YELLOW)git add .$(NC)"
	@echo "  3. $(YELLOW)git commit -m 'Your commit message'$(NC)"
	@echo "  4. $(YELLOW)git push origin feature/my-first-contribution$(NC)"
	@echo "  5. Create Pull Request on GitHub"
	@echo ""
	@$(MAKE) test-quick

pr-ready: ## ✅ Check if code is ready for PR
	@echo "$(BLUE)Running pre-PR checks...$(NC)"
	@$(MAKE) lint
	@$(MAKE) phpstan
	@$(MAKE) test
	@echo ""
	@echo "$(GREEN)✅ Code is ready for Pull Request!$(NC)"

ci-local: ## Run CI checks locally
	@echo "$(BLUE)Running CI checks locally...$(NC)"
	@$(MAKE) lint
	@$(MAKE) phpstan
	@$(MAKE) test-coverage
	@$(MAKE) security-check
	@echo "$(GREEN)✅ All CI checks passed!$(NC)"

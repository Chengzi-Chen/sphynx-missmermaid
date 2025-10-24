COMPOSE = docker compose

.PHONY: up down logs db\:dump fix-install init-sphynx seed-sphynx sync-theme

up:
	$(COMPOSE) up -d --build

down:
	$(COMPOSE) down

logs:
	$(COMPOSE) logs -f

db\:dump:
	@mkdir -p dumps
	@echo "Creating database dump at dumps/wp_sphynx.sql"
	@$(COMPOSE) exec mariadb-sphynx sh -c 'mysqldump -u root -proot_pass wp_sphynx' > dumps/wp_sphynx.sql

fix-install:
	$(COMPOSE) exec wordpress-sphynx bash /opt/sphynx-scripts/fix_wp_install.sh

init-sphynx:
	$(COMPOSE) exec wordpress-sphynx bash /opt/sphynx-scripts/init_sphynx.sh

seed-sphynx:
	$(COMPOSE) exec wordpress-sphynx bash /opt/sphynx-scripts/seed_sphynx.sh

sync-theme:
	./scripts/rsync_theme.sh

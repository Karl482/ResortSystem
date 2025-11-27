@echo off
echo Running migration: add_is_active_to_users.php
php scripts/migrations/add_is_active_to_users.php
echo.
echo Running migration: create_staff_resort_assignments_table.php
php scripts/migrations/create_staff_resort_assignments_table.php up
echo.
echo All migrations finished successfully.
pause
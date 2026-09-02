-- Runs once on first container start. The app DB is created from
-- MYSQL_DATABASE; the test suite needs its own.
CREATE DATABASE IF NOT EXISTS mtb_test;
GRANT ALL PRIVILEGES ON mtb_test.* TO 'root'@'%';

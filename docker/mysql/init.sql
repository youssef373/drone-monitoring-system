CREATE DATABASE IF NOT EXISTS drone_monitoring_testing
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

GRANT ALL PRIVILEGES ON drone_monitoring_testing.* TO 'drone'@'%';

FLUSH PRIVILEGES;

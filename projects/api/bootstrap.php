<?php

ini_set("memory_limit", "4096M");
set_time_limit(0);

function write($line) {
    echo $line . "\n";
    ob_flush();
}

$config = require("/var/www/html/config/api.php");

if ($config["database"]["type"] !== "mysql") {
    write("Bootstrap script only supports MySQL backends.");
    exit(1);
}

write("Connecting to the database...");

$retries = getenv("DATABASE_RETRY");
if (!$retries) {
    $retries = 120;
} else {
    $retries = intval($retries);
}

while ($retries-- > 0) {
    try {
        $dsn = "mysql:" .
            "host=" . $config["database"]["host"] . ";" .
            "port=" . $config["database"]["host"] . ";";
        $connection = new PDO($dsn,
            $config["database"]["username"],
            $config["database"]["password"], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
    } catch (PDOException $e) {
        $connection = false;
        write("Database connection failed.");
        write($e->getMessage());
        sleep(1);
        continue;
    }
    break;
}

if (!$connection) {
    write("Cannot connect to the database server.");
    exit(1);
} else {
    write("Connected to the database instance.");
}

// List database

$databases = [];

try {
    $statement = $connection->prepare("SHOW DATABASES");
    $statement->execute();
    $databases = $statement->fetchAll(PDO::FETCH_CLASS);
} catch (PDOException $e) {
    write("Failed to show databases.");
    write($e->getMessage());
    exit(1);
}

// Find database

$found = false;

try {
    foreach ($databases as $database) {
        if ($database->Database === $config["database"]["name"]) {
            $found = true;
            break;
        }
    }
} catch (Exception $e) {
    write("Failed to find database.");
    write($e->getMessage());
    exit(1);
}

// Create database

if (!$found) {
    try {
        $statement = $connection->prepare("CREATE DATABASE " . $config["database"]["name"]);
        $statement->execute();
    } catch (PDOException $e) {
        write("Failed to create database.");
        write($e->getMessage());
        exit(1);
    }
}

// Select database

try {
    $statement = $connection->prepare("USE " . $config["database"]["name"]);
    $statement->execute();
} catch (PDOException $e) {
    write("Failed to select database.");
    write($e->getMessage());
    exit(1);
}

// Initialize if necessary

$shouldInstall = false;

try {
    $statement = $connection->prepare("SHOW TABLES");
    $statement->execute();
    $tables = $statement->fetchAll(PDO::FETCH_CLASS);
    if (sizeof($tables) <= 0) {
        $shouldInstall = true;
    }
} catch (PDOException $e) {
    write("Failed to list database tables.");
    write($e->getMessage());
    exit(1);
}

// Initialize

if ($shouldInstall) {

    write("Installing database...");
    sleep(3);
    passthru("/var/www/html/bin/directus install:database");

    write("Installing data...");
    sleep(3);
    passthru("/var/www/html/bin/directus install:install -e \"admin@admin.com\" -p \"admin\" -t \"Directus\"");

}

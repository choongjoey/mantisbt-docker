<?php

// DB_TYPE: mysql (default), pgsql
// Normalise to the MantisBT / ADOdb driver name
$_db_type = strtolower(getenv('DB_TYPE') !== false ? getenv('DB_TYPE') : 'mysql');
switch ($_db_type) {
    case 'pgsql':
    case 'postgres':
    case 'postgresql':
        $g_db_type = 'pgsql';
        break;
    default:
        $g_db_type = 'mysqli';
}

// Generic DB_* vars take precedence; MYSQL_* kept for backward compatibility.
$g_hostname      = getenv('DB_HOST')     !== false ? getenv('DB_HOST')
                 : (getenv('MYSQL_HOST')     !== false ? getenv('MYSQL_HOST')     : 'db');
$g_database_name = getenv('DB_DATABASE') !== false ? getenv('DB_DATABASE')
                 : (getenv('MYSQL_DATABASE') !== false ? getenv('MYSQL_DATABASE') : 'bugtracker');
$g_db_username   = getenv('DB_USER')     !== false ? getenv('DB_USER')
                 : (getenv('MYSQL_USER')     !== false ? getenv('MYSQL_USER')     : 'mantis');
$g_db_password   = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD')
                 : (getenv('MYSQL_PASSWORD') !== false ? getenv('MYSQL_PASSWORD') : 'mantis');


$g_crypto_master_salt       = getenv('MASTER_SALT');


# Configure email
$g_webmaster_email          = getenv('EMAIL_WEBMASTER') !== false ? getenv('EMAIL_WEBMASTER') : null;
$g_from_email          = getenv('EMAIL_FROM') !== false ? getenv('EMAIL_FROM') : null;
if (getenv('EMAIL_FROM_NAME') !== false) {
    $g_from_name = getenv('EMAIL_FROM_NAME');
}
$g_return_path_email          = getenv('EMAIL_RETURN_PATH') !== false ? getenv('EMAIL_RETURN_PATH') : null;

# SMTP
if (getenv('SMTP_HOST') !== false) {
    $g_phpMailer_method = PHPMAILER_METHOD_SMTP;
    $g_smtp_host = getenv('SMTP_HOST');
    $g_smtp_port = getenv('SMTP_PORT') !== false ? getenv('SMTP_PORT') : 587;
    $g_smtp_connection_mode = getenv('SMTP_MODE') !== false ? getenv('SMTP_MODE') : "tls";
    $g_smtp_username = getenv('SMTP_USER') !== false ? getenv('SMTP_USER') : null;
    $g_smtp_password = getenv('SMTP_PASSWORD') !== false ? getenv('SMTP_PASSWORD') : null;
}

include 'config_inc_addon.php';

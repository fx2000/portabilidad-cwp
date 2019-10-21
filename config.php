<?php
/**
 *
 * Portabilidad Numérica en Campo
 *
 * @copyright     Copyright (c) Móviles de Panamá, S.A. (http://www.movilesdepanama.com)
 * @link          http://portabilidad.appstic.net Portabilidad Numérica en Campo Project
 * @package       Script
 * @since         Portabilidad Numérica en Campo(tm) v 0.1a
 */

// Server URL and app directory
define('DOMAINURL','http://appstic.ddns.net/');
define('DIR', '/var/www/html/portabilidad');

// Set email parameters
define('EMAIL_SERVER', 'ssl://mail.clubprepago.com');
define('EMAIL_FROM', 'noreply@clubprepago.com');
define('EMAIL_USER', 'noreply@clubprepago.com');
define('EMAIL_PASSWORD', 'noreplyClub2049');
define('EMAIL_SENDER_NAME', 'Club Prepago Celular');
define('EMAIL_CWP', 'portabilidad@mailinator.com');

// Set ODK Aggregate parameters
define('HOST', 'http://181.197.173.227/');
define('USER','aggregate');
define('PASSWORD', 'romsDaniel2012');

// Set Mysql Server parameters
define('MYSQL_SERVER', '181.197.173.227');
define('MYSQL_USER', 'portabilidad');
define('MYSQL_PASSWORD', 'Danielito1980');
define('MYSQL_DATABASE', 'aggregate');
define('MYSQL_TABLE', 'PORTABILIDAD_CORE');

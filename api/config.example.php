<?php
/**
 * Database connection settings.
 * Copy this file to api/config.php and fill in your values. config.php is git-ignored.
 *
 * TiDB Cloud (Serverless): open your cluster -> "Connect" -> Connect With: General, Operating System: any.
 *   host : gateway01.<region>.prod.aws.tidbcloud.com
 *   port : 4000
 *   user : <prefix>.root        (TiDB usernames include a cluster prefix before the dot)
 *   pass : the password you generated in the Connect dialog
 *   ssl  : true                 (TiDB Serverless requires TLS)
 *
 * Local XAMPP: host 'localhost', port 3306, user 'root', pass '', ssl false.
 */
return [
    'host'   => 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com',
    'port'   => 4000,
    'db'     => 'one_optics',
    'user'   => 'xxxxxxxxxxxxxxxx.root',
    'pass'   => 'YOUR_TIDB_PASSWORD',
    'ssl'    => true,
    // CA bundle used to verify the server certificate. XAMPP ships one at this path.
    'ssl_ca' => 'C:/xampp/apache/bin/curl-ca-bundle.crt',
];

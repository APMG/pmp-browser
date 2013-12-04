<?php

/*

 PMP Browser
 Copyright 2013 American Public Media Group
 See LICENSE for terms.

 A simple wrapper around the PHP SDK.
 Requires a file 'pmp-config.php' in the same directory as this file, containing the following variables:
 $host, $client_id, $client_secret

*/

// include libraries
require_once dirname(realpath(__FILE__)) . '/phpsdk/lib/Pmp/Sdk/AuthClient.php';

use \Pmp\Sdk\AuthClient as AuthClient;
use \Pmp\Sdk\CollectionDocJson as CollectionDocJson;
use \Pmp\Sdk\Exception as Exception;


/**
 * main()
 */
function pmp_browser_run() {
    include dirname(realpath(__FILE__)) . '/pmp-config.php';
    $client = new AuthClient($host, $client_id, $client_secret);
    $params = pmp_browser_build_params();
    //print_r($params);
    header('Content-Type: application/json');
    if (!$params) {
        header('X-PMP-Browser: invalid parameters', false, 400);
        print json_encode(array('error' => 'No valid parameters found'));
        exit();
    }
    $results = CollectionDocJson::search($host, $client, $params);
    $response = array(
        'query' => $params,
    );
    if (!$results) {
        header('X-PMP-Browser: no results', false, 404);
        $response['total'] = 0;
    }
    else {
        $response['results'] = $results->items()->toArray();
        $response['uri']     = $results->getUri();
        $navself = $results->links('navigation')->rels(array("urn:pmp:navigation:self"));
        $response['total']   = $navself[0]->totalitems;
    }
    if (isset($_GET['raw'])) {
        $response['raw'] = $results;
    }
    print json_encode($response);
    exit();
}


/**
 * Parse $_GET into PMP-friendly string.
 *
 * @return array $params
 */
function pmp_browser_build_params() {
    $valid_fields = array('tag', 'text', 'title', 'limit', 'offset', 'searchsort', 'collection');
    $params = array();
    foreach ($valid_fields as $field) {
        if (isset($_GET[$field])) {
            $params[$field] = $_GET[$field];
        }
    }
    return $params;
}


// run the app
if (count($_GET)) {
    // if we have ? params
    pmp_browser_run();
}
else {
    // print simple form
?>
<html>
 <head>
  <title>PMP Browser</title>
 </head>
 <body>
  <h1>PMP Browser</h1>
  <form>
   <input name="q" />
   <button>Search</button>
  </form>
 </body>
</html>

<?php
}

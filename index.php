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
require_once dirname(realpath(__FILE__)) . '/phpsdk/lib/Pmp/Sdk/CollectionDocJson.php';
require_once dirname(realpath(__FILE__)) . '/phpsdk/lib/Pmp/Sdk/Exception.php';

use \Pmp\Sdk\AuthClient as AuthClient;
use \Pmp\Sdk\CollectionDocJson as CollectionDocJson;
use \Pmp\Sdk\Exception as Exception;

// detect whether we are called via XHR
$is_xhr = false;
if ((isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
    )
    ||
    (isset($_POST['X-PMPB-Requested-With'])
        && strtolower($_POST['X-PMPB-Requested-With']) == 'xmlhttprequest'
    )
) {
    $is_xhr = true;
}

$this_url = $_SERVER['REQUEST_URI']; // under Apache

/**
 * main()
 */
function pmpb_run() {
    include dirname(realpath(__FILE__)) . '/pmp-config.php';
    $client = new AuthClient($host, $client_id, $client_secret);
    $params = pmpb_build_params();
    //print_r($params);
    header('Content-Type: application/json');
    if (!$params) {
        header('X-PMPB: invalid parameters', false, 400);
        print json_encode(array('error' => 'No valid parameters found'));
        exit();
    }
    $results = CollectionDocJson::search($host, $client, $params);
    $response = array(
        'query' => $params,
    );
    if (!$results) {
        header('X-PMPB: no results', false, 404);
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
function pmpb_build_params() {
    $valid_fields = array('tag', 'text', 'limit', 'offset', 'searchsort', 'collection');
    $params = array();
    foreach ($valid_fields as $field) {
        if (isset($_GET[$field])) {
            $params[$field] = $_GET[$field];
        }
    }
    return $params;
}


// run the app if called as ajax
if ($is_xhr) {
    pmpb_run();
}
?>
<html>
 <head>
  <title>PMP Browser</title>
  <link rel="stylesheet" type="text/css" href="pmp-browser.css" />
  <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
  <script type="text/javascript" src="pmp-browser.js"></script>
 </head>
 <body>
  <h1>PMP Browser</h1>
  <form>
   <input name="text"  />
   <button>Search</button>
  </form>
  <div id="results"></div>
  <?php if (count($_GET)) { ?>
  <script type="text/javascript">
    $(document).ready(function() {
        PMPB.search('<?php echo $this_url ?>');
    });
  </script>
  <?php } ?>
 </body>
</html>


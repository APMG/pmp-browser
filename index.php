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
    ||
    (isset($_GET['xhr']) && $_GET['xhr'])
) {
    $is_xhr = true;
}

$this_url = $_SERVER['REQUEST_URI']; // under Apache


/**
 * Proxy GET params to PMP search.
 */
function pmpb_search() {
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
        header('X-PMPB: no results', false, 200);
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
 *
 *
 * @param unknown $url
 */
function pmpb_show_doc($url) {
    include dirname(realpath(__FILE__)) . '/pmp-config.php';
    $client = new AuthClient($host, $client_id, $client_secret);
    $doc = new CollectionDocJson($url, $client);
    header('Content-Type: application/json');
    print json_encode($doc);
}


/**
 * Parse $_GET into PMP-friendly string.
 *
 * @return array $params
 */
function pmpb_build_params() {
    $valid_fields = array('tag', 'text', 'profile', 'limit', 'offset', 'searchsort', 'collection');
    $params = array();
    foreach ($valid_fields as $field) {
        if (isset($_GET[$field]) && strlen($_GET[$field])) {
            $params[$field] = $_GET[$field];
        }
    }
    if (!isset($params['limit'])) {
        $params['limit'] = 10;
    }
    return $params;
}


// run the app if called as ajax
if ($is_xhr) {
    pmpb_search();
}
else if (isset($_GET['doc'])) {
    pmpb_show_doc($_GET['doc']);
    exit();
}

$params = pmpb_build_params();

?>
<html>
 <head>
  <title>PMP Browser</title>
  <link rel="stylesheet" type="text/css" href="pmp-browser.css" />
  <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
  <script type="text/javascript" src="pmp-browser.js"></script>
 </head>
 <body>
 <div id="main">
  <h1>PMP Browser</h1>
  <form>
  <table class="search">
   <tr><th>Title/Content:</th><td><input name="text" value="<?php echo isset($params['text']) ? htmlspecialchars($params['text']) : '' ?>" /></td></tr>
   <tr><th>Tag:</th><td><input name="tag" value="<?php echo isset($params['tag']) ? htmlspecialchars($params['tag']) : '' ?>" /></td></tr>
   <tr><th>Profile:</th><td><input name="profile" value="<?php echo isset($params['profile']) ? htmlspecialchars($params['profile']) : '' ?>" /> (e.g. "story" "media" "audio" "video" "user" "organization")</td></tr>
   <tr><th>Results per page:</th><td>
    <select name="limit">
   <?php foreach (array(10,25,50,100) as $n) { 
         echo '<option';
         if ($params['limit'] == $n) { echo ' selected="selected"'; }
         echo ">$n</option>\n";
     }
   ?>
    </select>
   </td></tr>
   <tr><th></th><td><button>Search</button></td></tr>
  </table>
  </form>
  <div id="results"></div>
  <?php if (count($_GET)) { ?>
  <script type="text/javascript">
    $(document).ready(function() {
        PMPB.search('<?php echo $this_url ?>');
    });
  </script>
  <?php } ?>
 </div>
 </body>
</html>

<?php

/*

 PMP Browser
 Copyright 2013 American Public Media Group
 See LICENSE for terms.

 A simple wrapper around the PHP SDK.
 Requires a file 'pmp-config.php' in the same directory as this file (or specified via server environment),
 containing the following variables:
 $host, $client_id, $client_secret

*/

require 'vendor/autoload.php';

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
 * Returns path to config file. Can be set with PMP_BROWSER_CONFIG environment variable.
 *
 * @return unknown
 */


function pmpb_get_config_path() {
    if (isset($_SERVER['PMP_BROWSER_CONFIG'])) {
        return $_SERVER['PMP_BROWSER_CONFIG'];
    }
    else {
        return dirname(realpath(__FILE__)) . '/pmp-config.php';
    }
}


/**
 *
 */
function pmpb_load_header() {
    $header_file = dirname(realpath(__FILE__)) . '/header.php';
    if (isset($_SERVER['PMP_BROWSER_HEADER'])) {
        $header_file = $_SERVER['PMP_BROWSER_HEADER'];
    }
    //error_log("header: $header_file");
    if (file_exists($header_file)) {
        include $header_file;
    }
}


/**
 *
 */
function pmpb_load_footer() {
    $footer_file = dirname(realpath(__FILE__)) . '/footer.php';
    if (isset($_SERVER['PMP_BROWSER_FOOTER'])) {
        $footer_file = $_SERVER['PMP_BROWSER_FOOTER'];
    }
    if (file_exists($footer_file)) {
        include $footer_file;
    }
}


/**
 * Proxy GET params to PMP search.
 */
function pmpb_search() {

    // check input
    $params = pmpb_build_params();
    //print_r($params);
    header('Content-Type: application/json');
    if (!$params) {
        header('X-PMPB: invalid parameters', false, 400);
        print json_encode(array('error' => 'No valid parameters found'));
        exit();
    }

    // query server
    include pmpb_get_config_path();
    $response = array(
        'query' => $params,
    );
    try {
        $client = new AuthClient($host, $client_id, $client_secret);
        $results = CollectionDocJson::search($host, $client, $params);
        if (!$results) {
            header('X-PMPB: no results', false, 200);
            $response['total'] = 0;
        }
        else {
            $response['results'] = $results->items()->toArray();
            $response['uri']     = $results->getUri();
            $navself = $results->links('navigation')->rels(array("self"));
            $response['total']   = $navself[0]->totalitems;
        }
    }
    catch (Exception $err) {
        $response['error'] = $err;
    }
    if (isset($_GET['raw'])) {
        $response['raw'] = $results;
    }
    print json_encode($response);
    exit();
}


/**
 *
 * @return unknown
 */
function pmpb_get_app_uri() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443)
        ? "https"
        : "http";
    $path = preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']);
    return sprintf("%s://%s%s", $protocol, $_SERVER['HTTP_HOST'], $path);
}


/**
 *
 * @param unknown $url
 */
function pmpb_show_doc($url) {
    include pmpb_get_config_path();
    $client = new AuthClient($host, $client_id, $client_secret);
    $doc = new CollectionDocJson($url, $client);
    header('Content-Type: application/json');
    $json = json_encode($doc, JSON_UNESCAPED_SLASHES);

    // set $proxy_links in config to turn this on
    if (isset($proxy_links) && $proxy_links) {
        $pattern = '/(' . preg_quote($host, '/') . ')/';
        $replacement = sprintf("%s?doc=$1", pmpb_get_app_uri());
        //error_log("$pattern => $replacement");
        $json = preg_replace($pattern, $replacement, $json);
    }

    print $json;
}


/**
 * Parse $_GET into PMP-friendly string.
 *
 * @return array $params
 */
function pmpb_build_params() {
    // get valid fields from PMP home doc 'urn:collectiondoc:query:docs'
    // the home doc is public (no authn needed) so just grab it with the RestAgent
    include pmpb_get_config_path();
    $ragent       = new \restagent\Request;
    $ragent->timeout(20000);
    $resp         = $ragent->get($host); // $host from config
    $valid_fields = array();
    if ($resp['code'] == 200) {
        $home_doc = json_decode($resp['data'], true);
        foreach ( $home_doc['links']['query'] as $qlink ) {
            if ($qlink['rels'][0] == 'urn:collectiondoc:query:docs') {
                $valid_fields = array_keys($qlink['href-vars']);
            }
        }
    }
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
elseif (isset($_GET['doc'])) {
    pmpb_show_doc($_GET['doc']);
    exit();
}

include pmpb_get_config_path();
$params = pmpb_build_params();

?>
<html>
 <head>
  <title>PMP Browser</title>
  <link rel="stylesheet" type="text/css" href="wPaginate.css" />
  <link rel="stylesheet" type="text/css" href="pmp-browser.css" />
  <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
  <script type="text/javascript" src="pmp-browser.js"></script>
  <script type="text/javascript" src="wPaginate.js"></script>
 </head>
 <body>
 <?php pmpb_load_header(); ?>
 <div id="main">
  <a href="http://pmp.io/"><img id="pmp-logo" src="http://publicmediaplatform.org/wp-content/uploads/logo.png" /></a>
  <h1 title="Fork me"><a href="https://github.com/APMG/pmp-browser">PMP Browser</a></h1>
  <form>
  <table class="search">
   <tr><th>PMP API</th><td><a href="<?php echo $host ?>"><?php echo $host ?></a></td></tr>
   <tr><th>Title/Content:</th><td><input name="text" value="<?php echo isset($params['text']) ? htmlspecialchars($params['text']) : '' ?>" /></td></tr>
   <tr><th>Tag:</th><td><input name="tag" value="<?php echo isset($params['tag']) ? htmlspecialchars($params['tag']) : '' ?>" /></td></tr>
   <tr><th>Profile:</th><td><input name="profile" value="<?php echo isset($params['profile']) ? htmlspecialchars($params['profile']) : '' ?>" />
     <div class="help"> (e.g. "story" "media" "audio" "video" "user" "organization")</div>
    </td></tr>
   <tr><th>Results per page:</th><td>
    <select name="limit">
<?php
foreach (array(10, 25, 50, 100) as $n) {
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
  <div id="pager"></div>
  <div id="results"></div>
  <?php if (count($_GET)) { ?>
  <script type="text/javascript">
    $(document).ready(function() {
        PMPB.search('<?php echo $this_url ?>');
    });
  </script>
  <?php } ?>
 </div>
 <?php pmpb_load_footer(); ?>
 </body>
</html>

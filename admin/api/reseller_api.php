<?php
include 'class.resellerapi.php';
/**
 * Xtream UI Reseller API
 */ 
 
foreach ($_GET as $rKey => $rValue) {
    $_POST[$rKey] = $rValue;
}
 
if (isset($_POST['api_key']) && !empty($_POST['api_key'])) {
    $action = $_POST['action'];
    $return = array();
    if (!$action) {
        $return['result'] = 'error';
        $return['message'] = 'Undefined Action, Please check the API Action.';
        echo json_encode($return);
        exit;
        // Default to index if no action specified
        $action = 'index';
    }
    $controller = new Controller();
    // Verify requested action is valid and callable
    if (is_callable(array($controller, $action))) {
        echo $controller->$action($_POST);
        exit;
    } else {
        $return['result'] = 'error';
        $return['message'] = 'Undefined Action, Please check the API Action.';
        echo json_encode($return);
        exit;
    }
}
?>
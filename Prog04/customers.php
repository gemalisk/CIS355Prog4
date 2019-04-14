<?php
session_start();

if (!$_SESSION) {
	session_destroy();
	header("Location: login.php");
	exit();
}

require "database.php";
require "customer.class.php";
$cust = new Customer();
 
// set active record field values, if any 
// (field values not set for display_list and display_create_form)
if(isset($_GET["id"]))          $id = $_GET["id"]; 
if(isset($_POST["name"]))       $cust->name = htmlspecialchars($_POST["name"]);
if(isset($_POST["email"]))      $cust->email = htmlspecialchars($_POST["email"]);
if(isset($_POST["mobile"]))     $cust->mobile = htmlspecialchars($_POST["mobile"]);
// we don't need htmlspecialchars() because password is never displayed in browswer!
if(isset($_POST["password"]))     $cust->password = $_POST["password"];
// "fun" is short for "function" to be invoked 
if(isset($_GET["fun"])) $fun = $_GET["fun"];
else $fun = "display_list"; 
switch ($fun) {
    case "list_pics":		$cust->list_pics();
    	break;
    case "display_list":        $cust->list_records();
        break;
    case "display_create_form": $cust->create_record(); 
        break;
    case "display_read_form":   $cust->read_record($id); 
        break;
    case "display_update_form": $cust->update_record($id);
        break;
    case "display_delete_form": $cust->delete_record($id); 
        break;
    case "insert_db_record":    $cust->insert_db_record(); 
        break;
    case "update_db_record":    $cust->update_db_record($id);
        break;
    case "delete_db_record":    $cust->delete_db_record($id);
        break;
    default: 
        echo "Error: Invalid function call (customers.php)";
        exit();
        break;
}
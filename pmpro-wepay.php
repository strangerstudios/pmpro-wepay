<?php
/*
Plugin Name: WePay Gateway for Paid Memberships Pro
Description: WePay Gateway for Paid Memberships Pro
Version: .1
*/

define("PMPRO_WEPAY_DIR", dirname(__FILE__));

//load payment gateway class
require_once(PMPRO_WEPAY_DIR . "/classes/class.pmprogateway_wepay.php");
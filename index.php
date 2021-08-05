<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
define('DB_TABLE_PREFIX', 'test_');
define('ICIOANNIDISBOT_DEBUG_LEVEL', 10);
define("__LIB__", '/Users/drweb_000/Desktop/MyData/phplib/');

require_once __LIB__ . "lhTestingSuite/classes/lhSelfTestingClass.php";
require_once __LIB__ . "lhTestingSuite/classes/lhTest.php";
require_once __LIB__ . "lhChatterBoxDataProviders/abstract/lhAbstractSession.php";
require_once __LIB__ . "lhChatterBoxDataProviders/classes/lhAIML.php";
require_once __LIB__ . "lhChatterBoxDataProviders/classes/lhCSML.php";
require_once __LIB__ . "lhChatterBoxDataProviders/classes/lhDBSession.php";
require_once __LIB__ . "lhChatterBox20/classes/lhChatterBox.php";
require_once __LIB__ . "lhSimpleMessage/classes/lhSimpleMessage.php";
require_once __LIB__ . "lhSimpleMessage/classes/lhSimpleMessageHint.php";
require_once __LIB__ . "lhSimpleMessage/classes/lhSimpleMessageAttachment.php";
require_once __LIB__ . "lhRuNames/classes/lhRuNames.php";
require_once __LIB__ . "lhTextConv/lhTextConv.php";
require_once __DIR__ . '/icIoannidisBot/classes/icTestCommand.php';

lhSelfTestingClass::$logfile = __DIR__ . '/logfile.log';

require_once 'icIoannidisBot/classes/icIoannidisBot.php';
require_once 'secrets.php';


$dbconn = new PDO('mysql:host=localhost;dbname=test;charset=UTF8', TEST_DB_USER, TEST_DB_PASSWORD);
$csml = new lhCSML('csml.xml');
$aiml = new lhAIML('aiml.xml');

$session = new lhDBSession(11, $dbconn, DB_TABLE_PREFIX);
$session->destroy();

$chatterbox = new icIoannidisBot($dbconn, $csml, $aiml);
$chatterbox->_test();
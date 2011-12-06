<?php

/*
 * Copyright 2011 Mo McRoberts.
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */

error_reporting(E_ALL);
ini_set('display_errors', 'On');

$stderr = fopen('php://stderr', 'w');
if(!isset($argv[2]))
{
	fprintf($stderr, "Usage: %s INPUT.xml OUTPUT.rtf\n", basename($argv[0]));
	exit(1);
}

require_once(dirname(__FILE__) . '/db2rtf/rtf.php');
require_once(dirname(__FILE__) . '/db2rtf/docbook.php');

$db = new DocBookProcessor($argv[1]);

$rtf = new RTF();
require(dirname(__FILE__) . '/db2rtf/styles.php');
$db->process($rtf);
$rtf->write($argv[2]);

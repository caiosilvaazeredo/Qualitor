<?php
	
	require_once 'request.class.php';

    $request = new request();

    print_r($request->requestprocess($_REQUEST));
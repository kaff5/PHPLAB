<?php

include_once 'headers/setHTTPStatus.php';

function checkUserAndPasswordNull($pwd, $username)
{
    if (empty($pwd) || empty($username))
    {
        setHTTPStatus("400","Strange data");
        exit();
    }
}

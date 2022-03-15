<?php

include_once 'checksDataUser.php';

function route($method, $urlList, $requestData, $link)
{

    if ($method === "POST" && $urlList[0] === "login")
    {
        login($link, $requestData);
    }
}


function login($link, $requestData)
{
    $pwd = $requestData->body->password;
    $username = $requestData->body->username;

    checkUserAndPasswordNull($pwd,$username);
    $res = $link->query("SELECT userId FROM users WHERE username = '$username' AND password = '$pwd'");
    if (!$res) //SQL
    {
        setHTTPStatus("500","Unexpected error");
    } else {
        $userId = $res->fetch_assoc()["userId"];
        if (!empty($userId)) {
            $token = (bin2hex(random_bytes(10)));
            $res = $link->query("UPDATE users SET token='$token' WHERE username = '$username' AND password = '$pwd'");
            if (!$res) //SQL
            {
                setHTTPStatus("500","Unexpected error");
            } else
            {
                setHTTPStatus();
                echo json_encode(["token" => $token]);
            }
        }
        else
        {
            setHTTPStatus("403","Username or password are wrong.");
        }
    }
    exit();
}

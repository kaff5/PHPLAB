<?php

include_once 'headers/setHTTPStatus.php';

function route($method, $urlList, $requestData, $link)
{

    if ($method === "POST" && $urlList[0] === "register")
    {
        if (!empty($requestData->body->password) && !empty($requestData->body->name) && !empty($requestData->body->surname) && !empty($requestData->body->username))
        {
            register($link, $requestData);
        }
        else
        {
            setHTTPStatus("403","Strange data");
        }
    }
}


function register($link, $requestData)
{
    $pwd = $requestData->body->password;
    $name = $requestData->body->name;
    $surname = $requestData->body->surname;
    $username = $requestData->body->username;

    $token = (bin2hex(random_bytes(10)));
    $res = $link->query("INSERT INTO `users`(`userName`, `name`, `surname`, `password`, token) VALUES ('$username','$name','$surname','$pwd','$token')");
    if (!$res) //SQL
    {
        setHTTPStatus("400", "User with the same username already exist");
    }
    else {
        setHTTPStatus();
        echo json_encode(["token"=>$token]);
    }
    exit();
}

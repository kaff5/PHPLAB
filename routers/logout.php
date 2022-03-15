<?php

include_once 'headers/setHTTPStatus.php';

function route($method, $urlList, $requestData, $link)
{

    if ($method === "POST" && $urlList[0] === "logout")
    {
        $token = substr(getallheaders()['Authorization'], 7);
        logout($token,$link);

    }
}


function logout($token, $link)
{
    if (!empty($token))
    {
        $userId = checkTokenIsThereInTable($token,$link);
        if ($userId != null)
        {
            $token = (bin2hex(random_bytes(10)));
            $res = $link->query("UPDATE users SET token='$token' WHERE userId = $userId");
            if (!$res) //SQL
            {
                setHTTPStatus("500","Unexpected error");
                exit();
            }
            else
            {
                setHTTPStatus("200","User logged out");
            }
        }
        else
        {
            setHTTPStatus("403","User already logged out");
            exit();
        }
    }
    else
    {
        setHTTPStatus("400","Strange data");
        exit();
    }
}


function checkTokenIsThereInTable($token, $link)
{
    $res = $link->query("SELECT `userId` FROM `users` WHERE token = '$token'");
    if (!$res) //SQL
    {
        echo "Не удалось выполнить запрос: (" . $link->errno . ") " . $link->error;
    }
    else
    {
        if ($link->affected_rows == 0 || $link->affected_rows == -1) {
            return null;
        } else {
            return $res->fetch_assoc()['userId'];
        }
    }
}
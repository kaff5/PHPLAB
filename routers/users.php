<?php

include_once 'headerUser/headerUs.php';
include_once 'headers/setHTTPStatus.php';

function route($method, $urlList, $requestData, $link)
{


    switch ($method) {
        case "GET":
        {
            switch (count($urlList)) {
                case 1:
                {
                    if (empty(getallheaders()['Authorization']))
                    {
                        setHTTPStatus("403","Authorization token are invalid");
                    }
                    else
                    {
                        if (checkAdmin($link))
                        {
                            setHTTPStatus();
                            echo json_encode(getUsers($link));
                        }
                        else
                        {
                            setHTTPStatus("403","Authorization token are invalid");
                        }
                    }
                    exit();
                    return;
                }
                case 2:
                {
                    if (empty(getallheaders()['Authorization'])) {
                        setHTTPStatus("403","Authorization token are invalid");
                        exit();
                    } else if (checkAdmin($link) || checkInformationAboutMe($link, $urlList[1])) {
                        echo json_encode(getUserID($urlList[1], $link));
                        exit();
                    } else {
                        setHTTPStatus("403","Authorization token are invalid");
                        exit();
                    }
                    return;
                }
                default:
                {
                    echo('{"message" : "Uncorrected GET path"}');
                    return;
                }
            }
        }
        case "POST":
        {
            if (empty(getallheaders()['Authorization'])) {
                setHTTPStatus("403","Authorization token are invalid");
                exit();
            } else if (checkAdmin($link) && $urlList[2] === "role") {
                setHTTPStatus();
                echo json_encode(postUserID($urlList[1], $link, $requestData));
                exit();
            } else {
                setHTTPStatus("403","Authorization token are invalid");
                exit();
            }
            return;
        }
        case "PATCH":
        {
            if (empty(getallheaders()['Authorization'])) {
                setHTTPStatus("403","Authorization token are invalid");
                exit();
            } else if (checkInformationAboutMe($link, $urlList[1])) {
                setHTTPStatus();
                echo json_encode(patchUserID((int)$urlList[1], $link, $requestData));
                exit();
            } else {
                setHTTPStatus("403","Authorization token are invalid");
                exit();
            }
            return;
        }
        case "DELETE":
        {
            if (empty(getallheaders()['Authorization'])) {
                setHTTPStatus("403","Authorization token are invalid");
                exit();
            } else if (checkAdmin($link)) {
                setHTTPStatus();
                echo json_encode(deleteUserID((int)$urlList[1], $link));
                exit();
            } else {
                setHTTPStatus("403","Authorization token are invalid");
                exit();
            }
            return;
        }
        default:
        {
            echo('{"message" : "Uncorrected GET path"}');
            return;
        }
    }
}



function getUsers($link)
{
    $message = [];
    $message["users"] = [];
    $res = $link->query("SELECT userId,username,roleId FROM users ORDER BY userId ASC");
    if (!$res) //SQL
    {
        setHTTPStatus("500", "Unexpected error");
        exit;
    } else {
        while ($row = $res->fetch_assoc()) {
            $message["users"][] = [
                "userId" => $row['userId'],
                "username" => $row['username'],
                "roleId" => $row['roleId']
            ];
        }
    }

    return $message["users"];
}


function getUserID($id, $link)
{
    if (empty($id))
    {
        setHTTPStatus("400","Strange data");
        exit;
    }

    $message = [];
    $message["users"] = [];
    $res = $link->query("SELECT userId,username,roleId,name,surname FROM users WHERE userid = $id");
    if (!$res) //SQL
    {
        setHTTPStatus("500", "Unexpected error");
    } else {
        while ($row = $res->fetch_assoc()) {


            $message["users"][] = [
                "userId" => $row['userId'],
                "username" => $row['username'],
                "roleId" => $row['roleId'],
                "name" => $row['name'],
                "surname" => $row['surname']
            ];
        }
    }
    if (empty($message["users"][0]))
    {
        setHTTPStatus("403", "Invalid id");
        exit();
    }
    setHTTPStatus();
    return $message["users"][0];
}

function patchUserID($id, $link, $requestData)
{
    $paramStr = "";
    $param = ['password','username','name','surname'];
    foreach ($requestData->body as $key=> $value)
    {
        if (!in_array($key,$param))
        {
            setHTTPStatus("400","$key doesnt exist");
        }
        else {
            $paramStr .= "`$key` = '$value', ";
        }
    }

    $paramStr = rtrim($paramStr, ', ');

    $message = [];
    $message["users"] = [];
    $res = $link->query("UPDATE users SET $paramStr WHERE userId = $id");
    if (!$res) //SQL
    {
        echo $link->error;
        //setHTTPStatus("500", "Unexpected error");
        exit;
    } else {
        $res = $link->query("SELECT userId,username,roleId,name,surname FROM users WHERE userid = $id");
        if (!$res) //SQL
        {
            setHTTPStatus("500", "Unexpected error");
            exit;
        } else {
            while ($row = $res->fetch_assoc()) {


                $message["users"][] = [
                    "userId" => $row['userId'],
                    "username" => $row['username'],
                    "roleId" => $row['roleId'],
                    "name" => $row['name'],
                    "surname" => $row['surname']
                ];
            }
        }
    }
    return $message["users"][0];
}


function deleteUserID($id, $link)
{
    $res = $link->query("DELETE FROM users WHERE userId = $id");
    if (!$res) //SQL
    {
        setHTTPStatus("500", "Unexpected error");
        exit;
    } else {
        return (['message' => "OK"]);
    }
}


function postUserID($id, $link, $requestData)
{
    $roleId = $requestData->body->roleId;
    if (empty($roleId))
    {
        setHTTPStatus("400", "Strange data");
        exit();
    }

    $res = $link->query("UPDATE users SET roleId = $roleId WHERE userId = $id");
    if (!$res) //SQL
    {
        setHTTPStatus("500", "Unexpected error");
        exit;
    } else {
        return (['message' => "OK"]);
    }
}

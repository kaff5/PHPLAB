<?php

include_once 'headerUser/headerUs.php';
include_once 'headers/setHTTPStatus.php';

function route($method, $urlList, $requestData,$link)
    {

        switch ($method)
        {
            case "GET":
            {
                switch (count($urlList))
                {
                    case 1:
                    {
                        if (empty(getallheaders()['Authorization'])) {
                            setHTTPStatus("403","Authorization token are invalid");
                            exit();
                        } else if (checkHaveUser($link)) {
                            setHTTPStatus();
                            echo json_encode(getRoles($link));
                            exit();
                        } else {
                            setHTTPStatus("403","Authorization token are invalid");
                            exit();
                        }
                        return;
                    }
                    case 2:
                    {
                        if (empty(getallheaders()['Authorization'])) {
                            setHTTPStatus("403","Authorization token are invalid");
                            exit();
                        } else if (checkHaveUser($link)) {
                            echo json_encode(getRoleId($urlList[1], $link));
                        } else {
                            setHTTPStatus("403","Authorization token are invalid");
                            exit();
                        }
                        return;
                    }
                }
            }
            default:
            {
                echo('{"message" : "Uncorrected GET path"}');
                return;
            }
        }
    }

    function getRoles($link)
    {
        $message = [];
        $res = $link->query("SELECT nameRole,roleId FROM roles ORDER BY roleId ASC");
        if (!$res) //SQL
        {
            setHTTPStatus("500", "Unexpected error");
            exit;
        } else {
            while ($row = $res->fetch_assoc()) {
                $message[] = [
                    "roleId" => $row['roleId'],
                    "nameRole" => $row['nameRole']
                ];
            }
        }

        return $message;
    }



    function getRoleID($id,$link)
    {
        $message = [];
        $res = $link->query("SELECT nameRole,roleId FROM roles WHERE roleId = $id");
        if (!$res) //SQL
        {
            setHTTPStatus("500", "Unexpected error");
            exit;
        } else {
            while ($row = $res->fetch_assoc()) {
                $message = [
                    "roleId" => $row['roleId'],
                    "nameRole" => $row['nameRole']
                ];
            }
        }
        if (empty($message))
        {
            setHTTPStatus("400", "Strange data");
            exit;
        }

        return $message;
    }
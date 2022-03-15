<?php

include_once 'getsForSolutions.php';
include_once 'headerUser/headerUs.php';
include_once 'headers/setHTTPStatus.php';

function route($method, $urlList, $requestData, $link)
{
    $taskId = $requestData->parameters["task"];
    $userId = $requestData->parameters["user"];

    switch ($method) {
        case "GET":
        {
            if (count($urlList) == 1) {
                echo json_encode(getSolutions($link, $taskId, $userId));
                return;
            }
        }
        case "POST":
        {
            if (empty(getallheaders()['Authorization'])) {
                setHTTPStatus("403", "Authorization token are invalid");
            } else {

                if (checkAdmin($link) && count($urlList) == 3 && $urlList[2] == "postmoderation") {
                    setHTTPStatus();
                    echo json_encode(postmoder($urlList[1], $link, $requestData));
                } else {
                    setHTTPStatus("403", "Authorization token are invalid");
                }
            }
            exit();
        }
        default:
        {
            echo('{"message" : "Uncorrected GET path"}');
            return;
        }
    }
}

function postmoder($id, $link, $requestData)
{

    $verdict = $requestData->body->verdict;

    if (!checkSolutionInTheTable($id,$link) || empty($verdict) || ($verdict != "Pending" && $verdict != "OK" && $verdict != "Rejected")) {
        setHTTPStatus("400", "Strange data");
        exit;
    }

    $res = $link->query("UPDATE `solutions` SET `verdict`='$verdict' WHERE solutionId = $id");
    if (!$res) //SQL
    {
        setHTTPStatus("500", "Unexpected error");
        exit();
    }
    $res = $link->query("SELECT taskId FROM solutions WHERE solutionId = $id")->fetch_assoc();
    if (!$res) //SQL
    {
        setHTTPStatus("500", "Unexpected error");
        exit();
    } else {
        return getTaskByIdForSolutions($res["taskId"], $link);
    }
}


function checkSolutionInTheTable($id, $link)
{
    $link->query("SELECT solutionId FROM solutions WHERE solutionId = $id");
    if ($link->affected_rows == 0 || $link->affected_rows == -1) {
        setHTTPStatus("400", "Strange data");
        exit();
    } else {
        return true;
    }
}



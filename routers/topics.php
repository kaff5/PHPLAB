<?php

include_once "headerUser/headerUs.php";
include_once "headers/setHTTPStatus.php";


function route($method, $urlList, $requestData, $link)
{
    $nameTopic = $requestData->parameters["name"];
    $parentId = $requestData->parameters["parent"];
    switch ($method) {
        case "GET":
        {
            switch (count($urlList)) {
                case 1:
                {
                    setHTTPStatus();
                    echo json_encode(getTopics($link, $nameTopic, $parentId));
                    return;

                }
                case 2:
                {
                    if (is_numeric($urlList[1])) {
                        setHTTPStatus();
                        echo json_encode(getTopicById((int)$urlList[1], $link));
                        return;
                    }
                }
                case 3:
                {
                    if (is_numeric($urlList[1]) && $urlList[2] == "childs") {
                        setHTTPStatus();
                        echo json_encode(getTopicChildsById((int)$urlList[1], $link));
                        return;
                    }
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
            }
            else
            {
                if (checkAdmin($link)) {
                    switch (count($urlList)) {
                        case 1:
                        {
                            setHTTPStatus();
                            echo json_encode(postTopic($link, $requestData));
                            return;
                        }
                        case 3:
                        {
                            if ($urlList[2] == "childs") {
                                setHTTPStatus();
                                echo json_encode(changeParentId($link, $urlList[1], $requestData));
                                return;
                            }
                        }
                        default:
                        {
                            echo('{"message" : "Uncorrected GET path"}');
                            return;
                        }
                    }
                }
                else
                {
                    setHTTPStatus("403","Authorization token are invalid");
                    exit();
                }
            }
            return;

        }
        case "PATCH":
        {
            if (empty(getallheaders()['Authorization'])) {
                setHTTPStatus("403","Authorization token are invalid");
                exit();
            } else {
                if (checkAdmin($link) && count($urlList) == 2) {
                    setHTTPStatus();
                    echo patchTopicId((int)$urlList[1], $link, $requestData);
                } else {
                    setHTTPStatus("403","Authorization token are invalid");
                    exit();
                }
            }
            return;
        }
        case "DELETE":
        {

            if (empty(getallheaders()['Authorization'])) {
                setHTTPStatus("403","Authorization token are invalid");
                exit();
            } else {
                if (checkAdmin($link)) {
                    switch (count($urlList)) {
                        case 2:
                        {
                            setHTTPStatus();
                            echo json_encode(deleteTopic((int)$urlList[1], $link));
                            return;
                        }
                        case 3:
                        {
                            if ($urlList[2] == "childs") {
                                setHTTPStatus();
                                echo json_encode(deleteChildsTopic($link, $urlList[1], $requestData));
                                return;
                            }
                        }
                        default:
                        {
                            echo('{"message" : "Uncorrected GET path"}');
                            return;
                        }
                    }
                } else {
                    setHTTPStatus("403","Authorization token are invalid");
                    exit();
                }
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

function deleteChildsTopic($link, $id, $requestData)
{
    $masChilds = $requestData->body;
    if (empty($masChilds))
    {
        setHTTPStatus("400","Strange data");
    }
    if (checkTopicIsThereInTable($id, $link) && count($masChilds) != 0) {
        foreach ($masChilds as $value) {
            $res = $link->query("UPDATE `topic` SET `parentId`=null WHERE topicId = $value");
            if (!$res) //SQL
            {
                setHTTPStatus("500", "Unexpected error");
            }
        }
    }
    setHTTPStatus();
    return getTopicById($id, $link);
}

function changeParentId($link, $id, $requestData)
{
    $masChilds = $requestData->body;
    if (empty($masChilds))
    {
        setHTTPStatus("403","Strange data");
        exit();
    }
    if (checkTopicIsThereInTable($id, $link) && count($masChilds) != 0) {
        foreach ($masChilds as $value) {
            $res = $link->query("UPDATE `topic` SET parentId='$id' WHERE topicId = $value");
            if (!$res) //SQL
            {
                setHTTPStatus("405", "Unexpected error");
                exit();
            }
        }
    }
    setHTTPStatus();
    return getTopicById($id, $link);
}

function getTopicChildsById($id, $link)
{
    if (checkTopicIsThereInTable($id, $link)) {
        $mas = getAllTopics($link);
        $childs = [];
        foreach ($mas as $value) {

            if ($value['topicId'] == $id) {
                $childs["childs"] = [];
                foreach ($mas as $k) {
                    if ($value['topicId'] == $k['parentId']) {
                        $childs["childs"][] = [
                            "id" => $k['topicId'],
                            "name" => $k['name'],
                            "parentId" => $value['topicId']
                        ];
                    }

                }
            }
        }
        setHTTPStatus();
        return $childs["childs"];
    }

}

function deleteTopic($id, $link)
{
    if (!checkTopicIsThereInTable($id, $link)) {
        setHTTPStatus("400","Strange data");
    }
    $link->query("DELETE FROM topic WHERE topicId = $id");
    setHTTPStatus();
    return (['message' => "OK"]);
}

function getTopicWithParent($id, $link, $parentId)
{
    $message = [];
    $mas = getAllTopics($link);

    foreach ($mas as $value) {
        $childs = [];
        $childs["childs"] = [];
        if ($value['topicId'] == $parentId) {

            foreach ($mas as $k) {
                if ($value['topicId'] == $k['parentId']) {
                    $childs["childs"][] = [
                        "id" => $k['topicId'],
                        "name" => $k['name'],
                        "parentId" => $value['topicId']
                    ];
                }

            }
            $message["topic"][] = [
                "id" => $value['topicId'],
                "name" => $value['name'],
                "parentId" => $value['parentId'],
                "childs" => $childs['childs']
            ];
        }
    }
    return $message;
}

function getTopicWithoutParent($id, $link)
{
    $message = [];
    $message["topic"] = [];
    $res = $link->query("SELECT topicId,name,parentId FROM topic WHERE topicId = $id");
    while ($row = $res->fetch_assoc()) {
        $message["topic"][] = [
            "topicId" => $row['topicId'],
            "name" => $row['name'],
            "parentId" => $row['parentId']
        ];
    }
    return $message;
}

function patchTopicId($id, $link, $requestData)
{
    $name = $requestData->body->name;
    $parentId = $requestData->body->parentId;
    $paramStr = "";
    $param = ['name','parentId'];
    foreach ($requestData->body as $key=> $value)
    {
        if (!in_array($key,$param))
        {
            setHTTPStatus("400","$key doesnt exist");
        }
        if ($key == "parentId")
        {
            $paramStr .= "`$key` = null, ";
        }
        else {
            $paramStr .= "`$key` = '$value', ";
        }
    }

    $paramStr = rtrim($paramStr, ', ');

    if (checkTopicIsThereInTable($id, $link)) {
        if ($parentId == null) {
            $res = $link->query("UPDATE `topic` SET $paramStr WHERE topicId = $id");
            $message = getTopicById($id, $link);
            return json_encode($message);
        } else {
            $res = $link->query("UPDATE `topic` SET $paramStr WHERE topicId = $id");
            $message = getTopicWithParent($id, $link, $parentId);
            return json_encode($message['topic']);
        }
    } else {
        setHTTPStatus("400", "Strange data");
        exit();
    }
}


function getTopics($link, $nameTopic, $parentId)
{
    $message = [];
    $message["topics"] = [];
    $mas = getAllTopics($link);
    if (!empty($nameTopic) && !empty($parentId))
    {
        foreach ($mas as $row) {
            if ($row["name"] == $nameTopic && $row["parentId"] == $parentId)
                $message["topics"][] = [
                    "id" => $row['topicId'],
                    "name" => $row['name'],
                    "parentId" => $row['parentId']
                ];
        }
        setHTTPStatus();
        return $message["topics"];
    }
    if (!empty($nameTopic)) {
        foreach ($mas as $row) {
            if ($row["name"] == $nameTopic)
                $message["topics"][] = [
                    "id" => $row['topicId'],
                    "name" => $row['name'],
                    "parentId" => $row['parentId']
                ];
        }
        setHTTPStatus();
        return $message["topics"];
    }
    if (!empty($parentId)) {
        foreach ($mas as $row) {
            if ($row["parentId"] == $parentId)
                $message["topics"][] = [
                    "id" => $row['topicId'],
                    "name" => $row['name'],
                    "parentId" => $row['parentId']
                ];
        }
        setHTTPStatus();
        return $message["topics"];
    }
    foreach ($mas as $row) {
        $message["topics"][] = [
            "id" => $row['topicId'],
            "name" => $row['name'],
            "parentId" => $row['parentId']
        ];
    }
    setHTTPStatus();
    return $message["topics"];
}


function getTopicById($id, $link)
{
    if (checkTopicIsThereInTable($id, $link)) {
        $message = [];

        $mas = getAllTopics($link);

        foreach ($mas as $value) {
            $childs = [];
            $childs["childs"] = [];
            if ($value['topicId'] == $id) {

                foreach ($mas as $k) {
                    if ($value['topicId'] == $k['parentId']) {
                        $childs["childs"][] = [
                            "id" => $k['topicId'],
                            "name" => $k['name'],
                            "parentId" => $value['topicId']
                        ];
                    }
                }
                $message["topic"][] = [
                    "id" => $value['topicId'],
                    "name" => $value['name'],
                    "parentId" => $value['parentId'],
                    "childs" => $childs['childs']
                ];
            }
        }
    } else return false;
    setHTTPStatus();
    return $message["topic"];
}


function postTopic($link, $requestData)
{
    $name = $requestData->body->name;
    $parentId = $requestData->body->parentId;
    if (empty($name))
    {
        setHTTPStatus("400", "Strange data");
        exit();
    }
    if ($parentId == null) {
        $res = $link->query("INSERT INTO topic(`name`, `parentId`) VALUES ('$name',null)");
        if (!$res)
        {
            setHTTPStatus("405", "Unexpected error");
            exit();
        }
        $id = $link->insert_id;
        $message = getTopicWithoutParent($id, $link);
    }
    else
    {
        $res = $link->query("INSERT INTO topic(`name`, `parentId`) VALUES ('$name','$parentId')");
        if (!$res)
        {
            echo $link->error;
            //setHTTPStatus("405", "Unexpected error");
            exit();
        }
        $id = $link->insert_id;
        $message = getTopicWithParent($id, $link, $parentId);
    }
    setHTTPStatus();
    return $message["topic"];
}



function checkTopicIsThereInTable($id, $link)
{
    $link->query("SELECT topicId FROM topic WHERE topicId = $id");
    if ($link->affected_rows == 0 || $link->affected_rows == -1) {
        setHTTPStatus("400", "Strange data");
        exit();
    } else {
        return true;
    }
}

function getAllTopics($link)
{
    $mas = [];
    $res = $link->query("SELECT topicId,name,parentId FROM topic ORDER BY topicId ASC");
    while ($row = $res->fetch_assoc()) {
        $mas[] = $row;
    }
    return $mas;
}



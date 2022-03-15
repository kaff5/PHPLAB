<?php
include_once 'getsForSolutions.php';
include_once 'headerUser/headerUs.php';
include_once 'headers/setHTTPStatus.php';

function route($method, $urlList, $requestData, $link)
{
    $nameTask = $requestData->parameters["name"];
    $topicId = $requestData->parameters["topic"];


    switch ($method) {
        case "GET":
        {
            switch (count($urlList)) {
                case 1:
                {
                    echo getTasks($link, $nameTask, $topicId);
                    return;
                }
                case 2:
                {
                    if (empty(getallheaders()['Authorization'])) {
                        setHTTPStatus("403","Authorization token are invalid");
                        exit();
                    } else if (checkHaveUser($link)) {
                        setHTTPStatus();
                        echo json_encode(getTaskById((int)$urlList[1], $link)["task"][0]);
                        return;
                    } else {
                        setHTTPStatus("403","Authorization token are invalid");
                        exit();
                    }
                    return;
                }
                case 3:
                {
                    if (empty(getallheaders()['Authorization'])) {
                        setHTTPStatus("403","Authorization token are invalid");
                        exit();
                    } else if (checkHaveUser($link)) {
                        switch ($urlList[2]) {
                            case "input":
                            {
                                if (is_numeric($urlList[1])) {
                                    setHTTPStatus();
                                    echo getTaskInput((int)$urlList[1], $link);
                                    return;
                                }
                            }
                            case "output":
                            {
                                if (is_numeric($urlList[1])) {
                                    setHTTPStatus();
                                    echo getTaskOutput((int)$urlList[1], $link);
                                    return;
                                }
                            }
                        }
                    } else {
                        setHTTPStatus("403","Authorization token are invalid");
                        exit();
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
            if ($urlList[2] == "solution" && is_numeric($urlList[1]))
            {
                if (empty(getallheaders()['Authorization'])) {
                    setHTTPStatus("403","Authorization token are invalid");
                    exit();
                } else if (checkHaveUser($link)) {
                    setHTTPStatus();
                    echo json_encode(postSolutionTask((int)$urlList[1], $link, $requestData));
                    return;
                } else {
                    setHTTPStatus("403","Authorization token are invalid");
                    exit();
                }
                return;
            }



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
                            echo json_encode(postTask($link, $requestData));
                            return;
                        }
                        case 3:
                        {
                            switch ($urlList[2]) {
                                case "input":
                                {
                                    if (is_numeric($urlList[1])) {
                                        setHTTPStatus();
                                        echo json_encode(postInputTask((int)$urlList[1], $link));
                                        return;
                                    }
                                }
                                case "output":
                                {
                                    if (is_numeric($urlList[1])) {
                                        setHTTPStatus();
                                        echo json_encode(postOutputTask((int)$urlList[1], $link));
                                        return;
                                    }
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
                else
                {
                    setHTTPStatus("403","Authorization token are invalid");
                    exit();
                }
            }
        }
        case "PATCH":
        {
            if (empty(getallheaders()['Authorization'])) {
                setHTTPStatus("403","Authorization token are invalid");
                exit();
            } else {
                if (checkAdmin($link) == true && count($urlList) == 2) {
                    setHTTPStatus();
                    echo json_encode(patchTask((int)$urlList[1], $link, $requestData));
                    return;
                } else if (!checkAdmin($link)) {
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
                if (checkAdmin($link) == true) {
                    switch (count($urlList)) {
                        case 2:
                        {
                            if (is_numeric($urlList[1])) {
                                setHTTPStatus();
                                deleteTask((int)$urlList[1], $link);
                                exit();
                            }
                        }
                        case 3:
                        {
                            switch ($urlList[2]) {
                                case "input":
                                {
                                    setHTTPStatus();
                                    echo json_encode(deleteInputTask($urlList[1], $link));
                                    exit();
                                }
                                case "output":
                                {
                                    setHTTPStatus();
                                    echo json_encode(deleteOutputTask($urlList[1], $link));
                                    exit();
                                }
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

function postSolutionTask($taskId, $link, $requestData)
{
    $token = substr(getallheaders()['Authorization'], 7);
    $sourceCode = $requestData->body->sourceCode;
    $programmingLanguage = $requestData->body->programmingLanguage;
    if (!checkTaskIsThereInTable($taskId,$link) || empty($sourceCode) || empty($programmingLanguage))
    {
        setHTTPStatus("400","Strange data");
        exit;
    }
    if ($programmingLanguage != "Python" && $programmingLanguage != "C++" && $programmingLanguage != "C#" && $programmingLanguage != "Java")
    {
        setHTTPStatus("400","Strange data");
        exit;
    }

    $idUser = $link->query("SELECT `userId` FROM `users` WHERE token = '$token'")->fetch_assoc()["userId"];
    $res = $link->query("INSERT INTO `solutions`(`sourceCode`, `programmingLanguage`, `authorId`, `taskId`) VALUES ('$sourceCode','$programmingLanguage','$idUser','$taskId')");
    if (!$res) //SQL
    {
        setHTTPStatus("500","Unexpected data");
        exit;
    }
    return getLastSolutionByUser($idUser, $link);
}

function deleteOutputTask($id, $link)
{
    if (!checkTaskIsThereInTable($id, $link))
    {
        setHTTPStatus("400","Strange data");
        exit;
    }
    $res = $link->query("SELECT output FROM tasks WHERE taskId = $id")->fetch_assoc();
    if ($res["output"] == null)
    {
        setHTTPStatus("400","Output not find");
        exit;
    }
    if (!$res) //SQL
    {
        setHTTPStatus("500","Not find output");
        exit;
    }
    else
    {
        unlink($res["output"]);
    }
    $link->query("UPDATE `tasks` SET `output` =null WHERE taskId = $id");
    return (["message"=>"OK"]);
}

function postOutputTask($id, $link)
{
    if (!checkTaskIsThereInTable($id, $link))
    {
        setHTTPStatus("400","Strange data");
        exit;
    }
    $file = $_FILES['output'];
    if ($file['type'] != "text/plain")
    {
        setHTTPStatus("400", "Strange file");
        exit();
    }
    $pathToUpLoad = "uploads" . "/output/" . $id . $file['name'];
    move_uploaded_file($file['tmp_name'],$pathToUpLoad);

    $res = $link->query("UPDATE `tasks` SET `output` ='$pathToUpLoad' WHERE taskId = $id");
    if (!$res) //SQL
    {
        setHTTPStatus("500","Unexpected data");
        exit;
    }
    return getTaskById($id, $link)["task"][0];
}

function getTaskOutput($id, $link)
{
    if (!checkTaskIsThereInTable($id, $link))
    {
        setHTTPStatus("400","Strange data");
        exit;
    }
    $res = $link->query("SELECT output FROM tasks WHERE taskId = $id")->fetch_assoc();
    if ($res["output"] == null)
    {
        setHTTPStatus("400","Output not find");
        exit;
    }
    if (!$res) //SQL
    {
        setHTTPStatus("500","Unexpected data");
        exit;
    }
    else
    {
        $file = $res["output"];
        // Не работает с postman(скорее всего просто не поддерживает)
//        header('Content-Description: File Transfer');
//        header('Content-Type: application/octet-stream');
//        header('Content-Disposition: attachment; filename=' . basename($file));
//        header('Content-Transfer-Encoding: binary');
//        header('Expires: 0');
//        header('Cache-Control: must-revalidate');
//        header('Pragma: public');
//        header('Content-Length: ' . filesize($file));
//        // читаем файл и отправляем его пользователю

        // возможен такой вариант echo json_encode(file($file)[0]);
        readfile($file);
        exit();
    }
    exit();
}

function deleteInputTask($id, $link)
{
    if (!checkTaskIsThereInTable($id, $link))
    {
        setHTTPStatus("400","Strange data");
        exit;
    }
    $res = $link->query("SELECT input FROM tasks WHERE taskId = $id")->fetch_assoc();
    if ($res["input"] == null)
    {
        setHTTPStatus("400","Input not find");
        exit;
    }
    if (!$res) //SQL
    {
        setHTTPStatus("500","Unexpected error");
        exit;
    }
    else
    {
        unlink($res["input"]);
    }
    $link->query("UPDATE `tasks` SET `input` =null WHERE taskId = $id");
    return (["message"=>"OK"]);
}

function postInputTask($id, $link)
{
    if (!checkTaskIsThereInTable($id, $link))
    {
        setHTTPStatus("400","Strange data");
        exit;
    }
    $file = $_FILES['input'];
    if ($file['type'] != "text/plain")
    {
        setHTTPStatus("400", "Strange file");
        exit();
    }
    $pathToUpLoad = "uploads" . "/input/" . $id . $file['name'];
    move_uploaded_file($file['tmp_name'],$pathToUpLoad);

    $res = $link->query("UPDATE `tasks` SET `input` ='$pathToUpLoad' WHERE taskId = $id");
    if (!$res) //SQL
    {
        setHTTPStatus("500","Unexpected data");
        exit;
    }
    return getTaskById($id, $link)["task"][0];
}

function getTaskInput($id, $link)
{
    if (!checkTaskIsThereInTable($id, $link))
    {
        setHTTPStatus("400","Strange data");
        exit;
    }
    $res = $link->query("SELECT input FROM tasks WHERE taskId = $id")->fetch_assoc();
    if ($res["input"] == null)
    {
        setHTTPStatus("400","Input not find");
        exit;
    }
    if (!$res) //SQL
    {
        setHTTPStatus("500","Unexpected data");
        exit;
    }
    else
    {
        $file = $res["input"];
        // Не работает с postman(скорее всего просто не поддерживает)
//        header('Content-Description: File Transfer');
//        header('Content-Type: application/octet-stream');
//        header('Content-Disposition: attachment; filename=' . basename($file));
//        header('Content-Transfer-Encoding: binary');
//        header('Expires: 0');
//        header('Cache-Control: must-revalidate');
//        header('Pragma: public');
//        header('Content-Length: ' . filesize($file));
//        // читаем файл и отправляем его пользователю

        // возможен такой вариант echo json_encode(file($file)[0]);
        readfile($file);
        exit();
    }
    exit();
}

function patchTask($id, $link, $requestData)
{
    $paramStr = "";
    $param = ['name','topicId','description', 'price','isDraft'];
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

    if (checkTaskIsThereInTable($id, $link)) {
        $res = $link->query("UPDATE `tasks` SET $paramStr WHERE taskId = $id");
        return getTaskById($id, $link)["task"][0];
    } else {
        setHTTPStatus("400","Strange data");
        exit;
    }

}

function deleteTask($id, $link)
{
    if (!checkTaskIsThereInTable($id, $link)) return;
    $res = $link->query("DELETE FROM tasks WHERE taskId = $id");
    if (!$res) //SQL
    {
        setHTTPStatus("500","Unexpected error");
        exit;
    } else {
        echo('{"message" : "OK"}');
    }
}

function getTaskById($id, $link)
{
    if (!checkTaskIsThereInTable($id, $link)) {
        setHTTPStatus("400","Strange data");
        exit;
    }
    $message = [];
    $message["task"] = [];
    $res = $link->query("SELECT taskId,name,topicId,description,price,isDraft FROM tasks WHERE taskId = $id");
    if (!$res) //SQL
    {
        setHTTPStatus("500","Unexpected error");
        exit;
    } else {
        while ($row = $res->fetch_assoc()) {
            $message["task"][] = [
                "id" => $row['taskId'],
                "name" => $row['name'],
                "topicId" => $row['topicId'],
                "description" => $row['description'],
                "price" => $row['price'],
                "isDraft" => $row['isDraft']
            ];
        }
    }
    return $message;
}

function checkTaskIsThereInTable($id, $link)
{
    $link->query("SELECT taskId FROM tasks WHERE taskId = $id");
    if ($link->affected_rows == 0 || $link->affected_rows == -1) {
        setHTTPStatus("400","Strange data");
        exit;
    } else {
        return true;
    }
}

function postTask($link, $requestData)
{
    $name = $requestData->body->name;
    $topicId = $requestData->body->topicId;
    $description = $requestData->body->description;
    $price = $requestData->body->price;

    if (empty($name) || empty($topicId) || empty($description) || empty($price))
    {
        setHTTPStatus("400","Strange data");
        exit;
    }

    $description = addcslashes($description, "'");
    $res = $link->query("INSERT INTO tasks(`name`, `topicId`, `description`, `price`) VALUES ('$name','$topicId','$description','$price')");
    if (!$res) //SQL
    {
        setHTTPStatus("500","Unexpected error");
        exit;
    } else {
        $id = $link->insert_id;
        return getTaskById($id, $link)["task"];
    }
}


function getTasks($link, $nameTask, $topicId)
{
    $message = [];
    $message["tasks"] = [];
    $mas = getAllTasks($link);

    if (!empty($nameTask) && !empty($topicId)) {
        foreach ($mas as $row) {
            if ($row["name"] == $nameTask && $row["topicId"] == $topicId) {
                $message["tasks"][] = [
                    "id" => $row['taskId'],
                    "name" => $row['name'],
                    "topicId" => $row['topicId']
                ];
            }

        }
        return json_encode($message["tasks"]);
    }
    if (!empty($nameTask)) {
        foreach ($mas as $row) {
            if ($row["name"] == $nameTask) {
                $message["tasks"][] = [
                    "id" => $row['taskId'],
                    "name" => $row['name'],
                    "topicId" => $row['topicId']
                ];
            }
        }
        return json_encode($message["tasks"]);
    }
    if (!empty($topicId)) {
        foreach ($mas as $row) {
            if ($row["topicId"] == $topicId) {
                $message["tasks"][] = [
                    "id" => $row['taskId'],
                    "name" => $row['name'],
                    "topicId" => $row['topicId']
                ];
            }
        }
        return json_encode($message["tasks"]);
    }

    foreach ($mas as $row) {
        $message["tasks"][] = [
            "id" => $row['taskId'],
            "name" => $row['name'],
            "topicId" => $row['topicId']
        ];
    }
    return json_encode($message["tasks"]);
}


function getAllTasks($link)
{
    $mas = [];
    $res = $link->query("SELECT taskId,name,topicId FROM tasks ORDER BY topicId ASC");
    if (!$res) //SQL
    {
        setHTTPStatus("500","Unexpected error");
        exit;
    } else {
        while ($row = $res->fetch_assoc()) {
            $mas[] = $row;
        }
    }
    return $mas;
}
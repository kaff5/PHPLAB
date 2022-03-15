<?php



function getSolutions($link,$taskId,$userId)
{
    $message = [];
    $message["solutions"] = [];
    $mas = getAllSolution($link);

    if (!empty($taskId) && !empty($userId))
    {
        foreach ($mas as $row) {
            if ($row["taskId"] == $taskId && $row["authorId"] == $userId)
            {
                $message["solutions"][] = [
                    "id" => $row['solutionId'],
                    "sourceCode" => $row['sourceCode'],
                    "programmingLanguage" => $row['programmingLanguage'],
                    "verdict" => $row['verdict'],
                    "authorId" => $row['authorId'],
                    "taskId" => $row['taskId']
                ];
            }
        }
        return $message["solutions"];
    }
    else if (!empty($taskId))
    {
        foreach ($mas as $row) {
            if ($row["taskId"] == $taskId)
            {
                $message["solutions"][] = [
                    "id" => $row['solutionId'],
                    "sourceCode" => $row['sourceCode'],
                    "programmingLanguage" => $row['programmingLanguage'],
                    "verdict" => $row['verdict'],
                    "authorId" => $row['authorId'],
                    "taskId" => $row['taskId']
                ];
            }
        }
        return $message["solutions"];
    }
    else if (!empty($userId))
    {
        foreach ($mas as $row) {
            if ($row["authorId"] == $userId)
            {
                $message["solutions"][] = [
                    "id" => $row['solutionId'],
                    "sourceCode" => $row['sourceCode'],
                    "programmingLanguage" => $row['programmingLanguage'],
                    "verdict" => $row['verdict'],
                    "authorId" => $row['authorId'],
                    "taskId" => $row['taskId']
                ];
            }
        }
        return $message["solutions"];
    }


    foreach ($mas as $row) {
        $message["solutions"][] = [
            "id" => $row['solutionId'],
            "sourceCode" => $row['sourceCode'],
            "programmingLanguage" => $row['programmingLanguage'],
            "verdict" => $row['verdict'],
            "authorId" => $row['authorId'],
            "taskId" => $row['taskId']
        ];
    }
    return $message["solutions"];
}

function getAllSolution($link)
{
    $mas = [];
    $res = $link->query("SELECT * FROM solutions ORDER BY solutionId ASC");
    while ($row = $res->fetch_assoc()) {
        $mas[] = $row;
    }
    return $mas;
}

function getSolutionById($id,$link)
{
    $message = [];
    $mas = getAllSolution($link);
    foreach ($mas as $value) {
        if ($value['solutionId'] == $id)
        {
            $message[] = [
                "id" => $value['solutionId'],
                "sourceCode" => $value['sourceCode'],
                "programmingLanguage" => $value['programmingLanguage'],
                "verdict" => $value['verdict'],
                "authorId" => $value['authorId'],
                "taskId" => $value['taskId']
            ];
        }
    }
    return $message;
}

function getLastSolutionByUser($userId,$link)
{
    $res["solutionId"] = $link->query("SELECT MAX(solutionId) FROM solutions WHERE authorId = $userId")->fetch_assoc();
    return getSolutionById($res["solutionId"]["MAX(solutionId)"],$link);
}



function getTaskByIdForSolutions($id, $link)
{

    $message = [];
    $res = $link->query("SELECT taskId,name,topicId,description,price,isDraft FROM tasks WHERE taskId = $id");
    if (!$res) //SQL
    {
        setHTTPStatus("500","Unexpected error");
        exit;
    } else {
        while ($row = $res->fetch_assoc()) {
            $message[] = [
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

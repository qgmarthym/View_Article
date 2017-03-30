<?php
$timeStart = microtime(true);
$routineStart = date('Y-m-d H:i:s');

function startLogRoutine($routineName) {
    global $db;

    $timeStart = microtime(true);
    $routineStartTime = date('Y-m-d H:i:s', $timeStart);
    $routineStatus = 'running';

    $logRoutine = $db->prepare("INSERT INTO routines (name, status, start_time) VALUES (:routineName, :routineStatus, :routineStartTime)");

    $logRoutine->bindParam(':routineName', $routineName, PDO::PARAM_STR);
    $logRoutine->bindParam(':routineStatus', $routineStatus, PDO::PARAM_STR);
    $logRoutine->bindParam(':routineStartTime', $routineStartTime, PDO::PARAM_STR);

    $logRoutine->execute();

    $routine = new StdClass();
    $routine->id = $db->lastInsertId();
    $routine->timeStart = $timeStart;

    return $routine;
}

function stopLogRoutine($routineId, $timeStart) {
    global $db;

    $timeEnd = microtime(true);
    $routineEndTime = date('Y-m-d H:i:s', $timeEnd);
    $routineStatus = 'finished';
    $routineExecutionTime = $timeEnd - $timeStart;

    $logRoutine = $db->prepare("UPDATE 
					routines 
				  SET 
					status = :routineStatus, 
					end_time = :routineEndTime, 
					execution_time = :routineExecutionTime
				  WHERE
					id = :routineId");

    $logRoutine->bindParam(':routineId', $routineId, PDO::PARAM_INT);
    $logRoutine->bindParam(':routineStatus', $routineStatus, PDO::PARAM_STR);
    $logRoutine->bindParam(':routineEndTime', $routineEndTime, PDO::PARAM_STR);
    $logRoutine->bindParam(':routineExecutionTime', $routineExecutionTime, PDO::PARAM_STR);

    $logRoutine->execute();
}

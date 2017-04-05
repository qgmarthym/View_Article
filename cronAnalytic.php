<?php

function fileName() {
    if(date('A') == 'AM')
    {
        // Récupèrer le fichier PM du jour précédent
        $file = date("Y-m-d", strtotime( '-1 days' ))."-PM";
    }
    else
    {
        // Récupèrer le fichier AM du même jour
        $file = date('Y-m-d')."-AM";
    }

    $file = $file.".txt";
    return $file;
}

function multipleExplode($delimiters = array(), $string = '') {
    $delim = $delimiters[count($delimiters)-1];
    array_pop($delimiters);

    foreach($delimiters as $delimiter) {
        $string = str_replace($delimiter, $delim, $string);
    }
    $result = explode($delim, $string);

    return $result;
}

// Vérifier que le fichier existe
$logsPath = "logs/";
$file = fileName();
$filePath = $logsPath.$file;
if(!file_exists($filePath))
    die('Le fichier n\'existe pas');

// Vérifier que l'on peut ouvrir le fichier
$fileOpen = fopen($filePath, 'r');
if($fileOpen == false)
    die('Impossible d\'ouvrir le fichier');

require 'dbconnect.php';
require 'routines-log.php';

$runningRoutine = startLogRoutine('logAnalytics');

// Lire le fichier ligne par ligne
while(!feof($fileOpen))
{
    $line = fgets($fileOpen);
    $line = str_replace('null', '"null"', $line);
    $line = str_replace('\\', '', $line);
    //var_dump($line);

    $array = multipleExplode(array('{"', '","', '"}'), $line);
    array_shift($array);
    array_pop($array);
    //echo "<pre>";var_dump($array);echo "</pre>";

    $n = count($array); //echo $n;
    for($i = 0; $i < $n; $i++)
    {
        $views = explode('":"', $array[$i]);

        if($views[0] == "date") //On convertit la date en 2 valeurs : 1 datetime et 1 date
        {
            $fulldate = date('Y-m-d H:i:s', strtotime($views[1]));
            $date = date('Y-m-d', strtotime($views[1]));
            $views[1] = $fulldate;

            $data = [
                "fulldate" => $fulldate,
                "date" => $date
            ];
        }
        else
        {
            $data[$views[0]] = $views[1];
        }
        //echo "<pre>";var_dump($views);echo "</pre>";
    }

    echo "<pre>";var_dump($data);echo "</pre>";
    echo "*****************************************************************************************<br />";

    if(
        !($data['portalId']=="null" || $data['portalId']=="0"|| $data['portalId']=="")
        && !($data['pageId']=="null" || $data['pageId']=="0"|| $data['portalId']=="")
        && ($data['pageType']=="article" || $data['pageType']=="folder")
        && !($data['page']=="null" || $data['page']=="")
        && !($data['ip']=="null" || $data['ip']==""))
    {
        $req = $db->prepare('CALL insert_analytic (:portal_group, :portal_id, :page_type, :page_id, :page, :ip, :session_id, :date, :fulldate, :referrer, :browser)');
        $req->bindParam('portal_group', $data['portalGroup'], PDO::PARAM_STR);
        $req->bindParam('portal_id', $data['portalId'], PDO::PARAM_INT);
        $req->bindParam('page_type', $data['pageType'], PDO::PARAM_STR);
        $req->bindParam('page_id', $data['pageId'], PDO::PARAM_INT);
        $req->bindParam('page', $data['page'], PDO::PARAM_STR);
        $req->bindParam('ip', $data['ip'], PDO::PARAM_INT);
        $req->bindParam('session_id', $data['sessionId'], PDO::PARAM_STR);
        $req->bindParam('date', $data['date'], PDO::PARAM_STR);
        $req->bindParam('fulldate', $data['fulldate'], PDO::PARAM_STR);
        $req->bindParam('referrer', $data['referrer'], PDO::PARAM_STR);
        $req->bindParam('browser', $data['browser'], PDO::PARAM_STR);
        $req->execute();
    }
}
fclose($fileOpen);

rename($logsPath.$file, $logsPath."archives/".$file);

stopLogRoutine($runningRoutine->id, $runningRoutine->timeStart);

$req->closeCursor();

?>

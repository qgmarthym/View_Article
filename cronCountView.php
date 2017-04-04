<?php

require 'dbconnect.php';
require 'routines-log.php';

function updateAnalytic($portal_id, $page_id, $view) {
    global $db;

    $sql = "UPDATE article
            SET number_view = number_view + $view
            WHERE id = $page_id AND portal_id = $portal_id";
    echo $sql."<br />";

    $req = $db->prepare('UPDATE article SET number_view = number_view + :view
                   WHERE id = :page_id AND portal_id = :portal_id');
    $req->bindParam(':view', $view, PDO::PARAM_INT);
    $req->bindParam(':page_id', $page_id, PDO::PARAM_INT);
    $req->bindParam(':portal_id', $portal_id, PDO::PARAM_INT);
    $req->execute();
}

//function deleteAnalytic($portal_id, $page_id, $page_type, $date) {
function deleteAnalytic($portal_id, $page_id, $page_type) {
    global $db;

    /*$sql = "DELETE FROM analytic
            WHERE portal_id = ".$portal_id." AND page_id = ".$page_id." AND page_type = ".$page_type." AND date = '".$date ."'";*/
    $sql = "DELETE FROM analytic
            WHERE portal_id = ".$portal_id." AND page_id = ".$page_id." AND page_type = ".$page_type."";
    echo $sql."<br />";

    //$req = $db->prepare('CALL deleteAnalyticsData (:portal_id, :page_id, :page_type, :date)');
    $req = $db->prepare('CALL deleteAnalyticsDataWithoutDate (:portal_id, :page_id, :page_type)');
    $req->bindParam(':portal_id', $portal_id, PDO::PARAM_INT);
    $req->bindParam(':page_id', $page_id, PDO::PARAM_INT);
    $req->bindParam(':page_type', $page_type, PDO::PARAM_STR);
    //$req->bindParam(':date', $date, PDO::PARAM_STR);
    $req->execute();
}

$runningRoutine = startLogRoutine('updateViews');

$portal_id = 1;
$page_type = ["article", "folder"];

$date = "2015-09-30";
//$date = date('Y-m-d');
$sql = "SELECT page_id, page_type, COUNT(id) AS total FROM analytic
        WHERE portal_id = ".$portal_id."
        AND (page_type = '".$page_type[0]."' OR page_type = '".$page_type[1]."')
        GROUP BY page_id ASC";
echo $sql;

//$req = $db->prepare('CALL getAnalyticsData (:portal_id, :article, :folder, :date)');
$req = $db->prepare('CALL getAnalyticsDataWithoutDate (:portal_id, :article, :folder)');
$req->bindParam(':portal_id', $portal_id, PDO::PARAM_INT);
$req->bindParam(':article', $page_type[0], PDO::PARAM_STR);
$req->bindParam(':folder', $page_type[1], PDO::PARAM_STR);
//$req->bindParam(':date', $date, PDO::PARAM_STR);
$req->execute();

while($data = $req->fetch())
{
    echo "<pre>";var_dump($data);echo "</pre>";

    updateAnalytic($portal_id, $data['page_id'], $data['total']);
    //deleteAnalytic($portal_id, $data['page_id'], $data['page_type'], $date);
    deleteAnalytic($portal_id, $data['page_id'], $data['page_type']);

    echo "<br />*****************************************************************************************";
}

stopLogRoutine($runningRoutine->id, $runningRoutine->timeStart);

$req->closeCursor();

?>


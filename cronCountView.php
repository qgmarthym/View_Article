<?php

require 'dbconnect.php';
require 'routines-log.php';

function updateAnalytic($id, $portal_group, $portal_id, $view) {
    global $db;

    $sql = "UPDATE article
            SET number_view = number_view + $view
            WHERE id = $id
            AND portal_group = '".$portal_group."' AND portal_id = ".$portal_id."";
    echo $sql."<br />";

    $req = $db->prepare('UPDATE article SET number_view = number_view + :view
                         WHERE id = :id
                         AND portal_group = :portal_group AND portal_id = :portal_id');
    $req->bindParam(':view', $view, PDO::PARAM_INT);
    $req->bindParam(':id', $id, PDO::PARAM_INT);
    $req->bindParam(':portal_group', $portal_group, PDO::PARAM_STR);
    $req->bindParam(':portal_id', $portal_id, PDO::PARAM_INT);
    $req->execute();
}

function deleteAnalytic($portal_group, $portal_id, $page_id, $page_type, $date) {
    global $db;

    $sql = "DELETE FROM analytic
            WHERE portal_group = '".$portal_group."' AND portal_id = ".$portal_id."
            AND page_id = ".$page_id." AND page_type = ".$page_type."
            AND date = '".$date ."'";
    echo $sql."<br />";

    $req = $db->prepare('CALL deleteAnalyticsData (:portal_group, :portal_id, :page_id, :page_type, :date)');
    $req->bindParam(':portal_group', $portal_group, PDO::PARAM_STR);
    $req->bindParam(':portal_id', $portal_id, PDO::PARAM_INT);
    $req->bindParam(':page_id', $page_id, PDO::PARAM_INT);
    $req->bindParam(':page_type', $page_type, PDO::PARAM_STR);
    $req->bindParam(':date', $date, PDO::PARAM_STR);
    $req->execute();
}

$page_type = ["article", "folder"];

$options = getopt(null, [
    'portal_id::',
    'date::'
]);

$portal_id = (is_bool($options['portal_id']) == false || is_null($portal_id)) ? 1 : $options['portal_id'];

$dateFile = (date('A') == 'AM') ? date("Y-m-d", strtotime( '-1 days' )) : date('Y-m-d');//$dateFile = "2015-09-30";
$date = (is_bool($options['date']) == false || is_null($date)) ? $dateFile : $options['date'];

$runningRoutine = startLogRoutine('updateViews');

$sql = "SELECT portal_group, page_id, page_type, COUNT(id) AS total FROM analytic
        WHERE portal_id = ".$portal_id."
        AND (page_type = '".$page_type[0]."' OR page_type = '".$page_type[1]."')
        AND date = '".$date."'
        GROUP BY page_id ASC";
echo $sql;

$req = $db->prepare('CALL getAnalyticsData (:portal_id, :article, :folder, :date)');
$req->bindParam(':portal_id', $portal_id, PDO::PARAM_INT);
$req->bindParam(':article', $page_type[0], PDO::PARAM_STR);
$req->bindParam(':folder', $page_type[1], PDO::PARAM_STR);
$req->bindParam(':date', $date, PDO::PARAM_STR);
$req->execute();

while($data = $req->fetch())
{
    echo "<pre>";var_dump($data);echo "</pre>";

    updateAnalytic($data['page_id'], $data['portal_group'], $portal_id, $data['total']);
    deleteAnalytic($data['portal_group'], $portal_id, $data['page_id'], $data['page_type'], $date);

    echo "<br />*****************************************************************************************";
}

stopLogRoutine($runningRoutine->id, $runningRoutine->timeStart);

$req->closeCursor();

?>

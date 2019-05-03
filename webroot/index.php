<?php

require __DIR__ . '/../vendor/autoload.php';


use JiraRestApi\Project\ProjectService;
use JiraRestApi\JiraException;
use JiraRestApi\User\UserService;
use JiraRestApi\Issue\IssueService;

try {
    $projectService = new ProjectService();

    $projects = $projectService->getAllProjects();
    //$$project = $projectService->get('WB');

} catch (JiraException $e) {
    print("Error Occured! " . $e->getMessage());
}

$currentProject = !empty($_POST['project']) ? $_POST['project'] : null;

$usersService = new UserService();

$paramArray = [
    'startAt' => 0,
    'maxResults' => 50, //max 1000
];

//ini_set('display_errors', 1);

if ($currentProject !== null) {
    $paramArray['project'] = $currentProject;
    $users = $usersService->findAssignableUsers($paramArray);
} else {
    $users = [];
}


$defaultStartDate = (new \DateTime('- 1 month'))->format('Y-m-d');
$defaultEndDate = (new \DateTime())->format('Y-m-d');

$startDate = empty($_POST['startDate']) ? $defaultStartDate : $_POST['startDate'];
$endDate = empty($_POST['endDate']) ? $defaultEndDate : $_POST['endDate'];


$userKey = empty($_POST['user']) ? '' : $_POST['user'];

if (!empty($_POST['isset_data'])) {
    $issueService = new IssueService();
    $fields = ['project', 'summary', 'worklog', 'timespent'];
    $startAt = 0;
    $step = 500;
    $resultIssues = [];
    do {
        try {
            $issues = $issueService->search('project = ' . $currentProject . '   and timespent > 0 and updated > ' . $startDate . ' ', $startAt, $step, $fields);
        } catch (JiraException $e) {
            return \Response::json(['meta' => ['message' => $e->getMessage()]], 500);
        }
        $startAt += $step;
        $resultIssues = array_merge($resultIssues, $issues->issues);

    } while (count($issues->issues) == $step);
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Jira Worklogs</title>
    <link rel="stylesheet" type="text/css" href="node_modules/bootstrap/dist/css/bootstrap.css">
    <link rel="stylesheet" type="text/css" href="node_modules/datatables.net-dt/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="node_modules/datatables.net-buttons-bs4/css/buttons.bootstrap4.css">

    <link rel="stylesheet" type="text/css" href="node_modules/bootstrap-datepicker/dist/css/bootstrap-datepicker.css">
    <link rel="stylesheet" type="text/css" href="node_modules/select2/dist/css/select2.css">
    <link rel="stylesheet" type="text/css" href="node_modules/select2-bootstrap-theme/dist/select2-bootstrap.css">
    <link rel="stylesheet" type="text/css" href="node_modules/datatables.net-bs4/css/dataTables.bootstrap4.css">
    <link rel="stylesheet" type="text/css"
          href="node_modules/datatables.net-responsive-bs4/css/responsive.bootstrap4.css">


    <script type="text/javascript" charset="utf8" src="node_modules/jquery/dist/jquery.js"></script>
    <script type="text/javascript" charset="utf8" src="node_modules/datatables.net/js/jquery.dataTables.js"></script>
    <script type="text/javascript" charset="utf8"
            src="node_modules/datatables.net-bs4/js/dataTables.bootstrap4.js"></script>

    <script type="text/javascript" charset="utf8"
            src="node_modules/bootstrap-datepicker/dist/js/bootstrap-datepicker.js"></script>
    <script type="text/javascript" charset="utf8" src="node_modules/select2/dist/js/select2.js"></script>

    <script type="text/javascript" charset="utf8" src="node_modules/jszip/dist/jszip.js"></script>
    <script type="text/javascript" charset="utf8" src="node_modules/pdfmake/build/pdfmake.js"></script>
    <script type="text/javascript" charset="utf8" src="node_modules/pdfmake/build/vfs_fonts.js"></script>
    <script type="text/javascript" charset="utf8"
            src="node_modules/datatables.net-buttons/js/dataTables.buttons.js"></script>

    <script type="text/javascript" charset="utf8"
            src="node_modules/datatables.net-buttons-bs4/js/buttons.bootstrap4.js"></script>
    <script type="text/javascript" charset="utf8"
            src="node_modules/datatables.net-buttons/js/buttons.print.js"></script>
    <script type="text/javascript" charset="utf8"
            src="node_modules/datatables.net-buttons/js/buttons.html5.js"></script>

    <script type="text/javascript" charset="utf8"
            src="node_modules/datatables.net-responsive/js/dataTables.responsive.js"></script>
    <script type="text/javascript" charset="utf8"
            src="node_modules/datatables.net-responsive-bs4/js/responsive.bootstrap4.js"></script>
</head>
<body>
<div class="container">
    <div class="row">
        <div class="col-sm-12">

            <h1>Jira Worklogs</h1>
            <form method="post">
                <div class="form-group">
                    <select name="project" class="form-control select2">
                        <?php
                        foreach ($projects as $project) {
                            echo '<option value="' . $project->key . '" ' . (($project->key == $_POST['project']) ? ' selected="selected" ' : '') . ' >' . $project->name . ' (' . $project->key . ')</option>';
                        }
                        ?>
                    </select>
                </div>
                <?php if (!empty($users)) { ?>
                    <div class="form-group">
                        <select name="user" class="form-control select2">
                            <option value="">All</option>
                            <?php
                            foreach ($users as $user) {
                                echo '<option value="' . $user->key . '" ' . (($user->key == $_POST['user']) ? ' selected="selected" ' : '') . ' >' . $user->displayName . ' &lt;' . $user->emailAddress . '&gt;</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-2">
                                from
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="startDate" class="form-control datepicker datepicker-inline"
                                       value="<?php echo $startDate ?>">
                            </div>
                            <div class="col-md-2">
                                to
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="endDate" class="form-control datepicker datepicker-inline"
                                       value="<?php echo $endDate ?>">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <input type="hidden" name="isset_data" value="1">
                        <button type="submit" class="btn btn-primary">Calculate</button>
                    </div>
                <?php } else { ?>
                    <div class="form-group">
                        <button type="submit" class="btn btn-success">Set Project</button>
                    </div>
                <?php } ?>
            </form>

            <?php if (!empty($_POST['isset_data'])) { ?>
                <h2>Common time: <strong class="common-time"></strong> hours</h2>
                <br>
                <table id="jiraDataTable" class="table dataTables_wrapper dt-bootstrap4">
                    <thead>
                    <tr>
                        <th>Issue</th>
                        <th>User</th>
                        <th>Comment</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Time in seconds</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $commonTime = 0;
                    foreach ($resultIssues as $issue) {
//                echo '<tr><td colspan="4">';
//                echo $issue->key;
//                echo '</td></tr>';
                        foreach ($issue->fields->worklog->worklogs as $worklog) {
                            $workLogDate = (new \DateTime($worklog->started))->format('Y-m-d');

                            if ($workLogDate >= $startDate && $workLogDate <= $endDate &&
                                (
                                    empty($userKey) ||
                                    (!empty($worklog->author->key) && $userKey == $worklog->author->key) ||
                                    (!empty($worklog->author->name) && $userKey == $worklog->author->name)
                                )) {
                                $commonTime += $worklog->timeSpentSeconds;
                                echo '<tr>';
                                //echo '<td><a href="' . $issue->self . '">' . $issue->key . '</a></td>';
                                echo '<td>' . $issue->key . '</td>';
                                //echo '<td><a href="' . $worklog->author->self . '">' . $worklog->author->displayName . '</a></td>';
                                echo '<td>' . $worklog->author->displayName . '</td>';
                                //echo '<td><a href="' . $worklog->self . '">' . $worklog->comment . '</a></td>';
                                echo '<td>' . $worklog->comment . '</td>';
                                echo '<td>' . (new \DateTime($worklog->started))->format('Y-m-d') . '</td>';
                                echo '<td>' . $worklog->timeSpent . '</td>';
                                echo '<td>' . $worklog->timeSpentSeconds . '</td>';
                                echo '</tr>';
                            }

                        }
                    }
                    ?>
                    </tbody>

                </table>

                <h2>Common time: <?php echo round($commonTime / 3600, 2); ?></h2>
                <script type="text/javascript">
                  $(document).ready(function () {
                    $('.common-time').html('<?php echo round($commonTime / 3600, 2); ?>');
                  });
                </script>
            <?php } ?>
        </div>
    </div>
</div>

<script type="text/javascript">
  $(document).ready(function () {

    $('#jiraDataTable').DataTable({
      dom: 'Bfrtip',
      buttons: [
        'copy', 'csv', 'excel', 'pdf', 'print'
      ],
      "paging": false,
    });

    $('.datepicker').datepicker({
      format: 'yyyy-mm-dd',
    });

    $('.select2').select2({
      theme: "bootstrap"
    });

  });
</script>
</body>
</html>






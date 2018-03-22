<?php
defined('EMONCMS_EXEC') or die('Restricted access');
global $path, $fullwidth, $session, $mysqli;
$fullwidth = true;

// Check table has been created in database
$result = $mysqli->query("SHOW TABLES LIKE 'tasks'");
if ($result->num_rows > 0) {
    $module_installation_complete = true;
}
else {
    $module_installation_complete = false;
}

// Check group module is installed
$result = $mysqli->query("SHOW TABLES LIKE 'groups'");
if ($result->num_rows > 0) {
    $group_support = true;
}
else {
    $group_support = false;
}

// Check cron job is running in order to show a warning
$fp = fopen("Modules/task/lockfile", "w");
if (!flock($fp, LOCK_EX | LOCK_NB)) {
    $cron_job_running = true;
}
else {
    $cron_job_running = false;
    flock($fp, LOCK_UN);    // release the lock
}
?>

<link href="<?php echo $path; ?>Modules/task/task.css" rel="stylesheet">
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/task/task.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/table.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/custom-table-fields.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/task/task-custom-table-fields.js"></script>
<link href="<?php echo $path; ?>Lib/bootstrap-datetimepicker-0.0.11/css/bootstrap-datetimepicker.min.css" rel="stylesheet">
<script type="text/javascript" src="<?php echo $path; ?>Lib/bootstrap-datetimepicker-0.0.11/js/bootstrap-datetimepicker.min.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/feed/feed.js"></script>
<?php if ($group_support === true) { ?>
    <script type="text/javascript" src="<?php echo $path; ?>Modules/group/group.js"></script>
<?php } ?>

<!-------------------------------------------------------------------------------------------
MAIN
-------------------------------------------------------------------------------------------->
<div id="tasks-wrapper">

    <div class="page-content" style="padding-top:15px">
        <h2>Tasks</h2>

        <div style="padding-bottom:15px">
            <div id="create-task"><i class="icon-plus"></i>Create task</div>
        </div>
        <div id="module-installation-error" class="alert alert-warning hide">
            <p><b>Warning!</b></p>
            <p>It looks like you have installed the module but not updated the database. The Task module won't work!</p>
            <p>So:</p>
            <ul>
                <li>In emonCMS in the menu on the top: click on setup</li>
                <li>Click on Administration</li>
                <li>On the Update database row, click on Update & Check</li>
                <li>In the new screen click on Apply changes</li>
            </ul>
        </div>
        <div id="cron-job-running" class="alert alert-warning hide">
            <p><b>Warning!</b></p>
            <p> The cron job that automatically triggers the enabled tasks is not running. You may need to contact the administrator.</p>
            <p>Until the cron job is started, tasks can only be triggered mannually</p>
        </div>
        <h3>My tasks</h3>
        <div id="no-user-tasks" class="alert alert-block"><p>You haven't got any tasks</p></div>
        <table id="user-tasks-table" class='table'></table>

        <!--<h3 class='if-groups-support'>My groups tasks</h3>
        <div id="no-group-member-tasks" class="alert alert-block if-groups-support"><p>You haven't got any tasks</p></div>
        <table id="group-mambers-tasks" class='table if-groups-support'>
            <p class='if-groups-support'>ToDo</p>
        </table>
        -->
        <div id="task-loader" class="ajax-loader hide"></div>
    </div>
</div>

<!-------------------------------------------------------------------------------------------
MODALS
-------------------------------------------------------------------------------------------->

<div id="taskCreateModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="taskCreateModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="taskCreateModalLabel"><?php echo _('Create task'); ?></h3>
    </div>
    <div class="modal-body">
        <p><?php echo _('Once a task is created you will need to set up the Process List and enable it'); ?></p>
        <table>
            <tr><td><?php echo _('Name*'); ?></td><td><input id="task-create-name" type="text" /></td></tr>
            <tr><td><?php echo _('Description'); ?></td><td><input id="task-create-description" type="text" /></td></tr>
            <tr><td><?php echo _('Tag'); ?></td><td><input id="task-create-tag" type="text" /></td></tr>
            <tr><td><?php echo _('Frequency'); ?></td><td id="task-create-frequency"></td></tr>
            <tr><td><?php echo _('Start date'); ?></td><td><div class="input-append date" id="task-create-run-on" data-format="dd/MM/yyyy hh:mm"><input data-format="dd/MM/yyyy hh:mm" type="text" /><span class="add-on"> <i data-time-icon="icon-time" data-date-icon="icon-calendar"></i></span></div></td></tr>
        </table>
        <div id="task-create-message" class="alert alert-block hide"></div>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Cancel'); ?></button>
        <button id="taskCreate-confirm" class="btn btn-primary"><?php echo _('Create task'); ?></button>
    </div>
</div>

<div id="taskDeleteModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="taskDeleteModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="taskDeleteModalLabel"><?php echo _('Delete task'); ?></h3>
    </div>
    <div class="modal-body">
        <p><?php echo _('Deleting a task is permanent.'); ?></p>
        <p><?php echo _('Are you sure you want to delete?'); ?></p>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo _('Cancel'); ?></button>
        <button id="taskDelete-confirm" class="btn btn-primary"><?php echo _('Delete permanently'); ?></button>
    </div>
</div>

<!-------------------------------------------------------------------------------------------
JAVASCRIPT
-------------------------------------------------------------------------------------------->
<?php require "Modules/process/Views/process_ui.php"; ?>
<script>
    var path = "<?php echo $path; ?>";
    var userid = <?php echo $session["userid"]; ?>;
    var cron_job_running =<?php echo $cron_job_running === true ? 'true' : 'false'; ?>;
    var group_support = <?php echo $group_support === true ? 'true' : 'false'; ?>;
    var module_installation_complete = <?php echo $module_installation_complete === true ? 'true' : 'false'; ?>;

    load_custom_table_fields();// Process list UI js
    processlist_ui.init(2); // 2 means that contexttype is taks(other option is 0 for input, 1 for feeds and virtual feeds)
    draw_user_tasks('#user-tasks-table', task.getUserTasks());

    // Group module support
    if (group_support === false)
        $('.if-groups-support').hide();
    else {
        // Antyhing to do?
    }

    if (module_installation_complete === false)
        $('#module-installation-error').show();
    else {
        $('#module-installation-error').hide();
    }

    if (cron_job_running === false)
        $('#cron-job-running').show();
    else {
        $('#cron-job-running').hide();
    }

    // Check if we need to expand any tag from URL
    var a = decodeURIComponent(window.location);
    var selected_tag = decodeURIComponent(window.location.hash).substring(1);
    console.log("Selected tag:" + selected_tag)
    if (selected_tag != "") {
        setTimeout(function () { // We need some extra time to let processlist_ui.init(1) to finish
            $('[group="' + selected_tag + '"]')[0].click();
        }, 100);
    }

    // ----------------------------------------------------------------------------------------
    // Actions
    // ----------------------------------------------------------------------------------------
    $('#create-task').click(function () {
        // Frequency field
        $('#task-create-frequency').html(get_frequency_html({type: 'once_a_month'}));
        add_frequency_html_events();
        // Start date field
        $('#task-create-run-on').datetimepicker({language: 'en-EN', useCurrent: true, weekStart: 1});
        var now = new Date();
        var today = new Date(now.getFullYear(), now.getMonth(), now.getDate(), now.getHours(), now.getMinutes());
        var picker = $('#task-create-run-on').data('datetimepicker');
        picker.setLocalDate(today);
        // Reset fields        
        $('#task-create-message').hide();
        $('#task-create-name').val('');
        $('#task-create-description').val('');
        $('#task-create-tag').val('');
        $('#task-create-frequency [value="once_a_month"]').click();
        $('#taskCreateModal').modal('show');
    });
    $('#taskCreate-confirm').click(function () {
        $('#task-create-message').hide();
        var name = $('#task-create-name').val();
        if ($('#task-create-name').val() == '')
            $('#task-create-message').html('<p>Name cannot be empty</p>').show();
        else {
            var description = $('#task-create-description').val();
            var tag = $('#task-create-tag').val();
            var frequency = get_frequency_field('#task-create-frequency');
            var run_on = parse_timepicker_time($('#task-create-run-on input').val());
            var result = task.createTask(name, description, tag, frequency, run_on);
            if (result.success == false)
                $('#task-create-message').html('<p>' + result.message + '</p>').show();
            else
                $('#taskCreateModal').modal('hide');
        }
        draw_user_tasks('#user-tasks-table', task.getUserTasks());
    });

    // ----------------------------------------------------------------------------------------
    // Functions
    // ----------------------------------------------------------------------------------------
</script>

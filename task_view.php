<?php
defined('EMONCMS_EXEC') or die('Restricted access');
global $path, $fullwidth, $session;
$fullwidth = true;
?>
<link href="<?php echo $path; ?>Modules/task/task.css" rel="stylesheet">
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/task/task.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/table.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/custom-table-fields.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Modules/task/task-custom-table-fields.js"></script>
<link href="<?php echo $path; ?>Lib/bootstrap-datetimepicker-0.0.11/css/bootstrap-datetimepicker.min.css" rel="stylesheet">
<script type="text/javascript" src="<?php echo $path; ?>Lib/bootstrap-datetimepicker-0.0.11/js/bootstrap-datetimepicker.min.js"></script>

<!-------------------------------------------------------------------------------------------
MAIN
-------------------------------------------------------------------------------------------->
<div id="tasks-wrapper">

    <div class="page-content" style="padding-top:15px">
        <h2>Tasks</h2>

        <div style="padding-bottom:15px">
            <div id="create-task"><i class="icon-plus"></i>Create task</div>
        </div>

        <h3>My tasks</h3>
        <div id="no-user-tasks" class="alert alert-block"><p>You haven't got any tasks</p></div>
        <table id="user-tasks-table" class='table'></table>

        <h3 class='if-groups-support'>My groups tasks</h3>
        <div id="no-group-member-tasks" class="alert alert-block if-groups-support"><p>You haven't got any tasks</p></div>
        <table id="group-mambers-tasks" class='table if-groups-support'>
            <p>ToDo</p>
        </table>

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
    var group_support = false;
    
    // Extend table library field types with customtablefields
    for (z in customtablefields)
        table.fieldtypes[z] = customtablefields[z];
    
    // Extend table with a new fields specific to the task module
    for (z in taskcustomtablefields)
        table.fieldtypes[z] = taskcustomtablefields[z];

    draw_user_tasks();
    if (group_support === false)
        $('.if-groups-support').hide();
    else {
//
    }

// ----------------------------------------------------------------------------------------
// Functions
// ----------------------------------------------------------------------------------------
    function draw_user_tasks() {
        $('#user-tasks-table').html('');
        var user_tasks = task.getUserTasks();
        if (user_tasks.length > 0)
            $('#no-user-tasks').hide();
        else
            $('#user-tasks-list').show();
        table.element = "#user-tasks-table";
        //table.groupprefix = "Node ";
        table.groupby = 'tag';
        table.deletedata = false;
        table.groupfields = {
            'dummy-1': {'title': '', 'type': "blank"},
            'dummy-2': {'title': '', 'type': "blank"},
            'dummy-3': {'title': '', 'type': "blank"},
            'dummy-4': {'title': '', 'type': "blank"},
            'dummy-5': {'title': '', 'type': "blank"},
            'dummy-6': {'title': '', 'type': "blank"},
            'dummy-7': {'title': '', 'type': "blank"},
            'dummy-8': {'title': '', 'type': "blank"},
            'dummy-9': {'title': '', 'type': "blank"},
            'dummy-10': {'title': '', 'type': "blank"}
        };
        table.fields = {
            'id': {'title': "<?php echo _('Id'); ?>", 'type': "fixed"},
            'tag': {'title': "<?php echo _('Tag'); ?>", 'type': "text"},
            'name': {'title': '<?php echo _("Name"); ?>', 'type': "text"},
            'description': {'title': '<?php echo _("Description"); ?>', 'type': "text"},
            'processList': {'title': '<?php echo _("Process list"); ?>', 'type': "processlist"},
            'frequency': {'title': "<?php echo _("Frequency") . " <i title='" . _("When frequency is 0 the task will only be run once (no new Run On time will be set)") . "' class='icon-question-sign'></i>" ?>", 'type': "frequency"},
            'enabled': {'title': "<?php echo _('Enabled'); ?>", 'type': "icon", 'trueicon': "icon-ok", 'falseicon': "icon-remove"},
            'time': {'title': "<?php echo _('Last run'); ?>", 'type': "fixeddate"},
            'run_on': {'title': "<?php echo _('Next run'); ?>", 'type': "date"},
            // Actions
            'edit-action': {'title': '', 'type': "edit"},
            'delete-action': {'title': '', 'type': "delete"},
            'processlist-action': {'title': '', 'type': "iconbasic", 'icon': 'icon-wrench'},
            'run_task': {'title': '', 'type': "run_task"},
        }

        table.data = user_tasks;
        table.draw();
    }

    // Process list UI js
    processlist_ui.init(1); // 1 means that contexttype is feeds and virtual feeds (other option is 1 for input)

    //------------------------------------
    // Table actions
    //------------------------------------
    $("#user-tasks-table").bind("onEdit", function (e) {

        setTimeout(function () { // The onEdit event is triggered before adding the html of the field, we need to wait until the html there before we can add the datetimepicker and events
            $('.date').each(function (index) {
                $(this).datetimepicker({language: 'en-EN'});
            });
            add_frequency_html_events();
        }, 100);
    });
    $("#user-tasks-table").bind("onSave", function (e, id, fields_to_update) {
        console.log(fields_to_update)
        if (fields_to_update.frequency != undefined)
            fields_to_update.frequency = JSON.parse(fields_to_update.frequency); // frequency is a string, when we call task.setTask it stringfys all the fields, if we don't parse it now the final strinng is corrupted JSON 
        task.setTask(id, fields_to_update);
        draw_user_tasks();
    });
    $("#user-tasks-table").bind("onDelete", function (e, id, row) {
        $('#taskDeleteModal').modal('show');
        $('#taskDeleteModal').attr('the_id', id);
        $('#taskDeleteModal').attr('the_row', row);
    });
    $("#taskDelete-confirm").click(function () {
        var id = $('#taskDeleteModal').attr('the_id');
        var row = $('#taskDeleteModal').attr('the_row');
        task.deleteTask(id);
        table.remove(row);
        table.draw();
        $('#taskDeleteModal').modal('hide');
    });
    $("#user-tasks-table").on('click', '.icon-wrench', function () {
        var i = table.data[$(this).attr('row')];
        console.log(i);
        var contextid = i.id; // Task ID
        var contextname = "";
        if (i.name != "")
            contextname = i.tag + " : " + i.name;
        else
            contextname = i.tag + " : " + i.id;
        var processlist = processlist_ui.decode(i.processList); // Task process list
        processlist_ui.load(contextid, processlist, contextname, null, null); // load configs
    });
    $("#save-processlist").click(function () {
        console.log(processlist_ui)
        var result = task.setProcessList(processlist_ui.contextid, processlist_ui.encode(processlist_ui.contextprocesslist));
        if (result.success) {
            processlist_ui.saved(table);
        } else {
            alert('ERROR: Could not save processlist. ' + result.message);
        }
    });
    $("#user-tasks-table").bind("onDraw", function (e, id, row) {
        // Replace dates that are 0 with the relevant information
        $('[field="time"]').each(function () { // Last run
            if ($(this).html() === "1/1/1970 0:0") {
                $(this).html("Never");
            }
        });
        $('[field="run_on"]').each(function () { // When frequency is 0 it means that task is only run once (on the start date). When this is the case run_on is set to 0 (1/1/1970 0:0)
            if ($(this).html() === "1/1/1970 0:0") {
                $(this).html("Never");
            }
        });
    });
    // ----------------------------------------------------------------------------------------
    // Actions
    // ----------------------------------------------------------------------------------------
    $('#create-task').click(function () {
        // Frequency field
        $('#task-create-frequency').html(get_frequency_html({type: 'once_a_month'}));
        add_frequency_html_events();
        // Start date field
        $('#task-create-run-on').datetimepicker({language: 'en-EN', useCurrent: true});
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
            var description = $('#task-create-name').val();
            var tag = $('#task-create-tag').val();
            var frequency = get_frequency_field('#task-create-frequency');
            var run_on = parse_timepicker_time($('#task-create-run-on input').val());
            var result = task.createTask(name, description, tag, frequency, run_on);
            if (result.success == false)
                $('#task-create-message').html('<p>' + result.message + '</p>').show();
            else
                $('#taskCreateModal').modal('hide');
        }
        draw_user_tasks();
    });
    // ----------------------------------------------------------------------------------------
    // Functions
    // ----------------------------------------------------------------------------------------
    $('#user-tasks-table > tbody:nth-child(1) > tr > th:nth-child(1) > a').click();
</script>

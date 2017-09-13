<?php
defined('EMONCMS_EXEC') or die('Restricted access');
global $path, $fullwidth, $session;
$fullwidth = true;
?>
<link href="<?php echo $path; ?>Modules/task/task.css" rel="stylesheet">
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/task/task.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/table.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/custom-table-fields.js"></script>

<!-------------------------------------------------------------------------------------------
MAIN
-------------------------------------------------------------------------------------------->
<div id="tasks-wrapper">

    <div class="page-content" style="padding-top:15px">
        <h2>Tasks</h2>

        <div style="padding-bottom:15px">
            <div id="create-task"><i class="icon-plus"></i>Create task</div>
            <div class="userstitle"><span id="groupname">Users</span></div>
            <div id="groupdescription"></div>
        </div>

        <h3>My tasks</h3>
        <div id="no-user-tasks" class="alert alert-block"><p>You haven't got any tasks</p></div>
        <table id="user-tasks-table" class='table'></table>

        <h3 class='if-groups-support'>My groups tasks</h3>
        <div id="no-group-member-tasks" class="alert alert-block if-groups-support"><p>You haven't got any tasks</p></div>
        <table id="group-mambers-tasks" class='table if-groups-support'>
            <p>ToDo</p>
        </table>

    </div>
</div>

<!-------------------------------------------------------------------------------------------
MODALS
-------------------------------------------------------------------------------------------->

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

        // Extend table library field types
        for (z in customtablefields)
            table.fieldtypes[z] = customtablefields[z];
        table.element = "#user-tasks-table";
        //table.groupprefix = "Node ";
        table.groupby = 'tag';
        table.deletedata = false;
        table.groupfields = {
        };
        table.fields = {
            'id': {'title': "<?php echo _('Id'); ?>", 'type': "fixed"},
            'tag': {'title': "<?php echo _('Tag'); ?>", 'type': "text"},
            'name': {'title': '<?php echo _("Name"); ?>', 'type': "text"},
            'description': {'title': '<?php echo _("Description"); ?>', 'type': "text"},
            'processList': {'title': '<?php echo _("Process list"); ?>', 'type': "processlist"},
            'frequency': {'title': "<?php echo _('Frequency'); ?>", 'type': "text"},
            'enabled': {'title': "<?php echo _('Enabled'); ?>", 'type': "icon", 'trueicon': "icon-ok", 'falseicon': "icon-remove"},
            'time': {'title': "<?php echo _('Last run'); ?>", 'type': "fixed"},
            // Actions
            'edit-action': {'title': '', 'type': "edit"},
            'delete-action': {'title': '', 'type': "delete"},
            'processlist-action': {'title': '', 'type': "iconbasic", 'icon': 'icon-wrench'}
        }

        table.data = user_tasks;
        table.draw();
    }

    // Process list UI js
    processlist_ui.init(1); // 1 means that contexttype is feeds and virtual feeds (other option is 1 for input)

    //------------------------------------
    // Table actions
    //------------------------------------
    $("#user-tasks-table").bind("onSave", function (e, id, fields_to_update) {
        task.setTask(id, fields_to_update);
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
        
    // ----------------------------------------------------------------------------------------
    // Actions
    // ----------------------------------------------------------------------------------------


</script>

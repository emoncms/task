// AJAX
var task = {
    'getUserTasks': function () {
        var result = {};
        $.ajax({url: path + "task/getusertasks", dataType: 'json', async: false, success: function (data) {
                result = data;
            }});
        return result;
    },
    'setTask': function (id, fields) {
        var result = {};
        $.ajax({url: path + "task/settask", data: "taskid=" + id + "&fields=" + JSON.stringify(fields), dataType: 'json', async: false, success: function (data) {
                result = data;
            }});
        return result;
    },
    'deleteTask': function (id) {
        var result = {};
        $.ajax({url: path + "task/deletetask", data: "taskid=" + id, dataType: 'json', async: false, success: function (data) {
                result = data;
            }});
        return result;
    },
    'setProcessList': function (taskid, processlist) {
        var result = {};
        $.ajax({url: path + "task/setprocesslist.json?id=" + taskid, method: "POST", data: "processlist=" + processlist, async: false, success: function (data) {
                result = data;
            }});
        return result;
    },
    'createTask': function (name, description, tag, frequency, run_on) {
        var result = {};
        $.ajax({url: path + "task/createtask.json?name=" + name + "&description=" + description + "&tag=" + tag + "&frequency=" + frequency + "&run_on=" + run_on, async: false, success: function (data) {
                result = data;
            }});
        return result;
    },
    'runTask': function (taskid) {
        var result = {};
        $.ajax({url: path + "task/runusertask.json?id=" + taskid, async: false, success: function (data) {
                result = data;
            }});
        return result;
    },
};

// ----------------------------------------------------------------------------------------
// Functions
// ----------------------------------------------------------------------------------------
function draw_user_tasks(selector, user_tasks) {
    $(selector).html('');
    if (user_tasks.length > 0)
        $('#no-user-tasks').hide();
    else
        $('#user-tasks-list').show();
    table.element = selector;
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
        'id': {'title': "Id", 'type': "fixed"},
        'tag': {'title': "Tag", 'type': "text"},
        'name': {'title': 'Name', 'type': "text"},
        'description': {'title': 'Description', 'type': "text"},
        'processList': {'title': 'Process list', 'type': "processlist"},
        'frequency': {'title': "Frequency <i title='When frequency is 0 the task will only be run once (no new Run On time will be set)' class='icon-question-sign'></i>", 'type': "frequency"},
        'enabled': {'title': 'Enabled', 'type': "icon", 'trueicon': "icon-ok", 'falseicon': "icon-remove"},
        'time': {'title': "Last run", 'type': "fixeddate"},
        'run_on': {'title': 'Next run', 'type': "date"},
        // Actions
        'edit-action': {'title': '', 'type': "edit"},
        'delete-action': {'title': '', 'type': "delete"},
        'processlist-action': {'title': '', 'type': "iconbasic", 'icon': 'icon-wrench'},
        'run_task': {'title': '', 'type': "run_task"},
    }

    table.data = user_tasks;
    table.draw();
    bind_table_events(selector);
}

function bind_table_events(selector) {
    $(selector).bind("onEdit", function (e) {

        setTimeout(function () { // The onEdit event is triggered before adding the html of the field, we need to wait until the html there before we can add the datetimepicker and events
            $('.date').each(function (index) {
                $(this).datetimepicker({language: 'en-EN', weekStart: 1});
            });
            add_frequency_html_events();
        }, 100);
    });
    $(selector).bind("onSave", function (e, id, fields_to_update) {
        if (fields_to_update.frequency != undefined)
            fields_to_update.frequency = JSON.parse(fields_to_update.frequency); // frequency is a string, when we call task.setTask it stringfys all the fields, if we don't parse it now the final strinng is corrupted JSON 
        task.setTask(id, fields_to_update);
        draw_user_tasks(selector, task.getUserTasks());
    });
    $(selector).bind("onDelete", function (e, id, row) {
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
    $(selector).on('click', '.icon-wrench', function () {
        var i = table.data[$(this).attr('row')];
        console.log(i);
        var contextid = i.id; // Task ID
        var contextname = "";
        if (i.name != "")
            contextname = i.tag + " : " + i.name;
        else
            contextname = i.tag + " : " + i.id;
        var processlist = processlist_ui.decode(i.processList); // Task process list
        processlist_ui.load(contextid, processlist, contextname, null, null); // show process list modal
        //Set default process to add
        $("#process-select").val('task__feed_last_update_higher');
        $("#process-select").change();
    });
    $("#save-processlist").unbind('click').bind('click', function () { // the reason for unbinding first is that otherwise the event gets triggered twice, I haven't been able to find the second bind so this is the best solution I could find
        console.log(processlist_ui)
        var result = task.setProcessList(processlist_ui.contextid, processlist_ui.encode(processlist_ui.contextprocesslist));
        if (result.success) {
            processlist_ui.saved(table);
        } else {
            alert('ERROR: Could not save processlist. ' + result.message);
        }
    });
    $(selector).bind("onDraw", function (e, id, row) {
        // Replace dates that are 0 with the relevant information
        $('[field="time"]').each(function () { // Last run
            var date = new Date($(this).html());
            var time = date.getTime();
            console.log(time);
            if ($(this).html() === "1/1/1970 00:00" || $(this).html() === "1/1/1970 01:00") {
                $(this).html("Never");
            }
        });
        $('[field="run_on"]').each(function () { // When frequency is 0 it means that task is only run once (on the start date). When this is the case run_on is set to 0 (1/1/1970 0:0)
            if ($(this).html() === "1/1/1970 00:00" || $(this).html() === "1/1/1970 01:00") {
                $(this).html("Never");
            }
        });
    });
}

function load_custom_table_fields() {
    // Extend table library field types with customtablefields
    for (z in customtablefields)
        table.fieldtypes[z] = customtablefields[z];
    // Extend table with a new fields specific to the task module
    for (z in taskcustomtablefields)
        table.fieldtypes[z] = taskcustomtablefields[z];
}
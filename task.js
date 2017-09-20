
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


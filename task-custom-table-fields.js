/*
 table.js is released under the GNU Affero General Public License.
 See COPYRIGHT.txt and LICENSE.txt.
 
 Part of the OpenEnergyMonitor project: http://openenergymonitor.org
 2016-12-20 - Expanded tables by : Nuno Chaveiro  nchaveiro(a)gmail.com  
 */

var taskcustomtablefields = {
    frequency: {
        'draw': function (t, row, child_row, field) {
            var frequency = JSON.parse(t.data[row][field]);
            if (frequency.type == 'one_time')
                var string = 'One time';
            if (frequency.type == 'once_a_month')
                var string = 'Once a month';
            if (frequency.type == 'number_of') {
                var string = '';
                var first = true;
                if (frequency.weeks != 0) {
                    string += frequency.weeks + ' weeks';
                    first = false;
                }
                if (frequency.days != 0) {
                    string += first === false ? ', ' + frequency.days + ' days' : frequency.days + ' days';
                    first = false;
                }
                if (frequency.hours != 0) {
                    string += first === false ? ', ' + frequency.hours + ' hours' : frequency.hours + ' hours';
                    first = false;
                }
                if (frequency.minutes != 0) {
                    string += first === false ? ', ' + frequency.minutes + ' minutes' : frequency.minutes + '  minutes';
                    first = false;
                }
                if (frequency.seconds != 0) {
                    string += first === false ? ', ' + frequency.seconds + ' seconds' : frequency.seconds + 'seconds';
                }
            }
            return string;
        },
        'edit': function (t, row, child_row, field) { /// Here aqui todo
            var frequency = JSON.parse(t.data[row][field]);
            return get_frequency_html(frequency);
        },
        'save': function (t, row, child_row, field) {
            return get_frequency_field("[row='" + row + "'][child_row='" + child_row + "'][field='" + field + "']");
        }
    },
    'date': {
        'draw': function (t, row, child_row, field) {
            var date = new Date();
            date.setTime(1000 * t.data[row][field]); //from seconds to miliseconds
            return (date.getDate() + '/' + (date.getMonth() + 1) + '/' + date.getFullYear() + ' ' + date.getHours() + ':' + date.getMinutes());
        },
        'edit': function (t, row, child_row, field) {
            var date = new Date();
            date.setTime(1000 * t.data[row][field]); //from seconds to miliseconds
            var day = date.getDate();
            var month = date.getMonth() + 1; // getMonth() returns 0-11
            var year = date.getFullYear();
            var hours = date.getHours();
            var minutes = date.getMinutes();
            return '<div class="input-append date" id="' + field + '-' + row + '-' + t.data[row][field] + '" data-format="dd/MM/yyyy hh:mm" data-date="' + day + '/' + month + '/' + year + ' ' + hours + ':' + minutes + '"><input data-format="dd/MM/yyyy hh:mm" value="' + day + '/' + month + '/' + year + ' ' + hours + ':' + minutes + '" type="text" /><span class="add-on"> <i data-time-icon="icon-time" data-date-icon="icon-calendar"></i></span></div>';
        },
        'save': function (t, row, child_row, field) {
            return parse_timepicker_time($("[row='" + row + "'][child_row='" + child_row + "'][field='" + field + "'] input").val());
        }
    },
    'fixeddate': {
        'draw': function (t, row, child_row, field) {
            var date = new Date();
            date.setTime(1000 * t.data[row][field]); //from seconds to miliseconds
            return (date.getDate() + '/' + (date.getMonth() + 1) + '/' + date.getFullYear() + ' ' + date.getHours() + ':' + date.getMinutes());
        }
    }
};


// date and fixeddate fields functions
function parse_timepicker_time(timestr) {
    var tmp = timestr.split(" ");
    if (tmp.length != 2)
        return false;

    var date = tmp[0].split("/");
    if (date.length != 3)
        return false;

    var time = tmp[1].split(":");
    if (time.length != 2)
        return false;

    return new Date(date[2], date[1] - 1, date[0], time[0], time[1], 0).getTime() / 1000;
}

// Frequency field functions
function get_frequency_html(frequency) {
        console.log(frequency)
        var str = "<input name='frequency-type' type='radio' value='one_time' ";
        str += frequency.type == "one_time" ? "checked" : "";
        str += "> Only once</input></br>";

        str += "<input name='frequency-type' type='radio' value='once_a_month' ";
        str += frequency.type == "once_a_month" ? "checked" : "";
        str += "> Once a month</input></br>";

        str += "<input name='frequency-type' type='radio' value='number_of' ";
        str += frequency.type == "number_of" ? "checked" : "";
        str += "> Number of...</input>";

        str += "<table style='margin-top:15px' class='table ";
        str += frequency.type !== "number_of" ? "hide'" : "'";
        ;
        str += "'><tr><td>Weeks</td><td><input id='frequency-weeks' type='number' min='0' value='" + frequency.weeks + "' style='width:45px' /></td></tr>";
        str += "<tr><td>Days</td><td><input id='frequency-days' type='number' min='0' value='" + frequency.days + "' style='width:45px' /></td></tr>";
        str += "<tr><td>Hours</td><td><input id='frequency-hours' type='number' min='0' value='" + frequency.hours + "' style='width:45px' /></td></tr>";
        str += "<tr><td>Minutes</td><td><input id='frequency-minutes' type='number' min='0' value='" + frequency.minutes + "' style='width:45px' /></td></tr>";
        str += "<tr><td>Seconds</td><td><input id='frequency-seconds' type='number' min='0' value='" + frequency.seconds + "' style='width:45px' /></td></tr></table>";

        return str;
    }

    function add_frequency_html_events() {
        $('[name="frequency-type"').change(function () {
            var type = $('[name="frequency-type"]:checked').val();
            if (type == 'number_of')
                $(this).siblings('table').show();
            else
                $(this).siblings('table').hide();
        });
    }

    function get_frequency_field(selector) {
        var frequency = {};
        frequency.type = $(selector + " [name='frequency-type']:checked").val();
        if (frequency.type == 'number_of') {
            frequency.weeks = $(selector + " #frequency-weeks").val();
            frequency.days = $(selector + " #frequency-days").val();
            frequency.hours = $(selector + " #frequency-hours").val();
            frequency.minutes = $(selector + " #frequency-minutes").val();
            frequency.seconds = $(selector + " #frequency-seconds").val();
        }
        return JSON.stringify(frequency);
    }
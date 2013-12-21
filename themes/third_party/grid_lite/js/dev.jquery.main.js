

var callbacks = {display: {},    beforeSort: {},    afterSort: {}};

Matrix = function ()
{
};

Matrix.bind = function (fieldtype, event, callback)
{
	callbacks[event][fieldtype] = callback;
};


$(function ()
{
    Matrix.bind('expresso', 'display', function (cell)
    {

    });

	Matrix.bind('file', 'display', function (cell)
    {
		add_file(cell);
    });

	Matrix.bind('date', 'display', function (cell)
    {
		gl_date = new Date();
        cell.find('input:first').datepicker(
        {
            dateFormat: $.datepicker.W3C + EE.date_obj_time,
            defaultDate: new Date(gl_date)
        });

    });

	Matrix.bind('index', 'display', function (cell)
    {
		addMenu(cell);
    });

});

(function($){
  jQuery.fn.Grid=function(){
    // code comes here
	$(".grid_lite-add").click(function ()
    {
        field_name = $(this).attr("rel");
        insertRow(field_name, "end", $(this).prev("table").children("tbody"));
    });

	// initializes
	 $.each($("tr.gl_rows"), function (index) {cell_c($(this)); });

    function cell_c(row)
    {
        $.each(row.find('td.grid_cell'), function (index)
        {
            type = $(this).data("type");

            if (type == undefined) { return; };

            if (callbacks['display'][type] != undefined)
            {
                callbacks['display'][type].call('row', $(this))
            };

        });
    }


   function insertRow(field, where, srcElement)
    {
        $obj = $("#" + field_name + "-table");
        template = $obj.data("template");
        rows = $obj.data("rows");
        rows_max = $obj.data("limit");

        if (rows_max !== '' && rows > rows_max)
        {
            return true;
        }

        if (rows == 1)
        {
            $(srcElement).html("");
        }

        if (where == "end")
        {
            row = $(srcElement).append(template.replace(/{row_id}/g, rows));
        }

        if (where == "before")
        {
            row = $(template.replace(/{row_id}/g, rows)).insertBefore(srcElement);
        }

        if (where == "after")
        {
            row = $(template.replace(/{row_id}/g, rows)).insertAfter(srcElement);
        }
        //alert(row);
     //   alert(cell_c(row));

        rows = rows + 1;

        $obj.data("rows", rows);


        //.find(".mx_file:first")attr("id");
        callbacks['display']['file'].call('row', row);
	 }


  }
})(jQuery);


    function addMenu(obj)
    {
        $(".dand-target").each(function ()
        {
        });
        var el = obj;
        var offset = $(el).offset()
         obj.mousedown(function (e)
        {
            eat = this;
            var evt = e;
            $(this).mouseup(function (e)
            {
                var srcElement = $(this);
                //$(this).unbind("mouseup");
                if (evt.button == 2)
                {

                    // Detect mouse position
                    var d =
                    {
                    },
                        x, y;
                    if (self.innerHeight)
                    {
                        d.pageYOffset = self.pageYOffset;
                        d.pageXOffset = self.pageXOffset;
                        d.innerHeight = self.innerHeight;
                        d.innerWidth = self.innerWidth;
                    }
                    else if (document.documentElement && document.documentElement.clientHeight)
                    {
                        d.pageYOffset = document.documentElement.scrollTop;
                        d.pageXOffset = document.documentElement.scrollLeft;
                        d.innerHeight = document.documentElement.clientHeight;
                        d.innerWidth = document.documentElement.clientWidth;
                    }
                    else if (document.body)
                    {
                        d.pageYOffset = document.body.scrollTop;
                        d.pageXOffset = document.body.scrollLeft;
                        d.innerHeight = document.body.clientHeight;
                        d.innerWidth = document.body.clientWidth;
                    }
                    x = e.pageX - eat.offsetLeft;
                    y = e.pageY - 100;

                    $("#menu_gl").css(
                    {
                        top: y,
                        left: x
                    }).fadeIn();

                    // When items are selected
                    $("#menu_gl").find("A").unbind("click");
                    $("#menu_gl").find("LI:not(.disabled) A").click(function ()
                    {
                        //$(this).unbind("click");
                        $(".contextMenu").hide();
                        // Callback
                        action = $(this).attr("href").substr(1);

                        switch (action)
                        {

                        case "delete":
                            $(srcElement).parents("tr:first").remove();
                            close_menu();
                            break;
                        case "insert_below":
                            field_name = $(srcElement).parents("tbody:first").attr("rel");
                            insertRow(field_name, "after", $(srcElement).parents("tr:first"));
                            close_menu();
                            break;

                        case "insert_above":
                            field_name = $(srcElement).parents("tbody:first").attr("rel");
                            insertRow(field_name, "before", $(srcElement).parents("tr:first"));
                            close_menu();
                            break;
                        };
                        return false;
                    });

                    setTimeout(function ()
                    {
                        $(document).click(function ()
                        {
                            close_menu();
                            return false;
                        });
                    }, 0);
                }
            })
        });

        $(el).add("UL.GridLiteMenu").bind("contextmenu", function ()
        {
            return false;
        });
        return obj;
    }

    function close_menu()
    {
        $(document).unbind('click');
        $("#menu_gl").fadeOut();
    }

    function destroyMenu()
    {
        $(".dand-target").unbind("mousedown").unbind("mouseup");
    }
	//check it
   function add_file(obj)
    {
            file_obj = obj.find(".mx_file:first");
            content_type = file_obj.attr("data-content-type"), directory = file_obj.attr("data-directory");

            $.ee_filebrowser.add_trigger(file_obj, Math.floor(Math.random() * 1111), {
                content_type: content_type,
                directory: directory
            }, function (file, field)
            {
                addfile(file.upload_location_id, file.file_name, file.thumb, $(this));
            });
    }

    function addfile(u_l_id, file_name, thumb_id, element)
    {
        var full_url = EE.upload_directories[u_l_id].url + file_name;

        relative_url = full_url.replace(/http:\/\/[^\/]+/i, "");

        thumb = '<img src="' + thumb_id + '" class="grid-img"/>';

        $td_cell = element.parents("td:first");
        $td_cell.find(".grid-img").replaceWith(thumb);
        $td_cell.find(".gr-file-name").html(file_name);
        element.parents("div:first").hide();
        $td_cell.find(".grid_file").show();
        $("input[name='" + $(element).attr("rel") + "[dir]']").val(u_l_id);
        $("input[name='" + $(element).attr("rel") + "[file]']").val(file_name);
    }

$(document).ready(function ()
{

	$.fn.Grid();
	var fixHelper = function(e, ui) {
    ui.children().each(function() {
        $(this).width($(this).width());
    });
    return ui;
	};
    $(".dand").sortable(
    {
        cancel: ".dand-disabled",
        axis: "y",
        handle: ".dand-target",
       // forcePlaceholderSize: true,
        placeholder: "ui-state-highlight",

       // cursor: "crosshair",
		    helper: fixHelper,
        start: function (event, ui)
        {

        },
        stop: function (event, ui)
        {
            i = 1;
            body = $(ui.item).parents("tbody:first");
            $(body).children("tr").each(function (index)
            {
                $(this).children("td:first").html(i);
                i++;
            });

        }
    });




        //      callbacks[callback][obj.type].call(obj.dom.$td, obj);
        //obj.callback('display', 'onDisplayCell');
        //date_ini();
        //destroyMenu();
        //addMenu();
        //add_file();
   /* $(".remove_item").bind('click', function ()
    {
        $td_cell = $(this).parents("td:first");
        $td_cell.find("div:first").show();
        $td_cell.find(".grid_file").hide();
        $td_cell.find("input").val("");
        return false;
    });
        $(".remove_item").bind('click', function ()
        {
            $td_cell = $(this).parents("td:first");
            $td_cell.find("div:first").show();
            $td_cell.find(".grid_file").hide();
            $td_cell.find("input").val("");
            return false;
        });
        return true;
    } */

});

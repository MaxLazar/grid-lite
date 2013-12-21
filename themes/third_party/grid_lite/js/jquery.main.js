$(document).ready(function ()
{

    $(".grid_lite-add").click(function ()
    {
        field_name = $(this).attr("rel");
        insertRow(field_name, "end", $(this).prev("table").children("tbody"));
    });

    $(".mx_file").click(function ()
    {
    });

    date_ini();
    addMenu();
    add_file();

    function date_ini()
    {
        gl_date = new Date();
        $(".addDate").datepicker(
        {
            dateFormat: $.datepicker.W3C + EE.date_obj_time,
            defaultDate: new Date(gl_date)
        })
        $(".addDate").removeClass("addDate");
    }

    $(".dand").sortable(
    {
        cancel: ".dand-disabled",
        axis: "y",
        handle: ".dand-target",
        forcePlaceholderSize: true,
        cursor: "crosshair",
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

    function addMenu()
    {
        $(".dand-target").each(function ()
        {
            var el = $(this);
            var offset = $(el).offset()
            $(this).mousedown(function (e)
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
        });
        return $(this);
    };

    function close_menu()
    {
        $(document).unbind('click');
        $("#menu_gl").fadeOut();
    }

    function destroyMenu()
    {
        $(".dand-target").unbind("mousedown").unbind("mouseup");
    };

    function add_file()
    {

        if (EE22)
        {

            $.ee_filebrowser.add_trigger(".mx_file", "", {
                //   directory:    $(this).attr("data-directory"),
                //   content_type:  $(this).attr("data-content-type")
                content_type: "any",
                directory: "all"
            }, function (file, field)
            {
                addfile(file.upload_location_id, file.file_name, file.thumb, $(this));
            });

        }
        else
        {
            $.ee_filebrowser.add_trigger(".mx_file", "", function (file, field)
            {
                addfile(file.directory, file.name, file.thumb, $(this));
                $.ee_filebrowser.reset();
            });

        }

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


    $(".remove_item").bind('click', function ()
    {
        $td_cell = $(this).parents("td:first");
        $td_cell.find("div:first").show();
        $td_cell.find(".grid_file").hide();
        $td_cell.find("input").val("");
        return false;
    });


    function insertRow(field, where, srcElement)
    {

		$obj = $("#" + field_name + "-table");
        template = $obj.data("template");
        rows = $obj.data("rows");
		rows_max = $obj.data("limit");

		if  (rows_max != '' && 	rows > rows_max) {return true;}

        if (rows == 1)
        {
            $(srcElement).html("");
        }

        if (where == "end")
        {
            $(srcElement).append(template.replace(/{row_id}/g, rows));
        }

        if (where == "before")
        {
            $(template.replace(/{row_id}/g, rows)).insertBefore(srcElement);
        }
        if (where == "after")
        {
            $(template.replace(/{row_id}/g, rows)).insertAfter(srcElement);
        }

        rows = rows + 1;

        $obj.data("rows", rows);

        date_ini();
        destroyMenu();
        addMenu();
        add_file();

        $(".remove_item").bind('click', function ()
        {
            $td_cell = $(this).parents("td:first");
            $td_cell.find("div:first").show();
            $td_cell.find(".grid_file").hide();
            $td_cell.find("input").val("");
            return false;
        });
        return true;
    };

});

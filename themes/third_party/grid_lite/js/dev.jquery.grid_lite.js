$(function ()
{
    $.fn.removeCol = function (col)
    {
        if (!col)
        {
            col = 1;
        }
        $("tr td:nth-child(" + col + "), tr th:nth-child(" + col + ")", this).remove();
        return this;
    };

    if (last_index == 1)
    {
        create_col(true);

    }
    else
    {
        sortRows();
    }

    function sortRows()
    {
        $("#grid_table").sortable(
        {
            cancel: ".dand-disabled",
            axis: "y",
            handle: ".grid_lite-sort",
            forcePlaceholderSize: false,
            cursor: "crosshair",
            connectWith: "#grid_table",
            start: function (event, ui)
            {

            },
            stop: function (event, ui)
            {


            }
            ,helper: function(e, ui) {
    ui.children().each(function() {
        $(this).width($(this).width());
    });
    return ui;
			  }

        }).disableSelection();
    };

    $(".grid_delete").live("click", function ()
    {
        $(this).parents("tr.column-line:first").remove();

    });

    $(".grid_lite-add").live("click", function ()
    {
        create_col(true);
    });

    $(".settings_open").live("click", function ()
    {
        if ($(this).parents("tr:first").next("tr").is(":visible"))
        {
            $(this).parents("tr:first").next("tr").hide();
        }
        else
        {
            $(this).parents("tr:first").next("tr").show();
        }

        return false;
    });

    grid_width = $("#mainContent").width();

    $("#grid_field").parents("div:first").css("width", grid_width - 100);


    function delete_col(index)
    {
        $(index).parents("tr:first").remove();
    }

    function create_col(stat)
    {

        last_index = last_index + 1;

        field_menu = "<select name=\"grid_lite_[col][cell_" + last_index + "][type]\" class=\"celltype\">";

        if (stat == false)
        {
            stat = "style=\"display:none\""
        };

        for (key in field)
        field_menu = field_menu + "<option value=\'" + key + "\'>" + key + "</option>";

        field_menu = field_menu + "</select>";
        i = 1;
        var settings = field["text"].replace(/\{COL_ID\}/g, last_index);

        template = "<tr class=\"column-line\"><td><table class=\"mainTable grid_table\"  border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"margin:10px 0 10px 0\"><tr><th style=\"width:20px;padding:0px;margin:0px;\"><span class=\"grid_lite-sort\"></span></th><th>Label</th><th>Name</th><th>Type</th><th style=\"width:50px;\">Width</th><th style=\"width:10px;padding:0px;margin:0px;\"></th></tr><tr class=\"cell_" + last_index + "\"><td class=\"dand-target\"><a style=\"text-decoration: none;\" href=\"#\" class=\"settings_open\">+/-</a></td><td><input name=\"grid_lite_[col][cell_" + last_index + "][label]\" value=\"\" class=\"label\" type=\"text\"></td><td><input name=\"grid_lite_[col][cell_" + last_index + "][name]\" value=\"\" type=\"text\"><input type=\"hidden\" name=\"grid_lite_[col][cell_" + last_index + "][order]\" value=\"" + last_index + "\" /><input type=\"hidden\" name=\"grid_lite_[col][cell_" + last_index + "][col_id]\" value=\"" + last_index + "\" /></td><td>" + field_menu + "</td><td><input name=\"grid_lite_[col][cell_" + last_index + "][width]\" value=\"\" class=\"label\" type=\"text\"></td><td><span class=\"grid_delete\"></span></td></tr> <tr  " + stat + " id=\"cell_" + last_index + "\"><td colspan=\"6\"><table class=\"padTable\"  border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"width:100%;\"><tr class=\"header\"><th width=\"40%\">Custom Field Options</th><th></th></tr><tbody>" + settings + "</tbody></table></td></tr> </table> </td></tr>";


        $("#grid_table").append(template);
        sortRows()


    }



    function change_settings(index, value)
    {
        var myIndex = $(index).closest("td").prevAll("td").length + 1;
        var parent = $(index).parents("table:first");
        parent.removeCol(myIndex);
    }


    $(".celltype").live("change", function ()
    {
        var myIndex = $(this).parents("tr:first");
        val2 = $(this).val();
        col_index = (((($(this).attr("name")).split("["))[2]).split("]"))[0];
        settings = field[val2].replace(/\{COL_ID\}/g, col_index);

        myIndex.next("tr").children("td:first").html("<table class=\"padTable\"  border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"width:100%;\"><tr class=\"header\"><th width=\"40%\">Custom Field Options</th><th></th></tr><tbody>" + settings + "</tbody></table>");

    });

    $(".label").live("keyup", function (e)
    {
        var myIndex = $(this).closest("td").prevAll("td").length;
        textValue = $(this).val();
        $("#grid_field tr:eq(0)").find("th:eq(" + myIndex + ")").html(textValue);
    });

});

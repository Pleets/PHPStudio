$(function(){

    $("body").delegate(".btn-read-file", "click", function(event)
    {
        event.preventDefault();

        var project   = $(this).attr('data-project');
        var absolute   = $(this).attr('data-id');
        var file_name = $(this).attr('data-name');
        var url = $(this).attr('data-resource');

        var call = eval($(this).attr('data-callback')) || {};

        call.success = call.success || new Function();
        call.before  = call.before  || new Function();
        call.error   = call.error   || new Function();

        var d = new Date();
        var n = d.getTime();

        var title = $("#worksheet-collector .worksheet-item-title").last().clone();

        if (title.hasClass('active'))
            title.removeClass('active');

        var other_tabs = $("div[data-tab][data-absolute='" + absolute + "']");

        if (!other_tabs.length)
        {
            title.html(file_name + "&nbsp; <button class='ui small compact basic button btn-remove-worksheet'>x</button>").attr('data-tab', n);

            $("#worksheet-collector .worksheet-item-title").last().parent().append(title);

            var content = $("#worksheet-collector .worksheet-item-content").last().clone();
            content.empty().attr('data-absolute', absolute);

            $("#worksheet-collector .worksheet-item-content").last().parent().append(content);

            if (content.hasClass('active'))
                content.removeClass('active');

            content.attr('data-tab', n);
            content.load(url, { project: project, id: n, file: absolute });
        }
        // focus

        $('.menu .item').tab();

        $(title).trigger('click');
    });

    $('.menu .item').tab();

    $("body").delegate(".btn-remove-worksheet", "click", function(event)
    {
        event.preventDefault();
        event.stopPropagation();

        var tab = $(this).parent().attr('data-tab');
        $("[data-tab='" + tab + "']").remove();
        $("a[data-tab='home']").trigger("click");
    });
});
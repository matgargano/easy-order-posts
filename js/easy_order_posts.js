jQuery(function($) {
    var id, ids=[];
    $( "#post-list" ).sortable({
        stop: function(){
            ids = [];
            $(this).find('li').each(function(){
                id = $(this).data('id');
                ids.push(id);
            });
            $(this).closest('.post-list-wrap').find('input.order').val(JSON.stringify(ids));

        }
    });
    $( "#post-list" ).disableSelection();
});
jQuery(function($){

    $(document).ready(function(){
        $('.notice-dismiss').on('click', function(){
            $.post(ajaxurl,{
                  action: 'sirv_dismiss_review_notice',
                }).done(function(response){
                      //debug
                      console.log(response);

                }).fail(function(jqXHR, status, error){
                      console.error("Error during ajax request: " + error);                     
                });
        });
    }); //domready end
});
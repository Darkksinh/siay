jQuery(function ($) {

  let ids = {};
  let $instance = null;


  function filterItems(id=''){
    if( !!!$instance ) return;

    let indexes = ids[id];

    if( !!indexes ){
      for (let i = 0; i < $instance.itemsCount(); i++) {
        if(inArray(i, indexes)){
          $instance.enableItem(i);
        }else{
          $instance.disableItem(i);
        }
      }
    }else{
      for (let i = 0; i < $instance.itemsCount(); i++) {
        //$instance.enableItem(i);
        if(!!sirv_woo_product.showAllVariations){
          $instance.enableItem(i);
        }else{
          if (inArray(i, ids[sirv_woo_product.mainID])) {
            $instance.enableItem(i);
          }
        }
      }
    }

    $instance.jump(0);
  }


  function inArray(val, arr){
    return arr.indexOf(val) !== -1;
  }

  function onMVStart(){
    let defaultID = sirv_woo_product.mainID;
    /* $.each( $('#sirv-woo-gallery_' + defaultID).children(), function () {
      let id = $(this).attr('data-view-id');
      let index = $(this).attr('data-order');
      addIndex(id + '', index * 1);
    }); */

    let data = $('#sirv-woo-gallery_data_' + defaultID).attr('data-gallery-json');
    try {
      ids = JSON.parse(data);
    } catch (error) {
      console.error('SIRV: can\'t get gallery data from json');
      console.error('SIRV: ' + error);
    }
  }


  function addIndex(id, index){
    if ( !ids.hasOwnProperty(id) ){
      ids[id] = [index];
    }else{
      ids[id].push(index);
    }
  }


  $(document).ready(function () {

    onMVStart();

    $('input.variation_id').on('change', function () {
      let variation_id = $('input.variation_id').val();
      if (!!variation_id ) {
        filterItems(variation_id);
      }else{
        if (!!sirv_woo_product.showAllVariations){
          filterItems();
        } else { filterItems(sirv_woo_product.mainID); }

      }
    });

    $('.reset_variations').on('click', function () {
      filterItems(sirv_woo_product.mainID);
    });

    Sirv.on('viewer:ready', function (viewer) {
      $('.sirv-skeleton').removeClass('sirv-skeleton');
      $instance = Sirv.viewer.getInstance('#sirv-woo-gallery_' + sirv_woo_product.mainID);
    });
  }); //end dom ready
});

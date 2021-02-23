jQuery( function($){
  "use strict";

  $(document).ready( function(){

    $(window).on('update_woo_sirv_images', updateWooSirvImages);
    function updateWooSirvImages(){
      id = window.sirvProductID;
      let data = getStorageData(id);
      let $ul = $('#sirv-woo-images_'+ id);

      if(!isEmpty(data.items)){
        let galleryHTML = getGalleryHtml(id, data);
        //unBindEvents();
        $ul.empty();
        $ul.append(galleryHTML);
        reCalcGalleryData(id);
        imageSortable(id);
      }

      window.sirvProductID = '';
    }


    function getGalleryHtml(id, data) {
      let documentFragment = $(document.createDocumentFragment());
      let imgPattern = '?thumbnail=78&image';
      let action_tpl = '<ul class="actions">\n' +
        '<li><a href="#" class="delete sirv-delete-item tips" data-id="'+ id +'" data-tip="Delete image">Delete</a></li>\n' +
        '</ul >\n';

      $.each(data.items, function (index, item) {
        let liItem = '<li class="sirv-woo-gallery-item" data-order="' + item.order + '" data-type="' + item.type + '"data-provider="'+ item.provider +'" data-url-orig="' + item.url + '" data-view-id="'+ id +'">\n' +
          '<img class="sirv-woo-gallery-item-img" src="' + item.url + imgPattern + '">\n' +
          action_tpl +
          '</li>\n';


        documentFragment.append(liItem);
      });

      return documentFragment;
    }


    $('body').on('click', 'a.sirv-woo-add-online-videos', parseOnlineVideo);
    function parseOnlineVideo(e) {
      e.preventDefault();
      e.stopPropagation();

      let id = $(this).attr('data-id');

      let lines = $('#sirv-online-video-links_'+ id).val().replace(/\r\n/g, "\n").split(/\n/).filter(function(line){return !!line});
      if(!!lines){
        lines.forEach(function(link){
          let videoObj = getVideoObj(link);
          if(!isEmpty(videoObj)){
            renderOnlineVideo(id, videoObj);
            getVideoThumb(id, videoObj);
          }
        });

        $('#sirv-add-online-videos-container_' + id).hide();
        $('#sirv-online-video-links_'+ id).val('');
        $('#sirv_woo_gallery_container_' + id).on('click', 'a.sirv-woo-add-online-video', showOnlineVideosBlock);
      }
    }


    function renderOnlineVideo(id, data){
      let action_tpl = '<ul class="actions">\n' +
        '<li><a href="#" class="delete sirv-delete-item tips" data-id="'+ id +'" data-tip="Delete image">Delete</a></li>\n' +
        '</ul >\n';

      let $ul = $('#sirv-woo-images_' + id);
      let order = $ul.length - 1;

      $ul.append(
        '<li class="sirv-woo-gallery-item" data-order="' + order + '" data-type="online-video" data-provider="'+ data.provider +'" data-url-orig="" data-video-link="' + data.link +'" data-video-id="'+ data.videoID +'">\n' +
          '<span class="dashicons dashicons-format-video sirv-online-video-placeholder"></span>\n'+
            action_tpl +
        '</li>\n'
      );
    }


    function imageSortable(id=''){
      if(!!id){
        sortableBlock(id);
      }else{
        $.each($('.sirv-woo-images'), function(){
          let id = $(this).attr('data-id');
          sortableBlock(id);
        });
      }

    }


    function sortableBlock(id){
      $('#sirv-woo-images_' + id).sortable({
        cursor: 'move',
        scrollSensitivity: 40,
        forcePlaceholderSize: true,
        forceHelperSize: false,
        helper: 'clone',
        opacity: 0.65,
        placeholder: "sirv-sortable-placeholder",
        stop: function (event, ui) {
          reCalcGalleryData(id);
        }
      });
      $('#sirv-woo-images_' + id).disableSelection();
    }


    function reCalcGalleryData(id){
      let items = [];
      $('#sirv-woo-images_'+id+' .sirv-woo-gallery-item').each(function (index) {
        $(this).attr('data-order', index);
        let item = {
          url  : $(this).attr('data-url-orig'),
          type : $(this).attr('data-type'),
          provider: $(this).attr('data-provider'),
          order: index,
          viewId: id,
        }
        if(item.type == "online-video"){
          item.videoID = $(this).attr('data-video-id');
          item.videoLink = $(this).attr('data-video-link');
        }

        items.push(item);

      });

      let data = getStorageData(id);
      data.items = items;
      setStorageData(id, data);
      manageDeleteAllButtonState(id);

      if ($('#sirv-woo-gallery_' + id).hasClass('sirv-variation-container')) variationChanged($('#sirv-woo-gallery_' + id));
    }


    $('body').on('click', 'a.sirv-delete-item', deleteImage);
    function deleteImage(e){
      e.preventDefault();
      e.stopPropagation();

      let id = $(this).attr('data-id');

      $(this).closest('li.sirv-woo-gallery-item').remove();
      reCalcGalleryData(id);
    }


    $('.sirv-woo-delete-all').on('click', deleteAllImages);
    function deleteAllImages(){
      let id = $(this).attr('data-id');

      $(this).parent().siblings('.sirv-woo-images').empty();

      reCalcGalleryData(id);
    }


    function getStorageData(id, selector='#sirv_woo_gallery_data_'){
      return JSON.parse($(selector + id).val());
    }


    function setStorageData(id, data, selector='#sirv_woo_gallery_data_'){
      $(selector + id).val(JSON.stringify(data));
    }


    $('body').on('click', 'a.sirv-woo-add-online-video', showOnlineVideosBlock);
    function showOnlineVideosBlock(e) {
      e.preventDefault();
      e.stopPropagation();

      let id = $(this).attr('data-id');

      $('#sirv-add-online-videos-container_'+ id).slideDown();

      $('#sirv_woo_gallery_container_'+ id).off('click', 'a.sirv-woo-add-online-video', showOnlineVideosBlock);
    }


    $('body').on('click', 'a.sirv-woo-cancel-add-online-videos', cancelShowOnlineVideosBlock);
    function cancelShowOnlineVideosBlock(e) {
      e.preventDefault();
      e.stopPropagation();

      let id = $(this).attr('data-id');

      $('#sirv-add-online-videos-container_' + id).slideUp();
      $('#sirv-online-video-links_'+ id).val('');

      $('#sirv_woo_gallery_container_'+ id).on('click', 'a.sirv-woo-add-online-video', showOnlineVideosBlock);
    }


    function manageDeleteAllButtonState(id) {
      let $items = $('#sirv-woo-images_'+ id +' .sirv-woo-gallery-item');
      if($items.length > 5){
        $('#sirv-delete-all-images-container_'+id).show();
        $('#sirv-delete-all-images-container_' + id +' .sirv-woo-delete-all').on('click', deleteAllImages);
      }else{
        $('#sirv-delete-all-images-container_' + id).hide();
      }
    }


    function getVideoThumb(id, videoObj){
      switch (videoObj.provider) {
        case 'youtube':
          getYoutubeThumb(id, videoObj);
          break;
        case 'vimeo':
          getVimeoThumb(id, videoObj);
          break;

        default:
          break;
      }
    }


    function getVideoObj(url){
      let videoPatterns = [
        { provider: 'youtube', pattern: new RegExp('youtube\\.com.*(\\?v=|\\/embed\\/)(.{11})')},
        { provider: 'vimeo', pattern: new RegExp('vimeo\\.com.*?\\/(.*\\/)*(\\d*)')},
      ];

      let videoObj = {};

      videoPatterns.forEach(function(item){
        if(!!url.match(item.pattern)){
          videoObj.provider = item.provider;
          videoObj.videoID = url.match(item.pattern).pop();
          videoObj.link = url;
        }
      });

      return videoObj;
    }


    function getYoutubeThumb(id, data){
      let thumb =  'https://img.youtube.com/vi/' + data.videoID + '/0.jpg';
      setVideoThumb(id, data, thumb);
    }


    function getVimeoThumb(id, data){

      jQuery.getJSON('https://www.vimeo.com/api/v2/video/' + data.videoID + '.json?callback=?', { format: "json" }, function(response) {
        let thumb = response[0].thumbnail_medium;

        setVideoThumb(id, data, thumb);
      });
    }


    function setVideoThumb(id, videoData, thumb){
      let $videoItem = $('#sirv-woo-gallery_'+ id +' .sirv-woo-gallery-item[data-video-id='+videoData.videoID+']');
      console.log($videoItem);

      if(!!$videoItem){
        if(!!thumb){
          $videoItem.attr('data-url-orig', thumb);
          $('.sirv-online-video-placeholder', $videoItem).remove();

          $videoItem.append('<img class="sirv-woo-gallery-item-img" src="' + thumb + '">\n');
        }

        reCalcGalleryData(id);
      }
    }


    function isEmpty(obj){
      if(typeof obj =='object') return !!!Object.keys(obj).length;

      return !!!obj;
    }

    $('#woocommerce-product-data').on('woocommerce_variations_loaded', function () {
      updateVariation();
    });

    $('#variable_product_options').on('woocommerce_variations_added', function () {
      updateVariation();
    });


    function updateVariation(){
      $('.woocommerce_variation').each(function () {
        let $optionsBlock = $(this).find('.options:first');
        let $galleryBlock = $(this).find('.sirv-woo-gallery-container');

        let id = $galleryBlock.attr('data-id');

        $galleryBlock.insertBefore($optionsBlock);

        imageSortable(id);
      });
    }

    function variationChanged($el){
      $($el).closest('.woocommerce_variation').addClass('variation-needs-update');
      $('button.cancel-variation-changes, button.save-variation-changes').removeAttr('disabled');
      $('#variable_product_options').trigger('woocommerce_variations_input_changed');
    }

    //---------------initialization---------------------
    imageSortable();

  }); //onready end
});

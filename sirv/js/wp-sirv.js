jQuery(function($){

    $(document).ready(function(){

        /*-----------------global variables---------------*/
        let contentData = {};
        let scrollStack = [];
        let prev = -1;
        let maxFileSize;
        let maxFilesCount;
        let sirvFileSizeLimit;
        let profileTimer;
        let imgGallery = false;
        let emptyFolder = false;
        let noResults = false;
        let searchFrom = 0;
        let scrollSegmentLen = 100;
        window.shGalleryFlag = true;
        window.sirvViewerPath = '/';
        /*-------------global variables END---------------*/

        //code for drag area
        function onDrugEnterV(e) {
            e.stopPropagation();
            e.preventDefault();
            $('.sirv-drop-wrapper').show();
        }


        function onDrugEnterH(e) {
            e.stopPropagation();
            e.preventDefault();
            $('.sirv-drop-wrapper').show();
        }


        function onDrugOverH(e) {
            e.stopPropagation();
            e.preventDefault();
            $('.sirv-drop-wrapper').hide();
        }


        function onDropH(e) {
            $('.sirv-drop-wrapper').hide();
            e.stopPropagation();
            e.preventDefault();
            let files = e.originalEvent.dataTransfer.files;

            modernUploadImages(files);
        }

        /* $(document).on('dragenter dragover', function (e) {
            e.stopPropagation();
            e.preventDefault();
        });

        $(document).on('drop', function (e) {
            e.stopPropagation();
            e.preventDefault();
        }); */


        function bindDrugEvents(bind=true){
            if(bind){
                $("#drag-upload-area").on('dragenter dragover', onDrugEnterV);
                $('.sirv-drop-handler').on('dragenter dragover', onDrugEnterH);
                $('.sirv-drop-handler').on('dragleave', onDrugOverH);
                $('.sirv-drop-handler').on('drop', onDropH);
            }else{
                $("#drag-upload-area").off('dragenter dragover', onDrugEnterV);
                $('.sirv-drop-handler').off('dragenter dragover', onDrugEnterH);
                $('.sirv-drop-handler').off('dragleave', onDrugOverH);
                $('.sirv-drop-handler').off('drop', onDropH);
            }
        }


        $('.sirv-items-container').on('scroll', loadOnScroll);

        function loadOnScroll() {
            if ((this.scrollHeight - $(this).scrollTop() - $(this).offset().top - $(this).height()) <= 0) {
                unbindEvents();
                renderView();
                restoreSelections(false);
                bindEvents();
            }
        }


        function toolbarFixed() {
            let $toolbar = $('.toolbar-container');
            let $itemContainer = $('.sirv-items-container');

            if ($(this).scrollTop() > $toolbar.height()) {
                if(!$toolbar.hasClass('sub-toolbar--fixed')){
                    $toolbar.addClass('sub-toolbar--fixed');
                    $itemContainer.addClass('items-container-toolbar--fixed');
                }
            } else {
                $toolbar.removeClass('sub-toolbar--fixed');
                $itemContainer.removeClass('items-container-toolbar--fixed');
            }

        }


        //$('.sirv-search').on('scroll', searchLoadOnScroll);

        function searchLoadOnScroll(){
            if ((this.scrollHeight - $(this).scrollTop() - $(this).offset().top - $(this).height()) <= 0) {
                globalSearch(searchFrom, true);
                $('.sirv-items-container').off('scroll', searchLoadOnScroll);
            }
        }



        function manageContent(dt){
            let data = $.extend(true, {}, dt);
            let stack = [];
            let commonData = {
                sirv_url: data.sirv_url,
                current_dir : data.current_dir,
                continuation: data.continuation,
                fullImgLen: data.content.images.length
            };
            let dataObj = {
                orders: ['dirs', 'spins', 'images', 'videos'],
                dirs: {
                    len: data.content.dirs.length,
                    data: data.content.dirs,
                    func: renderDirs
                },
                spins: {
                    len: data.content.spins.length,
                    data: data.content.spins,
                    func: renderSpins
                },
                images: {
                    len: data.content.images.length,
                    data: data.content.images,
                    func: renderImages
                },
                videos: {
                    len: data.content.videos.length,
                    data: data.content.videos,
                    func: renderVideos
                }
            };

            let dataLen = dataObj.dirs.len + dataObj.spins.len + dataObj.images.len + dataObj.videos.len;
            let stackItemsCount = dataLen > 0 ? Math.ceil(dataLen / scrollSegmentLen) : 0;

            function getDataSplice(data, count) {
                return data.splice(0, count);
            }

            if(stackItemsCount > 0){
                for(let i = 1; i <= stackItemsCount; i++){
                    let count = scrollSegmentLen;
                    let item = [];
                    for(cItem of dataObj.orders){
                        let cItemLen = dataObj[cItem]['len'];
                        if(cItemLen > 0){
                            let rest = count - cItemLen >= 0 ? 0 : Math.abs(count - cItemLen);
                            dataObj[cItem]['len'] = rest;
                            let dataCount = rest > 0 ? 100 : cItemLen;
                            item.push(stackFunc(commonData, getDataSplice(dataObj[cItem]['data'], dataCount), dataObj[cItem]['func']));

                            count = count - cItemLen > 0 ? count - cItemLen : 0;
                            if(count == 0) break;
                        }else continue;

                    }
                    stack.push(item);
                }
            }
            return stack;
        }


        function getStackItem(){
            let stackItem = [];

            if (scrollStack.length > 0) stackItem = scrollStack.shift();

            return stackItem;
        }


        function stackFunc(commonData, data, funcName){
            return function(){
                funcName(commonData, data);
            }
        }


        function isEmptyContentData(data){
            let dirsLen = data.content.dirs.length;
            let spinsLen = data.content.spins.length;
            let imagesLen = data.content.images.length;
            let videosLen = data.content.videos.length;
            if((dirsLen + spinsLen + imagesLen + videosLen) == 0) return true;
            return false;
        }


        function renderEmptyFolder(){
            let html = '';
            if(noResults){
                html = $('.sirv-images').append('<div class="sirv-empty-dir"><span class="sirv-empty-folder-txt" style="font-size: 20px;">No results</span></div>');
            }else{
                html = $('<div class="sirv-empty-dir">' +
                            '<h2>This folder is empty</h2>'+
                            '<div><i class="fa fa-cloud-upload" aria-hidden="true" style="font-size: 56px;"></i></div>'+
                            '<span class="sirv-empty-folder-txt">Drag and drop images here to upload.</span>'+
                        '</div>');
            }

            hideItemsTitle();
            $('.sirv-empty-folder-container').addClass('sirv-latest-block');
            $('.sirv-empty-folder-container').append(html);
        }


        function getLatestUsesBlock(data){
            let block = '';
            let dataObj = {
                dirs: {len: data.content.dirs.length},
                spins: {len: data.content.spins.length},
                images: {len: data.content.images.length},
                videos: {len: data.content.videos.length}
            };

            for(let item in dataObj){
                if(dataObj[item].len > 0){
                    block = item;
                }
            }
            return block;
        }

        function fixLatestUsesBlock(data){
            let block = getLatestUsesBlock(data);
            let selector = '';
            switch (block) {
                case 'dirs':
                    selector = '.sirv-dirs';
                    break;
                case 'spins':
                    selector = '.sirv-spins';
                    break;
                case 'images':
                    selector = '.sirv-images';
                    break;
                case 'videos':
                    selector = '.sirv-videos';
                    break;
                default:
                    break;
            }
            $('.sirv-latest-block').removeClass('sirv-latest-block');
            if(!!selector) $(selector).addClass('sirv-latest-block');
        }


        function renderView() {
            let renderItem = getStackItem();
            if(renderItem.length > 0){
                for(let i = 0; i < renderItem.length; i++){
                    renderItem[i]();
                }
            }else{
                if(emptyFolder) renderEmptyFolder();
                $('.sirv-items-container').off('scroll', loadOnScroll);
            }

        }


        function clearOnScrollLoadingParams(){
            $('.sirv-items-container').on('scroll', loadOnScroll);
            emptyFolder = false;
            hideItemsTitle();
        }

        function hideItemsTitle(){
            $('.sirv-dirs-title').hide();
            $('.sirv-images-title').hide();
            $('.sirv-spins-title').hide();
            $('.sirv-videos-title').hide();

        }

        function renderDirs(commonData, dirs){
            if (dirs.length > 0){
                $('.sirv-folders-count').html(dirs.length);
                $('.sirv-dirs-title').show();
                let documentFragment = $(document.createDocumentFragment());

                for (let i = 0; i < dirs.length; i++) {
                    let dir = dirs[i];
                    if (!dir.filename.startsWith('.')) {
                        dir.dirname = commonData.current_dir;
                        let dt = getItemData('dir', commonData.sirv_url, dir, 'g_content');
                        let elemBlock = getItemBlock(dt);
                        documentFragment.append(elemBlock);
                    }
                }
                $('#dirs').append(documentFragment);
            } else{
                $('.sirv-folders-count').html(dirs.length);
                $('.sirv-dirs-title').hide();
            }
        }


        function formatDate(date, type='short'){
            let d = new Date(date);
            //let monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
            let monthNamesShort = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

            let formatedDate = monthNamesShort[d.getMonth()] + ' ' + d.getUTCDate() + ', ' + d.getFullYear();
            if(type == 'long'){
                formatedDate += ' ' + d.toLocaleString('en-US', { hour: 'numeric', minute: 'numeric', hour12: true });
            }

            return formatedDate;
        }


        function formatVideoDuration(duration) {
            if (!!duration) return new Date(1000 * duration).toISOString().substr(11, 8);

            return 'No data';
        }


        function renderImages(commonData, images){
            if (images.length > 0){
                $('.sirv-images-count').html(commonData.fullImgLen);
                $('.sirv-images-title').show();
                let documentFragment = $(document.createDocumentFragment());

                for (let i = 0; i< images.length; i++) {
                    let image = images[i];

                    image.dirname = commonData.current_dir;
                    let dt = getItemData('image', commonData.sirv_url, image, 'g_content');
                    let imgElemBlock = getItemBlock(dt);
                    documentFragment.append(imgElemBlock);
                    loadImage(imgElemBlock, getItemParams('image', 128));
                }
                $('#images').append(documentFragment);
            }
        }


        function renderSpins(commonData, spins) {
            if(spins.length > 0){
                $('.sirv-spins-count').html(spins.length);
                $('.sirv-spins-title').show();
                for (let i = 0; i < spins.length; i++) {
                    let spin = spins[i];


                    spin.dirname = commonData.current_dir;
                    let dt = getItemData('spin', commonData.sirv_url, spin, 'g_content');
                    let spinElemBlock = getItemBlock(dt);
                    spinElemBlock.appendTo('#spins');
                    loadImage(spinElemBlock, getItemParams('spin', 128));
                }
            }
        }

        function renderVideos(commonData, videos){
            if (videos.length > 0) {
                $('.sirv-videos-count').html(videos.length);
                $('.sirv-videos-title').show();
                for (let i = 0; i < videos.length; i++) {
                    let video = videos[i];

                    video.dirname = commonData.current_dir;
                    let dt = getItemData('video', commonData.sirv_url, video, 'g_content');

                    let videoElemBlock = getItemBlock(dt);
                    videoElemBlock.appendTo('#videos');
                    loadImage(videoElemBlock, getItemParams('video', 128));
                }
            }
        }


        function renderSearch(data){
            if(data.total > 0){
                $('.sirv-zero-items').hide();
                $('.sirv-search-title').show();
                let documentFragment = $(document.createDocumentFragment());
                $('.sirv-search-total-found').html(data.total);

                for (let hit of data.hits) {
                    let type = getSearchItemType(hit._source);
                    let dt = getItemData(type, data.sirv_url, hit._source, 's_content');
                    let block = getItemBlock(dt, 's_content');
                    documentFragment.append(block);
                    if (type !== 'dir') loadImage(block, getItemParams(type, 128));
                }

                $('#search-items').append(documentFragment);
            }else{
                //empty results
                $('.sirv-search-title').hide();
                $('.sirv-zero-items').show();
            }
        }


        function getItemData(type, sirv_url, data, source){
            let dt = {};
            dt.type = type;
            dt.contentType = data.contentType || '';
            dt.mtime = data.mtime;
            dt.meta = data.meta;

            if (type !== 'dir') dt.size = getFormatedFileSize( data.size * 1 );

            if(source == 's_content'){
                dt.basename = data.basename;
                dt.dirname = data.dirname;
                dt.filename = data.filename;
                dt.imageUrl = encodeURI(sirv_url + data.filename);
                dt.fullImageUrl = 'https://' + dt.imageUrl;
            }else{
                dt.basename = data.filename;
                dt.dirname = data.dirname;
                dt.filename = data.dirname == '/' ? data.dirname + data.filename : data.dirname +'/'+ data.filename;
                dt.imageUrl = encodeURI(sirv_url + dt.filename);
                dt.fullImageUrl = 'https://' + dt.imageUrl;
            }

            return dt;
        }


        function getItemBlock(data, receiveContentType='g_content'){
            let bcImg = "width:128px; height:128px; background-image: url(" + getItemPlaceHolder(data.type) + "); background-position: 50% 50%; background-repeat: no-repeat; background-size: contain;";
            let selectionButton = (data.type != 'dir') ? '<div class="sirv-item-selection dashicons"></div>' : '';
            let menuButton = (data.type != 'dir') ? '<div class="sirv-item-menu-actions dashicons"></div>' : '';
            let title_path = receiveContentType == 's_content' ? 'title="'+ data.filename +'" ' : '';
            let dir = data.dirname == '/' ? data.dirname : data.dirname + '/';
            let itemMeta = getItemMeta(data);

            let sirvItem = $(
                '<div class="sirv-item">' +
                    '<div class="sirv-item-body" '+ title_path +' data-id="'+ md5('//'+ data.imageUrl) +'"data-type="'+ data.type +'" data-dir-link="'+ data.filename +'" data-dir="'+ dir +'" data-content-type="'+ data.contentType +'">' +
                        selectionButton + menuButton +
                        '<div class="sirv-item-icon" style="'+ bcImg +'" data-original="'+ data.fullImageUrl +'"></div>' +
                        '<div class="sirv-item-desc">' +
                            '<div class="sirv-item-name-container sirv-overflow-ellipsis sirv-no-select-text" title="'+ data.basename +'">'+ data.basename + '</div>'+
                            '<div class="sirv-item-meta-container sirv-overflow-ellipsis sirv-no-select-text" title="'+ itemMeta.title +'">' + itemMeta.main +'</div>' +
                        '</div>' +
                    '</div>' +
                '</div>'
            );

            return sirvItem;
        }


        function getItemMeta(data){
            let meta = {title: '', main: ''};

            let shortDate = formatDate(data.mtime);
            let longDate = formatDate(data.mtime, 'long');
            let size = !!data.size ? ' - '+ data.size : '';

            meta.title = longDate + size;

            switch (data.type) {
                case 'dir':
                    meta.main = shortDate;
                    break;
                case 'spin':
                    meta.main = shortDate;
                    break;
                case 'image':
                    if (!!data.meta.width && data.meta.height){
                        meta.main = data.meta.width + ' x ' + data.meta.height;
                    }else{
                        meta.main = "No data";
                    }
                    break;
                case 'video':
                    meta.main = formatVideoDuration(data.meta.duration);
                    break;

                default:
                    break;
            }
            return meta;
        }


        function getItemPlaceHolder(type){
            let placeHolder = '';

            switch (type) {
                case 'dir':
                    placeHolder = sirv_ajax_object.assets_path + '/folder.svg';
                    break;
                case 'spin':
                    placeHolder = sirv_ajax_object.assets_path + '/spin-plhldr.svg';
                    break;
                case 'image':
                    placeHolder = sirv_ajax_object.assets_path + '/img-plhldr.svg';
                    break;
                case 'video':
                    placeHolder = sirv_ajax_object.assets_path + '/video-plhldr.svg';
                    break;

                default:
                    break;
            }

            return placeHolder;
        }


        function getItemParams(type, size, delimiter='?'){
            let isRetina = window.devicePixelRatio > 1 ? true : false;
            let params = '';
            let dSize = size * 2;
            switch (type) {
                case 'dir':
                    break;
                case 'spin':
                    params = isRetina == true
                        ? delimiter + 'image&w='+ dSize +'&h='+ dSize +'&canvas.width='+ dSize +'&canvas.height='+ dSize +'&scale.option=fit'
                        : delimiter + 'image&w=' + size + '&h=' + size + '&canvas.width=' + size + '&canvas.height=' + size +'&scale.option=fit';
                    break;
                case 'image':
                    params = isRetina == true
                        ? delimiter + 'w=' + dSize + '&h=' + dSize + '&q=60&scale.option=noup'
                        : delimiter + 'w=' + size + '&h=' + size + '&q=60&scale.option=noup';
                    break;
                case 'video':
                    params = isRetina == true ? delimiter + 'thumbnail=' + size + '&q=60&scale.option=noup' : delimiter + 'thumbnail=' + size + '&q=60&scale.option=noup';
                    break;

                default:
                    break;
            }

            return params;
        }


        function getSearchItemType(item){
            let type = '';
            if(!!item.isDirectory) type = 'dir';
            if(item.extension == '.spin') type = 'spin';
            if(!!item.contentType && item.contentType.match(/image\/.*/ig)) type = 'image';
            if(!!item.contentType && item.contentType.match(/video\/.*/ig)) type = 'video';

            return type;
        }


        function showSearchResults(show, continuosSearch=false){
            if(show){
                if(!$('.sirv-search').hasClass('sirv-search-v')){
                    $('.sirv-search').addClass('sirv-search-v sirv-latest-block');
                    $('.sirv-empty-folder-container').hide();
                    $('.sirv-dirs').hide();
                    $('.sirv-images').hide();
                    $('.sirv-spins').hide();
                    $('.sirv-videos').hide();
                    $('.breadcrumb').hide();
                }else{
                    if(!continuosSearch){
                        unbindEvents();
                        $('#search-items').empty();
                        //hack to show results at top if use search few times
                        $('.sirv-items-container').scrollTop(0);


                        //bindEvents();
                    }
                }
            }else{
                unbindEvents();
                $('#search-items').empty();
                //bindEvents();
                $('.sirv-search').removeClass('sirv-search-v sirv-latest-block');
                $('.sirv-empty-folder-container').show();
                $('.sirv-dirs').show();
                $('.sirv-images').show();
                $('.sirv-spins').show();
                $('.sirv-videos').show();
                $('.breadcrumb').show();
                $('.sirv-search-for').hide();
                $('.sirv-search-for').empty();
            }
        }


        function alphanumCase(a, b) {
            function chunkify(t) {
            let tz = new Array();
            let x = 0, y = -1, n = 0, i, j;

            while (i = (j = t.charAt(x++)).charCodeAt(0)) {
                let m = (i == 46 || (i >=48 && i <= 57));
                if (m !== n) {
                tz[++y] = "";
                n = m;
                }
                tz[y] += j;
            }
            return tz;
            }

            let aa = chunkify(a.toLowerCase());
            let bb = chunkify(b.toLowerCase());

            for (x = 0; aa[x] && bb[x]; x++) {
            if (aa[x] !== bb[x]) {
                let c = Number(aa[x]), d = Number(bb[x]);
                if (c == aa[x] && d == bb[x]) {
                return c - d;
                } else return (aa[x] > bb[x]) ? 1 : -1;
            }
            }
            return aa.length - bb.length;
        }


        function loadImage(elem, imgParams) {
            let $imgElem = $('.sirv-item-icon', elem);
            let src = $imgElem.attr('data-original');

            src = src.replace('(', '%28')
                .replace(')', '%29')
                .replace('#', '%23')
                .replace('?', '%3F')
                .replace("'", '%27');

            let newImg = new Image();
            let attemptsToLoadImg = 2;

            function load(imgElem, newImage, src) {
                newImage.onload = function () {
                    imgElem.css('background-image', 'url(' + newImage.src + ')');
                }

                newImage.src = src;

                newImage.onerror = function () {
                    if (attemptsToLoadImg > 0) {
                        setTimeout(function () { load($imgElem, newImage, src + imgParams); }, 2000);
                        attemptsToLoadImg--;
                    }
                }
            }

            load($imgElem, newImg, src + imgParams);
        }


        function eraseView(){

            unbindEvents();
            $('#dirs').empty();
            $('#images').empty();
            $('.sirv-empty-dir').remove();
            $('#spins').empty();
            $('#videos').empty();
            $('.breadcrumb').empty();
            $('.sirv-folders-title, .sirv-spins-title, .sirv-images-title, .sirv-videos-title').hide();
        }


        function renderBreadcrambs(currentDir){
            if(currentDir != "/"){
                $('<li><span class="breadcrumb-text">You are here: </span><a href="#" class="sirv-breadcramb-link" data-dir-link="/">Home</a></li>').appendTo('.breadcrumb');
                let dirs = currentDir.split('/').slice(1);
                let temp_dir = "";
                for(let i=0; i < dirs.length; i++){
                    temp_dir += "/" + dirs[i];
                    if(i+1 == dirs.length){
                        $('<li><span>' + dirs[i] + '</span></li>').appendTo('.breadcrumb');
                    }else{
                        $('<li><a href="#" class="sirv-breadcramb-link" data-dir-link="' + temp_dir + '">' + dirs[i] + '</a></li>').appendTo('.breadcrumb');
                    }

                }
            }else{
                $('<li><span class="breadcrumb-text">You are here: </span>Home</li>').appendTo('.breadcrumb')
            }
        }


        function setCurrentDir(currentDir){
            let cDir = currentDir == '/' ? currentDir : currentDir.substr(1) + '/';
            $('#filesToUpload').attr('data-current-folder', cDir);
            $('.sirv-drop-to-folder').text(currentDir);
        }


        function searchOnEnter(e){
            if(e.keyCode == 13)
            {
                globalSearch();
            }
        }


        function wideSearchField(){
            $(this).removeClass('sirv-search-narrow').addClass('sirv-search-wide');
            $('.sirv-search-cancel').removeClass('narrow').addClass('wide');
        }


        function narrowSearchField(){
            if($(this).val() == ""){
                $(this).removeClass('sirv-search-wide').addClass('sirv-search-narrow');
                $('.sirv-search-cancel').removeClass('wide').addClass('narrow');
            }
        }


        function globalSearch(from = 0, continuosSearch=false){
            let query = $('#sirv-search-field').val();
            //from = from || 0;

            if(!!!query) return;

            let ajaxData = {
                url: sirv_ajax_object.ajaxurl,
                data: {
                    action: 'sirv_get_search_data',
                    search_query: query,
                    from: from,
                },
                type: 'POST',
                dataType: 'json',
            }
            //sendAjaxRequest(AjaxData, processingOverlay=false, showingArea=false, isDebug=false, doneFn=false, beforeSendFn=false, errorFn=false)
            sendAjaxRequest(ajaxData, processingOverlay = '.loading-ajax', showingArea = false, isdebug = false, function (data) {
                if(data){
                    $('.sirv-search-for').text("Results for '" + query + "'");
                    if (from == 0) if ($('.sirv-items-container').scrollTop() > 0) $('.sirv-items-container').scrollTop(0);
                    if(data.isContinuation){
                        $('.sirv-items-container').on('scroll', searchLoadOnScroll);
                        searchFrom = data.from;
                    }else{
                        $('.sirv-items-container').off('scroll', searchLoadOnScroll);
                        searchFrom = 0;
                    }

                    console.log(data);
                    unbindEvents();
                    showSearchResults(true, continuosSearch);
                    renderSearch(data);
                    restoreSelections(false);
                    bindEvents();
                    patchMediaBar();
                }
            },
            function(){
                $('.breadcrumb').hide();
                $('.sirv-search-for').show();
                $('.sirv-search-for').text("Searching for '" + query +"'");
            }
            );
        }


        function cancelSearch(){
            $('#sirv-search-field').val('');
            $('#sirv-search-field').removeClass('sirv-search-wide').addClass('sirv-search-narrow');
            $('.sirv-search-cancel').removeClass('wide').addClass('narrow');
            showSearchResults(false);
            restoreSelections(false);
            bindEvents();
        }


        function rightClickContextMenu(e) {
            e.stopPropagation();
            e.preventDefault();

            deactivateActionMenu();

            if(!!$(this).attr('data-type')){
                let type = $(this).attr('data-type');
                renderActionMenu(e, type, $(this));
            }else{
                renderActionMenu(e, 'global', $(this));
            }
        }


        function clickActionMenu(e){
            e.preventDefault();
            e.stopPropagation();

            let $item = $(this).parent();
            let type = $item.attr('data-type');

            renderActionMenu(e, type, $item);
        }

        function renderActionMenu(e, type, $item){
            let $menu = $('.sirv-dropdown');
            let top = parseInt(e.pageY);
            let left = parseInt(e.pageX);


            if (!!$item.attr('data-type')) {
                let dataOrig = $('.sirv-item-icon', $item).attr('data-original');
                let dataDir = $item.attr('data-dir');
                let dirLink = $item.attr('data-dir-link');
                let delLink = dataDir + basename(dataOrig);

                dataOrig = dataOrig.replace('#', '%23').replace('?', '%3F');

                $menu.attr('data-original', dataOrig);
                $menu.attr('data-delete-link', delLink);
                $menu.attr('data-dir-link', dirLink);
                $menu.attr('data-type', type);
            }

            let items = [
                { id: 'newfolder', class: 'sirv-menu-item-new-folder', icon: "fa fa-plus", group: 1, type: ["global"], text: "New folder"},
                { id:'opentab', class: 'sirv-menu-item-open-new-tab', icon: "fa fa-external-link", group: 1, type: ['image', 'video', 'spin'], text: "Open in new tab"},
                { id: 'copylink', class: 'sirv-menu-item-copy-link', icon: "fa fa-clipboard", group: 1, type: ['image', 'video', 'spin'], text: "Copy link"},
                { id: 'upload', class: 'sirv-menu-item-upload-files', icon: "fa fa-upload", group: 2, type: ["global"], text: "Upload files"},
                { id: 'duplicate', class: 'sirv-menu-item-duplicate', icon: "fa fa-copy", group: 2, type: ['image', 'video', 'spin'], text: "Duplicate"},
                { id: 'rename', class: 'sirv-menu-item-rename', icon: "fa fa-pencil", group: 2, type: ['image', 'video', 'spin'], text: "Rename"},
                { id: 'delete', class: 'sirv-menu-item-delete', icon: "fa fa-trash-o", group: 2, type: ['image', 'video', 'spin', 'dir'], text: "Delete"},
                { id: 'download', class: 'sirv-menu-item-download', icon: "fa fa-download", group: 3, type: ['image', 'video', 'spin'], text: "Download"},
            ];

            let divider = '<div class="sirv-dropdown-divider"></div>';
            let documentFragment = $(document.createDocumentFragment());
            let group = 0;

            for(let item of items){
                if ($.inArray(type, item.type) !== -1){
                    if(group === 0) group = item.group;
                    if(item.group !== group){
                        group = item.group;
                        documentFragment.append(divider);
                    }

                    let menuItem = $('<a class="sirv-dropdown-item '+ item.class +'" href="#">\n' +
                                        '<i class= "'+ item.icon +'"></i>\n'+
                                        '<span>'+ item.text +'</span>\n'+
                                    '</a>\n'
                    );
                documentFragment.append(menuItem);
                }else continue;
            }

            if(documentFragment.children().length > 0){
                $menu.empty().append(documentFragment);
                bindActionMenuEvents();
                $menu.addClass('sirv-menu--active');
                $menu.css({ 'display': 'block' });
                let offset = calcElementOffset(e, $menu);
                $menu.css({ 'top': offset.top, 'left': offset.left });
            }

        }


        function calcElementOffset(e, elem){
            let cTop = parseInt(e.clientY);
            let cLeft = parseInt(e.clientX);
            let wHeigth = window.innerHeight;
            let wWidth = window.innerWidth;
            let elemBounds = elem[0].getBoundingClientRect();
            let cBottom = cTop + Math.ceil(elemBounds.height);
            let cRight = cLeft + Math.ceil(elemBounds.width);

            let top = cBottom > wHeigth ? cTop - ((cBottom - wHeigth) + 2) : cTop;
            let left = cRight > wWidth ? cLeft - ((cRight - wWidth) + 2) : cLeft;

            return {top: top, left: left};
        }


        $(document).on('click', deactivateActionMenu);
        function deactivateActionMenu(isClearParams=true){
            let $menu = $('.sirv-dropdown');

            unBindActionMenuEvents();

            $menu.empty();
            if (isClearParams) clearMenuData($menu);

            $menu.removeClass('sirv-menu--active');
            $menu.css({ 'display': 'none'});
        }


        function menuCopyItemLink(e){
            e.preventDefault();
            e.stopPropagation();

            let $menu = $('.sirv-dropdown');
            let fName = basename($menu.attr('data-original'));

            copyToClipboard($menu.attr('data-original'));
            deactivateActionMenu();
            alert("a link to '" + decodeURI(fName) + "' has been copied to the clipboard");
        }

        function menuDeleteItem(e){
            e.preventDefault();
            e.stopPropagation();

            let isClearParams = true;

            let $menu = $('.sirv-dropdown');
            let type = $menu.attr('data-type');
            let delLink = type == 'dir' ? $menu.attr('data-delete-link') + '/' : $menu.attr('data-delete-link');

            delLink = encodeItemPath(delLink);
            //console.log(delLink);

            if(type == 'dir'){
                let dirLink = $menu.attr('data-dir-link');
                getContentFromSirv(dirLink, false, deleteEmptyFolder);
                isClearParams = false;
            }else deleteSelectedImages(delLink);

            deactivateActionMenu(isClearParams);

        }


        function deleteEmptyFolder(data){
            if (isEmptyContentData(data)){
                let $menu = $('.sirv-dropdown');
                let delLink = $menu.attr('data-delete-link') + '/';

                delLink = encodeItemPath(delLink);

                deleteSelectedImages(delLink);
            } else{
                alert('Folder cannot be deleted because it contains files. Delete folder contents first.');
            }
        }


        function menuDownloadItem(e){
            e.preventDefault();
            e.stopPropagation();

            let $menu = $('.sirv-dropdown');
            let dataOrig = $menu.attr('data-original');
            let fName = basename(dataOrig);


            let a = document.createElement('a');
            a.setAttribute('href', dataOrig + '?dl&format=original&quality=0');
            a.setAttribute('download', fName);

            a.style.display = 'none';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);

            deactivateActionMenu();
        }


        function menuOpenInNewTab(e){
            e.preventDefault();
            e.stopPropagation();


            let $menu = $('.sirv-dropdown');
            let dataOrigUrl = $menu.attr('data-original');

            window.open(dataOrigUrl, '_blank');

            deactivateActionMenu();
        }


        function menuNewFolder(e){
            e.preventDefault();
            e.stopPropagation();

            createFolder();

            deactivateActionMenu();
        }


        function menuUploadFiles(e){
            e.preventDefault();
            e.stopPropagation();

            $('.sirvFilesToUpload').trigger('click');

            deactivateActionMenu();

        }


        function menuDuplicateFile(e){
            e.preventDefault();
            e.stopPropagation();

            let $menu = $('.sirv-dropdown');
            let filePath = $menu.attr('data-delete-link');
            let type = $menu.attr('data-type');

            let basePath = basepath(filePath);
            let ext = getExt(filePath);
            let baseNameWithoutExt = decodeURI(basenameWithoutExt(filePath));
            let searchPattern = new RegExp(baseNameWithoutExt +"\\s\\(copy(?:\\s\\d)*?\\)\\." + ext, 'i');

            let countCopies = searchFileCopies(type, searchPattern);

            let copyNum = countCopies > 0 ? ' ' + (countCopies) : '';
            let copyPattern = ' (copy'+ copyNum +').';
            let copyPath = encodeURI(basePath + baseNameWithoutExt + copyPattern + ext);

            duplicateFile(filePath, copyPath);

            deactivateActionMenu();
        }


        function menuRenameFile(e){
            e.preventDefault();
            e.stopPropagation();

            let $menu = $('.sirv-dropdown');
            let newFileName = encodeURI(window.prompt("Enter new file name:", ""));


            if (!!newFileName) {
                let filePath = $menu.attr('data-delete-link');
                filePath = encodeURI(basepath(filePath)) + basename(filePath);
                let basePath = basepath(filePath);
                let ext = getExt(filePath);
                let newFilePath = basePath + newFileName + '.' + ext;

                renameFile(filePath, newFilePath);
            }

            deactivateActionMenu();
        }


        function copyToClipboard(text) {
            var dummy = document.createElement("textarea");
            document.body.appendChild(dummy);
            dummy.value = text;
            dummy.select();
            document.execCommand("copy");
            document.body.removeChild(dummy);
        }


        function duplicateFile(filePath, copyFilePath){
            let ajaxData = {
                url: sirv_ajax_object.ajaxurl,
                data: {
                    action: 'sirv_copy_file',
                    filePath: filePath,
                    copyPath: copyFilePath,
                },
                type: 'POST',
                dataType: 'json',
            }
            //sendAjaxRequest(AjaxData, processingOverlay=false, showingArea=false, isDebug=false, doneFn=false, beforeSendFn=false, errorFn=false)
            sendAjaxRequest(ajaxData, processingOverlay = '.loading-ajax', showingArea = false, isdebug = false, function (data) {
                if(!!data){
                    if(data.duplicated){
                        getContentFromSirv(window.sirvGetPath);
                    }else{
                        //console.log('file was not duplicated');
                    }
                }
            });
        }


        function renameFile(filename, newFilename) {
                let ajaxData = {
                    url: sirv_ajax_object.ajaxurl,
                    data: {
                        action: 'sirv_rename_file',
                        filePath: filename,
                        newFilePath: newFilename
                    },
                    type: 'POST',
                    dataType: 'json',
                }
                sendAjaxRequest(ajaxData, processingOverlay = '.loading-ajax', showingArea = false, isdebug = false, function (data) {
                    if(!!data){
                        if(data.renamed){
                            getContentFromSirv(window.sirvGetPath);
                        }else{
                            console.log('File was not renamed');
                        }
                    }

                });
        }


        function searchFileCopies(type, searchPattern){
            let types = {image: 'images', spin: 'spins', video: 'videos'};

            items = contentData.content[types[type]];
            let countMatches = 0;

            for(item of items){
                if(item.filename.match(searchPattern)) countMatches++;
            }

            return countMatches;

        }


        function decodeItemPath(itemPath){

        }


        function encodeItemPath(itemPath){
            return encodeURIComponent(decodeURI(itemPath)).replace(/%2F/gi, '/');
        }


        function clearMenuData($menu){
            $menu.attr('data-original', '');
            $menu.attr('data-delete-link', '');
            $menu.attr('data-dir', '');
            $menu.attr('data-dir-link', '');
        }


        function bindActionMenuEvents(){
            $('.sirv-menu-item-copy-link').on('click', menuCopyItemLink);
            $('.sirv-menu-item-delete').on('click', menuDeleteItem);
            $('.sirv-menu-item-download').on('click', menuDownloadItem);
            $('.sirv-menu-item-open-new-tab').on('click', menuOpenInNewTab);
            $('.sirv-menu-item-new-folder').on('click', menuNewFolder);
            $('.sirv-menu-item-upload-files').on('click', menuUploadFiles);
            $('.sirv-menu-item-duplicate').on('click', menuDuplicateFile);
            $('.sirv-menu-item-rename').on('click', menuRenameFile);
        }


        function unBindActionMenuEvents() {
            $('.sirv-menu-item-copy-link').off('click', menuCopyItemLink);
            $('.sirv-menu-item-delete').off('click', menuDeleteItem);
            $('.sirv-menu-item-download').off('click', menuDownloadItem);
            $('.sirv-menu-item-open-new-tab').off('click', menuOpenInNewTab);
            $('.sirv-menu-item-new-folder').off('click', menuNewFolder);
            $('.sirv-menu-item-upload-files').off('click', menuUploadFiles);
            $('.sirv-menu-item-duplicate').off('click', menuDuplicateFile);
            $('.sirv-menu-item-rename').off('click', menuRenameFile);
        }


        function bindEvents(){
            $('.sirv-items-container').on('contextmenu', rightClickContextMenu);
            $('.sirv-item-menu-actions').on('click', clickActionMenu);
            $('.sirv-item-body').on('contextmenu', rightClickContextMenu);
            $('.sirv-items-container').on('scroll', toolbarFixed);
            $('#sirv-search-field').on('focus', wideSearchField);
            $('#sirv-search-field').on("focusout", narrowSearchField);
            $('#sirv-search-field').on('keyup', searchOnEnter);
            $('.sirv-search-cancel').on('click', cancelSearch);
            $('.sirv-breadcramb-link').on('click', beforeGetContent);
            $('.sirv-item-body[data-type=dir]').on('click', beforeGetContent);
            $('.sirv-item-body:not([data-type=dir])').on('click', function(event){selectImagesNew(event, $(this))});
            $('.insert').on('click', insert);
            $('.sirv-create-gallery').on('click', createGallery);
            $('.clear-selection').on('click', clearSelection);
            $('.delete-selected-images').on('click', function(){deleteSelectedImages('')});
            $('.create-folder').on('click', createFolder);
            $('.sirvFilesToUpload').on('change', function(event){modernUploadImages(event.target.files);});
            $('.sirv-gallery-type').on('change', manageOptionsStates);
            $('#gallery-thumbs-position').on('change', manageThumbPosition);
            $('.set-featured-image').on('click', setFeaturedImage);
            $('.sirv-woo-add-images').on('click', addWooSirvImages);
            $('.nav-tab-wrapper > a').on('click', function(e){changeTab(e, $(this));});
            $('input[id=gallery-width]').on('input', onChangeWidthInputRI);

            bindDrugEvents(true);
            //bindActionMenuEvents();
        };


        function unbindEvents(){
            $('.sirv-items-container').off('contextmenu', rightClickContextMenu);
            $('.sirv-item-body').off('contextmenu', rightClickContextMenu);
            $('.sirv-item-menu-actions').off('click', clickActionMenu);
            $('.sirv-items-container').off('scroll', toolbarFixed);
            $('#sirv-search-field').off('focus', wideSearchField);
            $('#sirv-search-field').off("focusout", narrowSearchField);
            $('#sirv-search-field').off('keyup', searchOnEnter);
            $('.sirv-search-cancel').off('click', cancelSearch);
            $('.insert').off('click', insert);
            $('.sirv-create-gallery').off('click', createGallery);
            $('.sirv-breadcramb-link').off('click', beforeGetContent);
            $('.sirv-item-body[data-type=dir]').off('click', beforeGetContent);
            $('.sirv-item-body:not([data-type=dir])').off('click');
            $('.clear-selection').off('click');
            $('.delete-selected-images').off('click');
            $('.create-folder').off('click');
            $('#filesToUpload').off('change');
            $('#gallery-flag').off('click');
            $('#gallery-zoom-flag').off('click');
            $('.sirv-gallery-type').off('change');
            $('.set-featured-image').off('click');
            $('.sirv-woo-add-images').off('click', addWooSirvImages);
            $('input[id=gallery-width]').off('input');

            bindDrugEvents(false);
            //unBindActionMenuEvents();
        }


        window.sirvGetPath = function(){
            if(window.sirvViewerPath){
                return window.sirvViewerPath;
            }
            return '/';
        }


        function beforeGetContent() {
            let dataLink = $(this).attr('data-dir-link');
            window.sirvViewerPath = dataLink;
            getContentFromSirv(dataLink);
        }


        window.getContentFromSirv = function(path, isRender=true, unRenderFunc=false, continuation=''){
            path = ( !!path ) ? path : '/';

            //clean searh field on update content
            if($('#sirv-search-field').val() !== ''){
                $('#sirv-search-field').val('');
                $('#sirv-search-field').removeClass('sirv-search-wide').addClass('sirv-search-narrow');
            }

            let ajaxData = {
                            url: sirv_ajax_object.ajaxurl,
                            data: {
                                    action: 'sirv_get_content',
                                    path: path,
                                    continuation: continuation,
                            },
                            type: 'POST',
                            dataType: 'json',
            }

            $('.sirv-empty-dir').remove();

            sendAjaxRequest(ajaxData, processingOverlay='.loading-ajax', showingArea=false, isdebug=false, function(data){
                if(data){
                    if(isRender){
                        clearOnScrollLoadingParams();
                        contentData = data;
                        scrollStack = manageContent(data);
                        emptyFolder = isEmptyContentData(data);
                        fixLatestUsesBlock(data);


                        eraseView();
                        showSearchResults(false);
                        renderBreadcrambs(data.current_dir);
                        setCurrentDir(data.current_dir);
                        renderView();
                        restoreSelections(false);
                        bindEvents();
                        patchMediaBar();
                    }else{
                        unRenderFunc(data);
                    }

                }
            });

        }


        function patchMediaBar(){

            if($('#chrome_fix', top.document).length <= 0){
                $('head', top.document).append($('<style id="chrome_fix">.media-frame.hide-toolbar .media-frame-toolbar {display: none;}</style>'));
            }
        }


        //create folder
        function createFolder(){
            let newFolderName = window.prompt("Enter folder name:", "");

            if (!!newFolderName) {

                let data = {
                            action:  'sirv_add_folder',
                            current_dir:  $('#filesToUpload').attr('data-current-folder'),
                            new_dir:  encodeURI(newFolderName)
                }
                let ajaxData = {
                                url: sirv_ajax_object.ajaxurl,
                                type: 'POST',
                                data: data
                }
                sendAjaxRequest(ajaxData, processingOverlay='.loading-ajax', showingArea=false, isdebug=false, function(response){
                    getContentFromSirv(window.sirvGetPath);
                });
            }
        }

        function filesSumSize(files){
            let sumSize = 0;

            $.each(files, function(index, value){
                sumSize += value.size;
            });

            return sumSize;
        }


        //upload images
        let uploadTimer;
        window['uploadImages'] = function(files){

            //filesSumSize(files);

            let currentDir = $('#filesToUpload').attr('data-current-folder');
            //let files = event.target.files;
            let data = new FormData();

            data.append('action', 'sirv_upload_files');
            data.append('currentDir', currentDir);

            $.each(files, function(key, value)
            {
                data.append(key, value);
            });

            let ajaxData = {
                            url: sirv_ajax_object.ajaxurl,
                            type: 'POST',
                            contentType: false,
                            processData: false,
                            data: data
            }
            sendAjaxRequest(ajaxData, processingOverlay='.sirv-upload-ajax', showingArea=false, isdebug=false,
                doneFn=function(response){
                    //clear list of files
                    let input = $("#filesToUpload");
                    input.replaceWith(input.val('').clone(true));

                    getContentFromSirv(window.sirvGetPath);
                },
                beforeFn=function(){
                    uploadTimer = window.setInterval(getUploadingStatus, 2500);
                },
                errorFn=function(jqXHR, status, error){
                    window.clearInterval(uploadTimer);
                });
        }


        window['modernUploadImages'] = function(files){
            let groupedImages = groupedFiles(files, maxFileSize, maxFilesCount, sirvFileSizeLimit);
            let countFiles = files.length;

            //let currentDir = $('#filesToUpload').attr('data-current-folder');
            let currentDir = encodeURI($('#filesToUpload').attr('data-current-folder'));

            //clear progress bar data before start new upload
            $('.sirv-progress-bar').css('width', '0');
            $('.sirv-progress-text').html('');
            $('.sirv-progress-text').html('<span class="sirv-traffic-loading-ico sirv-no-lmargin"></span>processing...');

            //let files = event.target.files;

            //clear list of files
            let input = $("#filesToUpload");
            input.replaceWith(input.val('').clone(true));

            if(countFiles > 0){
                $('.sirv-upload-ajax').show();
                uploadTimer = window.setInterval(getUploadingStatus, 2500);

                uploadByPart(groupedImages, currentDir, countFiles);
                $('.sirv-empty-dir').remove();
            }

        }


        function uploadByPart(groupedImages, currentDir, countFiles){
            if(groupedImages['partArray'].length !== 0){
                let imagePart = groupedImages['partArray'].shift();
                let data = new FormData();

                data.append('action', 'sirv_upload_files');
                data.append('current_dir', currentDir);
                data.append('totalFiles', countFiles);

                $.each(imagePart, function(key, value){
                    data.append(key, value);
                });

                let ajaxData = {
                                url: sirv_ajax_object.ajaxurl,
                                type: 'POST',
                                contentType: false,
                                processData: false,
                                data: data
                }
                sendAjaxRequest(ajaxData, processingOverlay=false, showingArea=false, isdebug=false,
                    doneFn=function(response){
                        uploadByPart(groupedImages, currentDir, countFiles);
                    },
                    beforeFn=false,
                    errorFn=function(jqXHR, status, error){
                        //window.clearInterval(uploadTimer);
                    });
            }else{
                if(groupedImages['overSizedImages'].length !== 0){
                    uploadImagesByChunk(groupedImages['overSizedImages'], currentDir, countFiles);
                }else{
                    $('.sirv-upload-ajax').hide();
                    //getContentFromSirv(decodeURI(currentDir));
                    getContentFromSirv(window.sirvGetPath);
                }
            }
        }


        function uploadImagesByChunk(overSizedImages, currentDir){
            let totalOverSizedFiles = overSizedImages.length;
            for(let index = 0; index < totalOverSizedFiles; index++){
                let file = overSizedImages[index];
                let reader = new FileReader();
                uploadImageByChunk(file, 0, reader, 1, totalOverSizedFiles, currentDir);
            }
        }


        function uploadImageByChunk(file, start, reader, partNum, totalOverSizedFiles, currentDir){
            let maxSliceSize = 3 * 1024 * 1024;
            let sliceSize = maxSliceSize > maxFileSize ? maxFileSize : maxSliceSize;
            let nextSlice = start + sliceSize + 1;
            let blob = file.slice(start, nextSlice);

            let totalSlices = countSlices(file.size, sliceSize);

            reader.onloadend = function( event ) {
                if ( event.target.readyState !== FileReader.DONE ) {
                    return;
                }
                let data = new FormData();
                data.append('action', 'sirv_upload_file_by_chanks');
                data.append('partFileName', file.name);
                data.append('totalParts', totalSlices);
                data.append('totalFiles', totalOverSizedFiles);
                data.append('partNum', partNum);
                data.append('currentDir', currentDir);
                data.append('binPart', event.target.result);


                let ajaxData = {
                                url: sirv_ajax_object.ajaxurl,
                                type: 'POST',
                                contentType: false,
                                processData: false,
                                data: data
                }

                sendAjaxRequest(ajaxData, processingOverlay=false, showingArea=false, isdebug=false,
                    doneFn=function(response){
                        if ( nextSlice < file.size ) {
                            uploadImageByChunk(file, nextSlice, reader, partNum + 1, totalOverSizedFiles, currentDir);
                        }

                        if(response){
                            try {
                                //response = response.substr(-1,1) == '0' ? response.substring(0, response.length-1) : response;
                                let json_obj = JSON.parse(response);
                                if(json_obj.hasOwnProperty('stop') && json_obj.stop == true){
                                    $('.sirv-upload-ajax').hide();
                                    //getContentFromSirv(decodeURI(currentDir));
                                    getContentFromSirv(window.sirvGetPath);
                                }
                            } catch(e) {
                                $('.sirv-upload-ajax').hide();
                                //getContentFromSirv(decodeURI(currentDir));
                                getContentFromSirv(window.sirvGetPath);
                                //console.log(e);
                            }
                        }
                    });
            };

            reader.readAsDataURL( blob );
        }


        function countSlices(fileSize, sliceSize){
            let nextSlice = 0;
            let count = 0;
            while(nextSlice < fileSize){
                count += 1;
                nextSlice += sliceSize + 1;
            }

            return count;
        }


        function groupedFiles(files, maxFileSize, maxFiles, sirvFileSizeLimit){
            let partArray = [];
            let overSizedImages = [];
            let sumFileSize = 0;
            let filesCount = 0;
            let part = 0;

            partArray.push([]);

            for(let i = 0; i<files.length; i++){
                let file = files[i];
                sumFileSize += file.size;
                filesCount += 1;
                if((sumFileSize >= maxFileSize && filesCount > maxFiles) || filesCount > maxFiles || sumFileSize >= maxFileSize){
                    if (file.size < maxFileSize){
                        sumFileSize = file.size;
                        filesCount = 1;
                        part += 1;
                        partArray.push([]);
                    }else{
                        overSizedImages.push(file);
                        continue;
                    }
                }

                partArray[part].push(file);
            }

            partArray = removeEmptyArrItems(partArray);

            return {partArray: partArray, overSizedImages: overSizedImages};
        }


        function removeEmptyArrItems(dataArr){
            for(let index=0; index<dataArr.length; index++){
                if (dataArr[index].length === 0) dataArr.splice(index, 1);
            }

            return dataArr;
        }


        //FirstImageUploadDelay - delay before first image will be uploaded. Need if loading big image and getUploadingStatus() will not get status info during uploading first image
        let FirstImageUploadDelay = 50;
        function getUploadingStatus(){
            let data = {
                        action: 'sirv_get_image_uploading_status',
                        sirv_get_image_uploading_status: true
            }
            let ajaxData = {
                            url: sirv_ajax_object.ajaxurl,
                            type: 'POST',
                            data: data
            }
            sendAjaxRequest(ajaxData, processingOverlay=false, showingArea=false, isdebug=false,
                doneFn=function(response){
                let json_obj = JSON.parse(response);
                if(json_obj.processedImage!== null || json_obj.count !== null){
                    $('.sirv-progress-bar').css('width', json_obj.percent + '%');
                    $('.sirv-progress-text').html(json_obj.percent + '%' + ' ('+ json_obj.processedImage +' of '+ json_obj.count +')');

                    if (json_obj.percent == 100) {
                        window.clearInterval(uploadTimer);
                    }
                }else{
                    if(json_obj.isPartFileUploading){
                        $('.sirv-progress-text').html('<span class="sirv-traffic-loading-ico sirv-no-lmargin"></span>processing upload big files by chunks...');
                    }else{
                        //$('.sirv-progress-text').html('processing...');
                        if(FirstImageUploadDelay == 0){
                            window.clearInterval(uploadTimer);
                            FirstImageUploadDelay = 50;
                        }
                        FirstImageUploadDelay--;
                    }
                }
            });
        }


        function searchImages() {
            let querySearch = $('#sirv-search-field').val();
            let data = JSON.parse(JSON.stringify(contentData));

            let isSearchActive = false;
            let currentDir = data.current_dir !== '/' ? data.current_dir : '';


            function searchItems(inSearch, key, querySearch) {
                let searchedItems = [];
                for (let i = 0; i < inSearch.length; i++) {
                    let cleanedName = (inSearch[i][key]).replace(currentDir, '');
                    if ((cleanedName.toLowerCase().indexOf(querySearch.toLowerCase())) !== -1) {
                        //searchedItems.push({ [key]: inSearch[i][key] });
                        searchedItems.push(inSearch[i]);
                    }
                }

                //hack if one item. In usual way contents also consist key with currentDir.
                //if (searchedItems.length > 0 && key == "Key" && isValueExists(inSearch)) searchedItems.push({ [key]: currentDir });

                return searchedItems;
            }


            function isValueExists(object) {
                for (let prop in object) {
                    if (object[prop] == currentDir) return true;
                }

                return false;
            }

            /* if (!querySearch) {

            } */

            if (data.content.images.length > 0) {
                isSearchActive = true;

                data.content.images = searchItems(data.content.images, 'filename', querySearch);
            }

            if (data.content.dirs.length > 0) {
                isSearchActive = true;

                data.content.dirs = searchItems(data.content.dirs, 'filename', querySearch);
            }

            if (data.content.spins.length > 0) {
                isSearchActive = true;

                data.content.spins = searchItems(data.content.spins, 'filename', querySearch);
            }

            if (data.content.videos.length > 0) {
                isSearchActive = true;

                data.content.videos = searchItems(data.content.videos, 'filename', querySearch);
            }

            if (isSearchActive) {
                eraseView();
                renderBreadcrambs(data.current_dir);
                setCurrentDir(data.current_dir);
                scrollStack = manageContent(data);
                noResults = isEmptyContentData(data);
                emptyFolder = isEmptyContentData(data);
                hideItemsTitle();
                renderView();
                restoreSelections(false);
                bindEvents();
                patchMediaBar();
            }
        }


        function basename(path,prefix='/') {
                path = path.split(prefix);

                return path[ path.length - 1 ];
            }


        function basepath(path){
            fileName = basename(path);

            return path.replace(fileName, '');
        }


        function getExt(filePath) {
            return filePath.substr((~-filePath.lastIndexOf(".") >>> 0) + 2);
        }


        function basenameWithoutExt(filePath) {
            let fileName = basename(filePath);
            let ext = '.' + getExt(fileName);

            return fileName.replace(ext, '');
        }


        function encodedFilename(path){
            fileName = basename(path);
            filePath = path.replace(fileName, '');

            return filePath + encodeURIComponent(fileName);
        }


        function escapeXMLChars(path){
            return path.replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&apos;');
        }


        function deleteSelectedImages(file=''){
            let filenamesArray = [];

            if(!!file){
                filenamesArray.push(file);
            }else{
                let selectedImages = $('.selected-miniature-img');
                if (selectedImages.length > 1) {
                    $.each(selectedImages, function (index, value) {
                        //filenamesArray.push(encodeURI($(value).attr('data-dir')) + basename($(value).attr('data-original')));
                        filenamesArray.push( escapeXMLChars($(value).attr('data-dir') + decodeURI(basename($(value).attr('data-original')))) );
                    });
                } else {
                    let value = selectedImages[0];
                    //filenamesArray.push(encodeURI($(value).attr('data-dir')) + basename($(value).attr('data-original')));
                    filenamesArray.push(encodeItemPath($(value).attr('data-dir') + basename($(value).attr('data-original'))));
                }
            }

            let data = {
                        action: 'sirv_delete_files',
                        filenames: filenamesArray
            }
            let ajaxData = {
                            url: ajaxurl,
                            type: 'POST',
                            data: data
            }

            sendAjaxRequest(ajaxData, processingOverlay='.loading-ajax', showingArea=false, isdebug=true, function(response){
                getContentFromSirv(window.sirvGetPath);
                if(!!!file) clearSelection();
            });
        }


        function selectImages(event, $object){

            function addMiniatures(object){
                let dir = $object.attr('data-dir');
                dir = dir.startsWith('/') && dir.length > 1 ? dir.substring(1, dir.length) : dir;
                $('.selected-miniatures-container').append('<li class="selected-miniature"><img class="selected-miniature-img" data-id="'+ object.attr('data-id') +
                    '" data-original="'+ object.attr('data-original') +'" data-type="'+ object.attr('data-type') +
                    '" data-dir="'+ dir +'"'+
                    ' data-caption="" src="' + object.attr('data-original') + getItemParams(object.attr('data-type'), 40) +'"' +' /></li>\n');
            }

            function removeMiniatures(object){
                $($('img[data-id='+ object.attr('data-id')+ ']').closest('li.selected-miniature')).remove();
            }

            let curr = -1;

            if(event.ctrlKey){
                event.preventDefault();
            }

            if(event.shiftKey){
                event.preventDefault();

                curr = $('.sirv-image').index($object);
                if(prev > -1){
                        let miniaturesArray= [];
                        $('.selected-miniature-img').each(function(){
                            miniaturesArray.push($(this).attr('data-id'));
                        });
                    $('.sirv-image').slice(Math.min(prev, curr), 1 + Math.max(prev, curr)).each(function(){
                        if ($.inArray($(this).attr('data-id'), miniaturesArray) == -1){
                            $(this).addClass('selected');
                            $(this).closest('li').addClass('selected');
                            addMiniatures($(this));
                        }
                    });
                }
            }else{
                curr = prev = $('.sirv-image').index($object);

                if($object.hasClass('selected')){
                    $object.removeClass('selected');
                    $object.closest('li').removeClass('selected');
                    removeMiniatures($object);

                } else{
                    $object.addClass('selected');
                    $object.closest('li').addClass('selected');
                    addMiniatures($object);
                }
            }

            if ($('.selected-miniature-img').length > 0){
                $('.selection-content').addClass('items-selected');
                $('.count').text($('.selected-miniature-img').length + " selected");
            } else $('.selection-content').removeClass('items-selected');
        };


        function selectImagesNew(event, $obj) {

            function addMiniatures($obj) {
                let data = {
                    id: $obj.attr('data-id'),
                    original: $('.sirv-item-icon', $obj).attr('data-original'),
                    dir: $obj.attr('data-dir'),
                    type: $obj.attr('data-type'),
                }

                $('.selected-miniatures-container').append('<li class="selected-miniature"><img class="selected-miniature-img" data-id="' + data.id +
                    '" data-original="' + data.original + '" data-type="' + data.type +
                    '" data-dir="' + data.dir + '"' +
                    ' data-caption="" src="' + data.original + getItemParams(data.type, 40) +'"' + ' /></li>\n');
            }

            function removeMiniatures($obj) {
                $($('img[data-id=' + $obj.attr('data-id') + ']').closest('li.selected-miniature')).remove();
            }

            let curr = -1;

            if (event.ctrlKey) {
                event.preventDefault();
            }

            if (event.shiftKey) {
                event.preventDefault();

                curr = $('.sirv-item-body:not([data-type=dir]').index($obj);
                if (prev > -1) {
                    let miniaturesArray = [];
                    $('.selected-miniature-img').each(function () {
                        miniaturesArray.push($(this).attr('data-id'));
                    });
                    $('.sirv-item-body:not([data-type=dir]').slice(Math.min(prev, curr), 1 + Math.max(prev, curr)).each(function () {
                        if ($.inArray($(this).attr('data-id'), miniaturesArray) == -1) {
                            $(this).addClass('sirv-item-body--selected');
                            addMiniatures($(this));
                        }
                    });
                }
            } else {
                curr = prev = $('.sirv-item-body:not([data-type=dir]').index($obj);

                if ($obj.hasClass('sirv-item-body--selected')) {
                    $obj.removeClass('sirv-item-body--selected');
                    //$obj.closest('li').removeClass('selected');
                    removeMiniatures($obj);

                } else {
                    $obj.addClass('sirv-item-body--selected');
                    //$obj.closest('li').addClass('selected');
                    addMiniatures($obj);
                }
            }

            if ($('.selected-miniature-img').length > 0) {
                $('.selection-content').addClass('items-selected');
                $('.count').text($('.selected-miniature-img').length + " selected");
            } else $('.selection-content').removeClass('items-selected');
        };


        function restoreSelections(isAddImages){

            $('.sirv-item-body--selected').removeClass('sirv-item-body--selected');

            if(isAddImages){
                $('.selected-miniatures-container').empty();

                if($('.gallery-img').length > 0){
                    let galleryItems = $('.gallery-img');

                    $.each(galleryItems, function(index, value){
                        $('.selected-miniatures-container').append('<li class="selected-miniature"><img class="selected-miniature-img" data-id="'+ $(this).attr('data-id') +
                            '" data-original="'+ $(this).attr('data-original') +'" data-type="'+ $(this).attr('data-type') + '"'+
                            '  data-caption="'+ $(this).parent().siblings('span').children().val() +'"'+
                            '  src="' + $(this).attr('data-original') + getItemParams($(this).attr('data-type'), 40) +'"' +' /></li>\n');
                    });
                }
            }

            if($('.selected-miniature-img').length > 0){
                let selectedImages = $('.selected-miniature-img');
                $('.count').text(selectedImages.length + " selected");

                if($('.selection-content').not('.items-selected')){
                    $('.selection-content').addClass('items-selected');
                }

                $.each(selectedImages, function(index, value){
                    $('.sirv-item-body[data-id="' + $(value).attr('data-id') + '"]').addClass('sirv-item-body--selected');
                });
            }else{
                $('.selection-content').removeClass('items-selected');
            }
        }


        function clearSelection(){

            $(".selected-miniatures-container").empty();
            $('.sirv-item-body--selected').removeClass('sirv-item-body--selected');
            $('.selection-content').removeClass('items-selected');
            $('.count').text($('.selected-miniature-img').length + " selected");
        }


        function insert(){

                let html = '';
                let $gallery = $('.sirv-gallery-type[value=gallery-flag]');
                let $zoom = $('.sirv-gallery-type[value=gallery-zoom-flag]');
                let $spin = $('.sirv-gallery-type[value=360-spin]');
                let $video = $('.sirv-gallery-type[value=video]');
                let $staticImage = $('.sirv-gallery-type[value=static-image]');
                let $responsiveImage = $('.sirv-gallery-type[value=responsive-image]');

                let isResponsive = $responsiveImage.is(':checked');
                let isStatic = $staticImage.is(':checked');
                let id = '';

                let srImagesAttr = {
                    'isResponsive': isResponsive,
                    'isLazyLoading': '',
                };

                if($gallery.is(':checked') || $zoom.is(':checked') || $spin.is(':checked') || $video.is(':checked')){
                    $('.loading-ajax').show();

                    window['sirvIsChangedShortcode'] = true;

                    if($('.insert').hasClass('edit-gallery')){

                        id = parseInt($('.insert').attr('data-shortcode-id'));

                        save_shorcode_to_db('sirv_update_sc', id);


                    }else{
                        id = save_shorcode_to_db('sirv_save_shortcode_in_db');
                        html = '[sirv-gallery id='+ id +']';
                    }

                }else{

                    let isLazyLoading = $('#responsive-lazy-loading').is(":checked");

                    let imagesObj = {
                        srcs: $('.gallery-img'),
                        align: $('#gallery-align').val() == '' ? '' : 'align' + $('#gallery-align').val().replace('sirv-', ''),
                        profile: $('#gallery-profile').val() == false ? '' : $('#gallery-profile').val(),
                        width: isNaN(Number($('#gallery-width').val())) ? '' : Math.abs(Number($('#gallery-width').val())),
                        isLink: $('#gallery-link-img').is(":checked"),
                        isLazyLoading: isLazyLoading,
                        //networkType: $('input[name=sirv-cdn]:checked').val(),
                        isAltCaption: $('#responsive-static-caption-as-alt').is(":checked"),
                        isResponsive: isResponsive,
                        isStatic: isStatic

                    };

                    srImagesAttr.isLazyLoading = isResponsive ? isLazyLoading : false;

                    html = getImagesHtml(imagesObj);
                }
            if(window.isSirvGutenberg && window.isSirvGutenberg == true){
                window.sirvHTML = html;
                generateGutenbergData(getShortcodeData(), id, srImagesAttr);
            }else if(window.isSirvElementor && window.isSirvElementor == true){
                let jsonStr = JSON.stringify(getElementorData(getShortcodeData(), id, srImagesAttr));
                //getElementorData(getShortcodeData(), id, srImagesAttr);

                let ifr = $('iframe#elementor-preview-iframe')[0];

                window.updateElementorSirvControl(jsonStr, false);
                window.isSirvElementor = false;

                setTimeout(function(){window.runEvent(ifr.contentWindow.document, 'updateSh');}, 1000);
            }else{
                if(typeof window.parent.send_to_editor === 'function'){
                    //some strange issue with firefox. If return empty string, than shortcode html block will broken. So return string only if not empty.
                    if(html != '') window.parent.send_to_editor(html);

                    //hack to show visualisation of shortcode or responsive images
                    if (!!window.parent.switchEditors) {
                        window.parent.switchEditors.go("content", "html");
                        window.parent.switchEditors.go("content", "tmce");
                    }
                }
            }

            $('.loading-ajax').hide();
            bPopup.close();
        }

        function parseUrl(url){
            let urlObj = document.createElement('a');
            urlObj.href = url;

            return urlObj;
        }


        function getSirvCdnUrl(url){
            if (!!sirv_ajax_object.sirv_cdn_url){
                urlInfo = parseUrl(url);
                url = 'https://' + sirv_ajax_object.sirv_cdn_url + urlInfo.pathname;
            }

            return url;
        }


        function getImagesHtml(data){
            let imagesHTML = '';
            let placehodler_grey = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAAAAAA6fptVAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAAKSURBVAgdY3gPAADxAPAXl1qaAAAAAElFTkSuQmCC";
            let placeholder_grey_params = '?q=1&w=10&colorize.color=efefef';

            $.each(data.srcs, function(index, value){
                        let figure = document.createElement('figure');
                        let imgTag = document.createElement('img');
                        let imgSrc = getSirvCdnUrl($(value).attr('data-original'));
                        let title = $(this).parent().siblings('span').children().val();
                        let linkTag = '';

                        figure.classList.add('sirv-flx');
                        figure.classList.add('sirv-img-container');

                        if(data.align) figure.classList.add(data.align);
                        if(data.isResponsive){
                            imgTag.classList.add('Sirv');
                            if(data.width) figure.style["width"] = data.width + 'px';
                        }
                        imgTag.classList.add('sirv-img-container__img');
                        if(data.isStatic){
                            imgTag.src = imgSrc + generateOptionsUriStr({'profile': data.profile, 'w': data.width});
                        }else{
                            if(!data.isLazyLoading) imgTag.setAttribute('data-options', 'lazy: false;');
                            imgTag.setAttribute('data-src', imgSrc + generateOptionsUriStr({'profile': data.profile}));
                            //imgTag.src = placehodler_grey;
                            imgTag.src = imgSrc + placeholder_grey_params;
                        }

                        imgTag.alt = title;
                        //imgTag.title = title;

                        if(data.isLink){
                            linkTag = document.createElement('a');
                            linkTag.classList.add('sirv-img-container__link');
                            linkTag.href = data.profile == '' ? imgSrc : imgSrc + generateOptionsUriStr({'profile': data.profile});
                            linkTag.appendChild(imgTag);
                            figure.appendChild(linkTag);
                        }else{
                            figure.appendChild(imgTag);
                        }

                        if(!data.isAltCaption && title){
                            let figCaption = document.createElement('figcaption');
                            figCaption.classList.add('sirv-img-container__cap');
                            figCaption.textContent = title;
                            figure.appendChild(figCaption);
                        }

                        imagesHTML += figure.outerHTML;
                    });
            return imagesHTML;
        }


        function getElementorData(data, id, srImagesAttr={}){
            let images = data.images;
            let count = data.images.length;
            let type = id !== ''
                ? window.getShortcodeType(data)
                : srImagesAttr.isResponsive
                    ? 'Responsive images'
                    : 'Static images';
            let thumbParams = generateOptionsUriStr({profile: data.profile, thumbnail: 40, image: true});

            let tmpObj = {shortcode: {}, images: {}};

            let thumbImages = [];

            images.forEach( function(image, index) {
                thumbImages.push(image.url + thumbParams);
            });

            if(id){
                tmpObj.shortcode.id = id;
                tmpObj.shortcode.count = count;
                tmpObj.shortcode.type = type;
                tmpObj.shortcode.images = thumbImages.slice(0, 4);

            }else{
                let width = isNaN(Number(data.width)) ? '' : Math.abs(data.width);
                let imgParams = !srImagesAttr.isResponsive && width
                    ? generateOptionsUriStr({profile: data.profile, w: data.width})
                    : generateOptionsUriStr({profile: data.profile});
                let profileParams = generateOptionsUriStr({profile: data.profile});
                let align = getAlign(data.align);
                let imagesData = [];

                tmpObj.images.full = {};

                tmpObj.images.thumbs = thumbImages;
                tmpObj.images.full.width = width;
                tmpObj.images.full.align = align;
                tmpObj.images.full.linkToBigImage = data.link_image;
                tmpObj.images.full.profile = data.profile;
                tmpObj.images.full.type = type;
                tmpObj.images.full.count = count;
                tmpObj.images.full.isResponsive = srImagesAttr.isResponsive;
                tmpObj.images.full.isLazyLoading = srImagesAttr.isLazyLoading;
                tmpObj.images.full.isAltCaption = data.isAltCaption;

                images.forEach( function(image, index) {
                    //imagesData.push(image.url + imgParams);
                    imagesData.push({
                        'origUrl': image.url + profileParams,
                        'modUrl' : image.url + imgParams,
                        'caption': image.caption
                    });
                });

                tmpObj.images.full.imagesData = imagesData;


            }


            return tmpObj;

        }


        function getProfilesNames(){
            let profiles = [];
            $('#gallery-profile option:not([disabled=""])').each(function(index, el){
                if($(this).val() !== '') profiles.push($(this).val());
            });

            return profiles;
        }


        function generateGutenbergData(data, id, srImagesAttr={}){
            let images = data.images;
            let count = data.images.length;
            let shType = window.getShortcodeType(data);
            let width = ( !!!data.width || isNaN(data.width)) ? '' : Math.abs(data.width);
            let imgParams = generateOptionsUriStr({profile: data.profile});
            //let thumbParams = generateOptionsUriStr({profile: data.profile, thumbnail: 150, image: true});
            let imgThumbParams = generateOptionsUriStr({profile: data.profile, thumbnail: 150});
            let spinThumbParams = generateOptionsUriStr({profile: data.profile, image: true, w: 150, h: 150, 'canvas.width': 150, 'canvas.height': 150, 'scale.option': 'fit'});
            let tmpImages = [];
            let tmpImagesJson = [];
            let tmpSrImages = [];
            let tmpCount = count > 4 ? 4 : count;
            let align = '';


            if(id){
                for(let i = 0; i < tmpCount; i++){
                    let params = data.images[i].type == 'spin' ? spinThumbParams : imgThumbParams;
                    tmpImages.push({ 'src': data.images[i].url + params} );
                    tmpImagesJson.push(data.images[i].url + params);
                }
                align = data.align;
            }

            if(!id && images.length > 0){
                align = getAlign(data.align);

                for(let i = 0; i < images.length; i++){
                    tmpSrImages.push({
                        'src': data.images[i].url + imgParams,
                        'thumb': data.images[i].url + imgThumbParams,
                        'original': data.images[i].url,
                        'link': data.images[i].url + imgParams,
                        'alt': data.images[i].caption
                    });
                }
            }

            window.sirvShObj = {
                sirvId: id,
                sirvType: shType,
                sirvCount: count,
                sirvImages: tmpImages,
                sirvImagesJson: JSON.stringify(tmpImagesJson),
                sirvSrImages: tmpSrImages,
                sirvAlign: align,
                sirvWidth: width,
                sirvIsResponsive: '' + srImagesAttr.isResponsive,
                sirvIsLazyLoading: '' + srImagesAttr.isLazyLoading,
                sirvIsLink: '' + data.link_image,
                sirvIsAltCaption: '' + data.isAltCaption,
                sirvProfile: data.profile,
                sirvProfiles: JSON.stringify(getProfilesNames()),
            }
        }


        function getAlign(sirvAlign){
            align = '';
            switch (sirvAlign) {
                case 'sirv-left':
                    align = 'alignleft';
                    break;
                case 'sirv-center':
                    align = 'aligncenter';
                    break;
                case 'sirv-right':
                    align = 'alignright';
                    break;
                default:
                    align = '';
                    break;
            }

            return align;
        }


        function generateOptionsUriStr(optObject){
            let uriStr = '';
            let isFirst = true;
            $.each(optObject, function(key, element){
                if(element){
                    let delimiter = isFirst ? '?' : '&';
                    let pair = key == 'image' ? key : key + '=' + element;
                    uriStr += delimiter + pair;
                    isFirst = false;
                }
            });

            return uriStr;
        }


        function setFeaturedImage(){
            if($('.selected-miniature-img').length > 0){
                let selectedImage = $('.selected-miniature-img');
                let inputAnchor = $('#sirv-add-featured-image').attr('data-input-anchor');

                $(inputAnchor).val($(selectedImage).attr('data-original'));

                bPopup.close();
            }
        }


        function addWooSirvImages(){
            let items = [];

            id = window.sirvProductID;

            if ($('.selected-miniature-img').length > 0) {
                let selectedImages = $('.selected-miniature-img');
                $.each(selectedImages, function(index, img){
                    let url = $(img).attr('data-original');
                    let type = $(img).attr('data-type');
                    items.push({url: url, type: type, provider: 'sirv', order: index});
                });

                let $storage = $('#sirv_woo_gallery_data_'+ id);
                let data = JSON.parse($storage.val());

                data.items = data.items.concat(items);

                $storage.val(JSON.stringify(data));

                window.runEvent(window, 'update_woo_sirv_images');
            }

            bPopup.close();
        }


        function createGallery(){
            //showSearchResults(false);
            $('.selection-content').hide();
            $('.gallery-creation-content').show();
            imageSortable();

            if($('.selected-miniature-img').length > 0){
                let selectedImages = $('.selected-miniature-img');
                let documentFragment = $(document.createDocumentFragment());
                let addItem = $('<li class="gallery-item"><div class="sirv-add-item-wrapper sirv-no-select-text"><span class="dashicons dashicons-plus-alt2 sirv-add-icon"></span><span>Add items</span></div></li>\n');

                documentFragment.append(addItem);

                $.each(selectedImages, function(index, value){
                    let elemBlock = $('<li class="gallery-item"><div><div><a class="delete-image delete-image-icon" href="#" title="Remove"></a>'+
                        '<img class="gallery-img" src="' + $(value).attr('data-original') + getItemParams($(value).attr('data-type'), 150) +'"'+
                                                        ' data-id="'+ $(value).attr('data-id') +'"'+
                                                        'data-order="'+ index +'"'+
                                                        'data-original="'+ $(value).attr('data-original') +
                                                        '" data-type="'+ $(value).attr('data-type') +'" alt=""'+
                                                        ' title="' + basename($(value).attr('data-original')) +'"></div>'+
                                                        '<span><input type="text" placeholder="Text caption.."'+
                                                        ' data-setting="caption" class="image-caption" value="'+ $(value).attr('data-caption') +'" /></span></div></li>\n');
                    documentFragment.append(elemBlock);
                });

                $('.gallery-container').append(documentFragment);


                //bind events
                $('.delete-image').on('click', removeFromGalleryView);
                $('.select-images').on('click', function(){selectMoreImages(false)});
                $('.sirv-add-item-wrapper').on('click', function(){selectMoreImages(false)});
            }

            manageOptionsStates();

        }


        function removeFromGalleryView(){
            $(this).closest('li.gallery-item').remove();
            manageOptionsStates();
        }

        function clearGalleryView(){
            $('.gallery-container').empty();
        }


        function selectMoreImages(isEditGallery){
            $('.create-gallery>span').text('Add items');
            $('.gallery-creation-content').hide();
            $('.selection-content').show();
            restoreSelections(true);
            if(isEditGallery){
                //getData();
                getContentFromSirv();
            }
            clearGalleryView();
            $('.delete-image').off('click');
            $('.select-images').off('click');
            $('.sirv-add-item-wrapper').off('click');
        }

        function imageSortable(){

            function reCalcOrder(){
                $('.gallery-img').each(function(index){
                    $(this).attr('data-order', index);
                });
            }

            $( ".gallery-container" ).sortable({
                /*  revert: true,
                cursor: "move",
                scroll: false, */
                items: "> li:not(:first)",
                cursor: 'move',
                scrollSensitivity: 40,
                //forcePlaceholderSize: true,
                //forceHelperSize: false,
                //helper: 'clone',
                opacity: 0.65,
                scroll: false,
                //placeholder: "sirv-sortable-placeholder",
                stop: function( event, ui ) {
                    reCalcOrder();
                }
            });
        }



        function getShortcodeData(){

            function getEmbededAsValue(value){
                let $gallery = $('.sirv-gallery-type[value=gallery-flag]'),
                    $zoom = $('.sirv-gallery-type[value=gallery-zoom-flag]'),
                    $spin = $('.sirv-gallery-type[value=360-spin]');
                    $video = $('.sirv-gallery-type[value=video]');
                switch(value){
                    case 'gallery-flag':
                        return ($gallery.is(':checked') || $zoom.is(':checked') || $spin.is(':checked') || $video.is(':checked')) ? true : false;
                        break;
                    case 'gallery-zoom-flag':
                        return $zoom.is(':checked') ? true : false;
                        break;
                }
            }

            let sirvGalleryType = getSirvType($('.gallery-img'));

            let shortcode_data = {}
            let tmp_data_options = {'zgallery_data_options':{}, 'spin_options':{}, 'global_options':{}, 'diff_options': {}};

            /* let isResponsive = $('.sirv-gallery-type[value=responsive-image]').is(':checked');
            let isStatic = $('.sirv-gallery-type[value=static-image]').is(':checked'); */

            shortcode_data['width'] = $('#gallery-width').val();
            shortcode_data['thumbs_height'] = $('#gallery-thumbs-height').val();
            shortcode_data['gallery_styles'] = $('#gallery-styles').val();
            shortcode_data['align'] = $('#gallery-align').val();
            shortcode_data['profile'] = $('#gallery-profile').val();
            shortcode_data['use_as_gallery'] = getEmbededAsValue('gallery-flag');
            shortcode_data['use_sirv_zoom'] = getEmbededAsValue('gallery-zoom-flag');
            shortcode_data['link_image'] = $('#gallery-link-img').is(":checked");
            shortcode_data['show_caption'] = $('#gallery-show-caption').is(":checked");
            shortcode_data['isAltCaption'] = ($('input[name=sirv-alt-caption]:checked').val() == 'true');

            setDataOptionPair(tmp_data_options['zgallery_data_options'], $('#gallery-thumbs-position').attr('data-option-name'), $('#gallery-thumbs-position').val());
            setDataOptionPair(tmp_data_options['zgallery_data_options'], $('input[name=sirv-thumb-shape]:checked').attr('data-option-name'), $('input[name=sirv-thumb-shape]:checked').val());
            setDataOptionPair(tmp_data_options['zgallery_data_options'], $('input[name=sirv-mousewheel-zoom]:checked').attr('data-option-name'), $('input[name=sirv-mousewheel-zoom]:checked').val());
            setDataOptionPair(tmp_data_options['zgallery_data_options'], $('input[name=sirv-fullscreen-only]:checked').attr('data-option-name'), $('input[name=sirv-fullscreen-only]:checked').val());
            setDataOptionPair(tmp_data_options['zgallery_data_options'], $('input[name=sirv-context-menu]:checked').attr('data-option-name'), $('input[name=sirv-context-menu]:checked').val());
            setDataOptionPair(tmp_data_options['zgallery_data_options'], $('input[name=sirv-video-autoplay]:checked').attr('data-option-name'), $('input[name=sirv-video-autoplay]:checked').val());

            //spin options
            setDataOptionPair(tmp_data_options['spin_options'], $('input[name=sirv-spin-spinmethod]:checked').attr('data-option-name'), $('input[name=sirv-spin-spinmethod]:checked').val());
            setDataOptionPair(tmp_data_options['spin_options'], $('#sirv-spin-autospin').attr('data-option-name'), $('#sirv-spin-autospin').val());
            setDataOptionPair(tmp_data_options['spin_options'], $('#sirv-spinrotation-duration').attr('data-option-name'), $('#sirv-spinrotation-duration').val());

            //global options
            setDataOptionPair(tmp_data_options['global_options'], 'sirvGalleryType', sirvGalleryType);
            setDataOptionPair(tmp_data_options['global_options'], 'shortcodeName', $('#shortcode-name').val());

            shortcode_data['shortcode_options'] = tmp_data_options;

            let images = []
            $('.gallery-img:visible').each(function(){
                let tmp = {};
                let url = $(this).attr('data-original');
                //tmp['url'] = tmp_url.replace(/http(?:s)*:/, '');
                tmp['url'] = getSirvCdnUrl(url);
                tmp['order'] = $(this).attr('data-order');
                tmp['caption'] = $(this).parent().siblings('span').children().val() ;
                tmp['type'] = $(this).attr('data-type');
                $.ajax({
                    url:  url + "?info",
                    type: 'GET',
                    dataType: 'json',
                    async: false
                }).done(function(imageData){
                    tmp['image_width'] = imageData.width;
                    tmp['image_height'] = imageData.height;
                }).fail(function(jqXHR, status, error){
                    tmp['image_width'] = 0;
                    tmp['image_height'] = 0;
                });

                images.push(tmp);
            });

            shortcode_data['images'] = images;

            return shortcode_data;
        }

        function setDataOptionPair(obj, key, value){
            obj[key] = value;
        }


        function save_shorcode_to_db(action, row_id){

            row_id = row_id || -1;
            let id;
            let data = {
                        action: action,
                        shortcode_data: getShortcodeData()
            };

            if (row_id != -1) {
                data['row_id'] = row_id;
            };

            let ajaxData = {
                            url: sirv_ajax_object.ajaxurl,
                            type: 'POST',
                            async: false,
                            data: data
            };

            //processingOverlay='.loading-ajax'
            sendAjaxRequest(ajaxData, processingOverlay = '.loading-ajax', showingArea=false, isdebug=false, doneFn=function(response){
                id = response;
            });

            return id;
        }


        window['sirvEditGallery'] = function(id){
            $('.selection-content').hide();
            $('.gallery-creation-content').show();
            $('.edit-gallery>span').text('Save');
            $('.insert>span').text('Update');
            $('.select-images>span').text('Add items');
            $('.sirv-gallery-type[value=static-image]').attr('disabled', true);
            $('.sirv-gallery-type[value=responsive-image]').attr('disabled', true);
            imageSortable();

            //let id = window.top.sirv_sc_id;
            let data = {
                        action: 'sirv_get_row_by_id',
                        row_id: id
            }
            let ajaxData = {
                            url: ajaxurl,
                            type: 'POST',
                            data: data,
                            dataType: 'json'
            }

            sendAjaxRequest(ajaxData, processingOverlay='.loading-ajax', showingArea=false, isdebug=false, doneFn=function(response){
                profileTimer = window.setInterval(function(){setSelectedProfile('#gallery-profile', response);}, 100);

                $('#gallery-width').val(response['width']);
                $('#gallery-thumbs-height').val(response['thumbs_height']);
                $('#gallery-styles').val(response['gallery_styles']);
                $("#gallery-align").val(response['align']);

                if(typeof response.shortcode_options == 'object' && Object.keys(response.shortcode_options).length > 0){
                    $('#gallery-thumbs-position').val((response['shortcode_options']['zgallery_data_options']['thumbnails']));

                    let thumbShape = response['shortcode_options']['zgallery_data_options']['squareThumbnails'];
                    $('input[name=sirv-thumb-shape][value='+ thumbShape +']').prop('checked', true);

                    let mousewheelZoom = response['shortcode_options']['zgallery_data_options']['zoom-on-wheel'];
                    $('input[name=sirv-mousewheel-zoom][value='+ mousewheelZoom +']').prop('checked', true);

                    let fullscreenOnly = response['shortcode_options']['zgallery_data_options']['fullscreen-only'];
                    $('input[name=sirv-fullscreen-only][value='+ fullscreenOnly +']').prop('checked', true);

                    let contextMenu = response['shortcode_options']['zgallery_data_options']['contextmenu'];
                    $('input[name=sirv-context-menu][value='+ contextMenu +']').prop('checked', true);

                    let videoAutoplay = response['shortcode_options']['zgallery_data_options']['videoAutoplay'];
                    $('input[name=sirv-video-autoplay][value=' + videoAutoplay + ']').prop('checked', true);

                    let spin = response['shortcode_options']['spin_options']['spin'];
                    $('input[name=sirv-spin-spinmethod][value=' + spin + ']').prop('checked', true);

                    let autospin = response['shortcode_options']['spin_options']['autospin'];
                    $('select[data-option-name=autospin]').val(autospin);

                    let autospinSpeed = response['shortcode_options']['spin_options']['autospinSpeed'];
                    $('input[data-option-name=autospinSpeed]').val(autospinSpeed);

                    let shortcodeName = response['shortcode_options']['global_options']['shortcodeName'] || '';
                    $('#shortcode-name').val(shortcodeName);

                    if($.parseJSON(response['use_sirv_zoom']) == true){
                        $('.sirv-gallery-type[value=gallery-zoom-flag]').prop('checked', true);
                    }else{
                        $('.sirv-gallery-type[value=gallery-flag]').prop('checked', true);
                    }
                }

                $('#gallery-flag').prop('checked', $.parseJSON(response['use_as_gallery']));
                $('#gallery-zoom-flag').prop('checked', $.parseJSON(response['use_sirv_zoom']));
                $('#gallery-link-img').prop('checked', $.parseJSON(response['link_image']));
                $('#gallery-show-caption').prop('checked', $.parseJSON(response['show_caption']));

                let images = response['images'];
                let documentFragment = $(document.createDocumentFragment());
                let addItem = $('<li class="gallery-item"><div class="sirv-add-item-wrapper sirv-no-select-text"><span class="dashicons dashicons-plus-alt2 sirv-add-icon"></span><span>Add items</span></div></li>\n');

                documentFragment.append(addItem);

                for(let i = 0; i < images.length; i++){
                    let caption = stripslashes(images[i]['caption']);

                    let elemBlock = $('<li class="gallery-item"><div><div><a class="delete-image delete-image-icon" href="#" title="Remove"></a>'+
                        '<img class="gallery-img" src="' + images[i]['url'] + getItemParams(images[i]['type'], 150) +'"'+
                                                        ' data-id="' + md5((images[i]['url']).replace('https:', '')) +'"'+
                                                        'data-order="'+ images[i]['order'] +'"'+
                                                        'data-original="'+ images[i]['url'] +
                                                        '" data-type="'+ images[i]['type'] +'" alt=""'+
                                                        'title="' + basename(images[i]['url']) +'"></div>'+
                                                        '<span><input type="text" placeholder="Text caption..."'+
                                                        ' data-setting="caption" class="image-caption" value="'+ caption +'" /></span></div></li>\n');
                    documentFragment.append(elemBlock);
                }

                $('.gallery-container').append(documentFragment);

                manageOptionsStates();
                manageThumbPosition();

                //bind events
                $('.delete-image').on('click', removeFromGalleryView);
                $('.select-images').on('click', function(){selectMoreImages(true)});
                $('.sirv-add-item-wrapper').on('click', function(){selectMoreImages(true)});
                $('.insert').on('click', insert);
                $('.sirv-gallery-type').on('change', manageOptionsStates);
                $('#gallery-thumbs-position').on('change', manageThumbPosition);
            });
        } //end sirvEditGallery


        function manageThumbPosition(){
            let selectedItem = $( "#gallery-thumbs-position option:selected" ).val();
            switch (selectedItem) {
                case 'left':
                case 'right':
                    $('.sirv-thumb-hw-text').html('Thumbnail width');
                    break;
                case 'bottom':
                    $('.sirv-thumb-hw-text').html('Thumbnail height');
                    break;
            }
        }


        function setSelectedProfile(selector, response){
            let profile = response['profile'] == " " ? "" : response['profile'];
            if($(selector + ' option').length > 0){
                $(selector).val(profile);
                window.clearInterval(profileTimer);
            }
        }

        function stripslashes(str) {
            str = str.replace(/\\'/g,'\'');
            str = str.replace(/\\"/g,'&quot;');
            str = str.replace(/\\0/g,'\0');
            str = str.replace(/\\\\/g,'\\');
            return str;
        }

        function getSirvType(gallery){
            let itemsTypes = [];
            let count = gallery.length;
            let type = 'empty';

            $.each(gallery, function (index, item) {
                itemsTypes.push($(item).attr('data-type'));
            });

            if(count == 0){
                type = 'empty';
            }

            if(count == 1){
                if(itemsTypes[0] == 'spin') type = 'spin';
                if(itemsTypes[0] == 'video') type = 'video';
                if(itemsTypes[0] == 'image') type = 'image';
            }

            if(count > 1){
                if (countItem(itemsTypes, 'image') > 0){
                    if (countItem(itemsTypes, 'image') == count){type = 'image'} else{type='gallery'};
                }
                //if (countItem(itemsTypes, 'spin') > 0 || countItem(itemsTypes, 'video') > 0) type = 'gallery';
                if (countItem(itemsTypes, 'spin') > 0) type = 'gallery';
                if (countItem(itemsTypes, 'video') > 0){
                    if (countItem(itemsTypes, 'video') == count){type = 'video'} else{type='gallery'};
                }
            }
            return type;
        }


        function countItem(data, type){
            return data.filter(item => item == type).length;
        }

        function manageGalleryType(sirvGalleryType){
            switch (sirvGalleryType) {
                case 'empty':
                    break;
                case 'image':
                    $('.sirv-gallery-type').prop('disabled', false);
                    if ($('.sirv-gallery-type[value=360-spin]').is(':checked') || $('.sirv-gallery-type[value=video]').is(':checked')){
                        $('.sirv-gallery-type[value=responsive-image]').prop('checked', true);
                    }
                    manageElement($('#360-spin').parent(), 'hide');
                    manageElement($('#video').parent(), 'hide');
                    break;
                case 'spin':
                    $('.sirv-gallery-type').prop('disabled', true);
                    manageElement($('#360-spin').parent(), 'show');
                    manageElement($('#video').parent(), 'hide');
                    $('.sirv-gallery-type[value=360-spin]').prop('disabled', false);
                    $('.sirv-gallery-type[value=360-spin]').prop('checked', true);
                    break;
                case 'video':
                    $('.sirv-gallery-type').prop('disabled', true);
                    manageElement($('#360-spin').parent(), 'hide');
                    manageElement($('#video').parent(), 'show');
                    $('.sirv-gallery-type[value=video]').prop('disabled', false);
                    $('.sirv-gallery-type[value=video]').prop('checked', true);
                    break;
                case 'gallery':
                    $('.sirv-gallery-type').prop('disabled', true);
                    manageElement($('#360-spin').parent(), 'hide');
                    manageElement($('#video').parent(), 'hide');
                    $('.sirv-gallery-type[value=gallery-zoom-flag]').prop('disabled', false);
                    $('.sirv-gallery-type[value=gallery-flag]').prop('disabled', false);
                    if ($('.sirv-gallery-type[value=static-image]').is(':checked') ||
                        $('.sirv-gallery-type[value=responsive-image]').is(':checked') ||
                        $('.sirv-gallery-type[value=360-spin]').is(':checked') ||
                        $('.sirv-gallery-type[value=video]').is(':checked')
                    ){
                        $('.sirv-gallery-type[value=gallery-zoom-flag]').prop('checked', true);
                    }
                    break;

                default:
                    break;
            }
        }


        function manageOptionsStates(){

            if(window.isShortcodesPage !== null && window.isShortcodesPage == true){
                manageElement($('#responsive-image').parent(), 'hide');
                manageElement($('#static-image').parent(), 'hide');
                if(shGalleryFlag){
                    $('.sirv-gallery-type[value=gallery-zoom-flag]').prop('checked', true);
                    shGalleryFlag = false;
                }
            }

            let galleryLength = $('.gallery-img').length;

            let sirvGalleryType = getSirvType($('.gallery-img'));
            manageGalleryType(sirvGalleryType);

            if(galleryLength === 0){
                let isEditGallery = $('.insert').hasClass('edit-gallery') ? true : false;
                selectMoreImages(isEditGallery);

            }else if(galleryLength === 1){
                $('.gallery-zoom-flag-text').text('Zoom image');
                manageElement($('#gallery-flag').parent(), 'hide');

            }else if(galleryLength > 1){
                $('.gallery-zoom-flag-text').text('Zoom gallery');
                manageElement($('#gallery-flag').parent(), 'show');
            }

            if($('.insert').hasClass('edit-gallery')){
                $('.sirv-gallery-type[value=static-image]').attr('disabled', true);
                $('.sirv-gallery-type[value=responsive-image]').attr('disabled', true);
            }

            //-----------------managing options depends on selected type------------------------------
            if($('.sirv-gallery-type[value=gallery-flag]').is(':checked')){
                imgGallery = true;
                $('#gallery-styles').removeAttr("disabled");
                $('#gallery-align').removeAttr('disabled');

                manageOptionsByType('gallery');

            }else if($('.sirv-gallery-type[value=gallery-zoom-flag]').is(':checked')){
                $('#gallery-styles').removeAttr("disabled");
                $('#gallery-align').removeAttr('disabled');

                manageOptionsByType('zoom');

                if(galleryLength == 1){
                    manageElement($('#gallery-thumbs-height').parent(), 'hide');
                    manageElement($('#gallery-thumbs-position').parent(), 'hide');
                }

            }else if($('.sirv-gallery-type[value=static-image]').is(':checked')){
                /* $('#gallery-zoom-flag').attr('disabled', false)
                $('#gallery-zoom-flag').attr('checked', false); */
                $('#gallery-align').removeAttr('disabled');

                manageOptionsByType('static');

            }else if($('.sirv-gallery-type[value=responsive-image]').is(':checked')){
                /* $('#gallery-zoom-flag').attr('disabled', false)
                $('#gallery-zoom-flag').attr('checked', false); */
                if($('#gallery-width').val() == '') $('#gallery-align').attr('disabled', true);

                manageOptionsByType('responsive');

            }else if($('.sirv-gallery-type[value=360-spin]').is(':checked')){
                /* $('#gallery-zoom-flag').attr('disabled', true)
                $('#gallery-zoom-flag').attr('checked', false); */
                $('#gallery-styles').attr('disabled', true);
                $('#gallery-align').removeAttr('disabled');

                manageOptionsByType('spin');
            } else if ($('.sirv-gallery-type[value=video]').is(':checked')){
                manageOptionsByType('video');
            }
        }

        //change align disabled on input on Responsive images
        function onChangeWidthInputRI(){
            if($('.sirv-gallery-type[value=responsive-image]').is(':checked')){
                if($('#gallery-width').val() == ''){
                    $('#gallery-align').attr('disabled', true);
                }else{
                    $('#gallery-align').removeAttr('disabled');
                }
            }
        }

        //hide or show options by type
        function manageOptionsByType(type){
            $('[data-option-type]').filter(function(){
                //change text on width field depends on type
                if(type == 'static'){$('#gallery-width').attr('placeholder', 'original');}else{$('#gallery-width').attr('placeholder', 'auto');}
                if(type == 'responsive'){$('.sirv-label-width').html("Max width (px)");}else{$('.sirv-label-width').html("Width (px)");};

                let attrText = $(this).attr('data-option-type');
                let pattern = new RegExp(type, "i");
                if(attrText.search(pattern) !== -1){
                    $(this).show();
                }else{
                    $(this).hide();
                }

                //hide thumb option if zoom image
                if($('.gallery-zoom-flag-text').text() == 'Zoom image') manageElement($('.sirv-thumb-shape'), 'hide');
            });
        }

        //hide or show element
        function manageElement($selector, action){
            switch (action) {
                case 'hide':
                    $selector.hide();
                    break;
                case 'show':
                    $selector.show();
                    break;
            }
        }


        function setProfiles(){
            let data = {
                        action: 'sirv_get_profiles'
            }
            let ajaxData = {
                            url: ajaxurl,
                            type: 'POST',
                            data: data
            }

            sendAjaxRequest(ajaxData, processingOverlay='.loading-ajax', showingArea=false, isdebug=false, doneFn=function(response){
                $('#gallery-profile').empty();
                $('#gallery-profile').append($(response));
            });
        }


        function getPhpFilesLimitations(){
            let data = {
                        action: 'sirv_get_php_ini_data',
                        sirv_get_php_ini_data: true

            };


            let ajaxData = {
                            url: ajaxurl,
                            type: 'POST',
                            data: data
            }

            sendAjaxRequest(ajaxData, processingOverlay=false, showingArea=false, isdebug=false, function(response){
                let json_obj = JSON.parse(response);
                let tmpMaxPostSize = getPhpMaxPostSizeInBytes(json_obj.post_max_size);
                let tmpMaxFileSize = getPhpMaxPostSizeInBytes(json_obj.max_file_size);

                maxFilesCount = json_obj.max_file_uploads;
                maxFileSize = tmpMaxPostSize <= tmpMaxFileSize ? tmpMaxPostSize : tmpMaxFileSize;
                sirvFileSizeLimit = json_obj.sirv_file_size_limit;
            });

        }


        function getPhpMaxPostSizeInBytes(sizeParam){
            let size = parseInt(sizeParam.substr(0, sizeParam.length - 1));
            let sizeCapacity = sizeParam.substr(-1).toUpperCase();

            switch (sizeCapacity) {
                case 'G':
                    size *= 1024;
                case 'M':
                    size *= 1024;
                case 'K':
                    size *= 1024;
                    break;
            }

            return size;
        }


        function getFormatedFileSize(bytes) {
            let negativeFlag = false;
            let position = 0;
            let units = [" Bytes", " KB", " MB", " GB", " TB"];

            bytes = parseInt(bytes);

            if (bytes < 0) {
                bytes = Math.abs(bytes);
                negativeFlag = true;
            }

            while (bytes >= 1000 && (bytes / 1000) >= 1) {
                bytes /= 1000;
                position++;
            }

            if (negativeFlag) bytes *= -1;

            bytes = bytes % 1 == 0 ? bytes : bytes.toFixed(2);

            return bytes + units[position];

        }


        function sendAjaxRequest(AjaxData, processingOverlay=false, showingArea=false, isDebug=false, doneFn=false, beforeSendFn=false, errorFn=false){
            let isprocessingOverlay = typeof processingOverlay !== 'undefined' ? processingOverlay : false;
            let isShowingArea = typeof showingArea !== 'undefined' ? showingArea : false;

            AjaxData['beforeSend'] = function(){
                if(isprocessingOverlay){
                    $(processingOverlay).show();
                }
                if(typeof beforeSendFn == 'function') beforeSendFn();
            }

            $.ajax(AjaxData).done(
                function(response){
                    if(isDebug) console.log(response);
                    if(isShowingArea){
                        $(showingArea).html('');
                        $(showingArea).html(response);
                    }
                    if(isprocessingOverlay) $(processingOverlay).hide();
                    if(typeof doneFn == 'function') doneFn(response);
                }

            ).fail(function(jqXHR, status, error){
                    console.error('Error during ajax request: ' + error);
                    console.error('Status: ' + status);
                    if(isShowingArea){
                        $(showingArea).html('');
                        $(showingArea).html(error);
                    }
                    if(isprocessingOverlay) $(processingOverlay).hide();
                    if(typeof errorFn == 'function') errorFn(jqXHR, status, error);
                }
            );
        }


        function changeTab(e, $object){
            $('.sirv-tab-content').removeClass('sirv-tab-content-active');
            $('.nav-tab-wrapper > a').removeClass('nav-tab-active');
            $('.sirv-tab-content'+$object.attr('href')).addClass('sirv-tab-content-active');
            $object.addClass('nav-tab-active').blur();
            if(typeof e !== 'undefined') e.preventDefault();
        }

        // Initialization
        patchMediaBar();
        getPhpFilesLimitations();

        if($('.sirv-items-container').length > 0) getContentFromSirv();

    });
});

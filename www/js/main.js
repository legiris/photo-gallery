function initVideo()
{        
    $(".video_description .text .description").dotdotdot({});
    var isTruncated = $(".video_description .text .description").triggerHandler("isTruncated");
    if (isTruncated) {
        $(".video_description .text .description").click(function() {
            console.log($(this).triggerHandler("originalContent"));            
            $(this).trigger("destroy");
            $(this).height("auto");     
            $(this).removeClass("is-truncated");
            $("#join_button_big").hide();
            $("#join_button_small").show();
            $("#join_button_small").css({display:"block"});
        });
    }
    
    
    $(".video-js").each(function(i,e) {
        var id = "video_"+i;
        $(e).attr("id","video_"+i);
        vjs(id, {
            plugins: {
                resolutions: true
            }
        });
        
        var mplayer = videojs(id);
        mplayer.on("firstplay", function() {
            $(".homepage_video .video_container .description").slideUp();
        });
        
        mplayer.on("ended",function() {     
            $(".join_in_video").show();                        
        });
        
        
    });    
    
    
    $(".previews:not(.disabled) .vimage").each(function(i,e) {
        $(e).click(function(evt) {
            evt.preventDefault();
            var id = $(".video-js").attr("id");
            var mplayer = videojs(id);

            if (mplayer.paused()) {
                mplayer.play();
            }        

            var length = mplayer.duration();
            mplayer.currentTime((length*(i+1))/7);
        });
    });
        
    
    
    
   
   
}

function initSelectbox() {
    $("select").selectbox();
}

function initCkEditor()
{
    
    $(".ckeditor").each(function(i,e) {
        var id = $(e).attr("id");
        if (!id) {
            id = "ckeditor_"+i;
            $(e).attr("id",id);
        }
        CKEDITOR.replace(id);
        
        CKEDITOR.instances[id].on("contentDom", function() {
            CKEDITOR.instances[id].document.on("keyup", function() {
                CKEDITOR.instances[id].updateElement();
                $("#"+id).change();
            });
        });
        
    });
    /*
    if ("frm_adminVideoForm-description" in CKEDITOR.instances) {
        CKEDITOR.instances["frm-adminVideoForm-description"].on('contentDom', function(){
            CKEDITOR.instances["frm-adminVideoForm-description"].document.on("keyup", function() {
                CKEDITOR.instances["frm-adminVideoForm-description"].updateElement();
                $("#frm-adminVideoForm-description").change();
            });                                
        });    
    }*/
}

function initFormSubmitters()
{
    $(".form_submit").click(function(e){
        e.preventDefault();
        
        var fid = $(this).data("form-id");
        if ($(this).hasClass("form_stay")) {
            $("#"+fid).find("input[name=stay]").val("true");
        } else {
            $("#"+fid).find("input[name=stay]").val("");
        }
                
        if ($(this).hasClass("package")) {
            var package = "";
            if ($(this).hasClass("green")) package = "green";
            if ($(this).hasClass("red")) package = "red";
            if ($(this).hasClass("blue")) package = "blue";
            $("#"+fid).find("input[name=package]").val(package);
        }
                
        $("#"+fid).submit();        
    });
}

function initPhotoFileTree() {
  $(".file_photo_input").each(function(i,e) {
      
    var id = "fileTree"+i;

    $("<div class=\"overlay\"><div class=\"dialog\"><span class=\"title\">Vyberte soubor</span> <span class=\"done\"><a href=\"#\">Vybrat a odejít</a></span><span class=\"close\">x</span>\n\
<div class=\"fileTree jqueryFileTree\" id=\""+id+"\"></div></div></div>").insertAfter(e);                

    $(".overlay .dialog .close").click(function() {
        $(this).parents(".overlay").hide();
    });

    $("#"+id).fileTree({
      root: '/',
      script: 'http://localhost/web-projects/main-com/admin/?do=browsedirectory&type=photo',
      //script: 'http://main.com/admin/?do=browsedirectory&type=photo',
      expandSpeed: 400,
      collapseSpeed: 400,
      multiFolder: true,
      multiSelect: true
    });
            
    // open file tree window
    $(e).find(".browse").click(function(){
      var o = $("#"+id).parents(".overlay");
      if ($(o).is(":visible")) $(o).hide();
      else $(o).show();
    });
  });
    
  $('.done').on('click', function(){
    $el = $(".fileTree input:checked");
    
    if ($el.length === 0) {
      alert('Vyberte adresář nebo soubor(y) pro nahrání fotografií.');
    } else {
      var checked = $el
        .map(function() {
          return $(this).parent().find('a').attr('rel');
        })
        .get()
        .join(',');
      $('.file_photo_input').val(checked);
      $('.overlay').hide();
    }
  });
}

function initFileTree()
{
    $(".file_input").each(function(i,e) {   
      
        var id = "fileTree"+i;
        
        $("<div class=\"overlay\"><div class=\"dialog\"><span class=\"title\">Vyberte soubor</span><span class=\"close\">x</span>\n\
<div class=\"fileTree jqueryFileTree\" id=\""+id+"\"></div></div></div>").insertAfter(e);                
        
        $(".overlay .dialog .close").click(function() {
            $(this).parents(".overlay").hide();
        });
        
        $("#"+id).fileTree({
            root: '/',
            script: 'http://localhost/web-projects/main-com/admin/?do=browsedirectory&type=video',
            //script: 'http://main.com/admin/?do=browsedirectory&type=video',
            expandSpeed: 400,
            collapseSpeed: 400,
            multiFolder: false
        }, function(file) {
            $(e).find("input").val(file);
            var o = $("#"+id).parents(".overlay");
            $(o).hide();
        });
        
        $(e).find(".browse").click(function(){
            var o = $("#"+id).parents(".overlay");
            if ($(o).is(":visible")) $(o).hide();
            else $(o).show();
        });
    });    
}


function pageAdminVideo()
{
    
    $("#form_adminVideo input[name=type]").change(function(e) {
        if ($("#frm-adminVideoForm-type-trailer").prop("checked")) {
            $(".input_full_video").hide();
        } else {
            $(".input_full_video").show();            
        }
    });
    if ($("#frm-adminVideoForm-type-trailer").prop("checked")) {
        $(".input_full_video").hide();
    } else {
        $(".input_full_video").show();            
    }
    
    var code = "";
    var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    for( var i=0; i < 16; i++ ) {
        code += possible.charAt(Math.floor(Math.random() * possible.length));
    }    
    
    $("#form_adminVideo input[name=code]").val(code);
    
    $("#form_adminVideo").submit(function() {
        $(".loading_page").show();
    });
    
        /*
    $("#form_adminVideo").submit(function() {
        if ($("#form_adminVideo input[name=video_id]").val() == "") {               
            setTimeout(function() {
                var url = $(".form_check_insert").attr("href");
                var code = $("#form_adminVideo input[name=code]").val();
                $.getJSON( url+"&code="+code, function(data) {
                    if (data["result"]) {
                        //window.location.href=$(".form_exit").attr("href");
                        alert("ok");
                    } else {
                        //alert("Chyba při vkládání!");
                    }
                });
            },1000);
        }
    });
    */
}


function pageAdminPhotogallery()
{
    $("#form_adminPhotogallery").submit(function() {
        $(".loading_page").show();
    });
}



function initToggler() {
    $(".toggler").each(function(i,e) {
        var hidden = $(e).data("hidden");
        var block = $(e).data("block");
        
        if (hidden) {
            if ($("#"+hidden).val()) $(e).addClass("active");
            else $(e).removeClass("active");
        }
        
        if ($(e).hasClass("active")) {
            $("#"+block).show();
        } else {
            $("#"+block).hide();
        }
        
        $(e).click(function(event) {
            event.preventDefault();
            $(this).toggleClass("active");
            if ($(this).hasClass("active")) {
                $("#"+block).slideDown(400,"linear");
                if (hidden) $("#"+hidden).val("1");
            } else {
                $("#"+block).slideUp(400,"linear");
                if (hidden) $("#"+hidden).val("");
            } 
        });
    });
}

function initToggler2() {
    $(".toggler2").each(function(i,e) {        
        var block = $(e).data("block");
        var block2 = $(e).data("block2");
        
        $(e).change(function() {
            if ($(this).prop("checked")) {         
                $("#"+block).slideDown(400,"linear");
                $("#"+block2).slideUp(400,"linear");
            } else {
                $("#"+block).slideUp(400,"linear"); 
                $("#"+block2).slideDown(400,"linear");
            }
        });
        if (!$(this).prop("checked")) {
            $("#"+block).hide();
        } else {
            $("#"+block2).hide();
        }       
    });    
}

function initDialogConfirm() {
    /*
    $(".useDialogConfirm").click(function(e){
        e.preventDefault();
    });
    */
    $(".useDialogConfirm").each(function(i,e) {
        $(e).unbind();
        var dialog = $(e).data("dialog");
        $(e).click(function(evt) {
            initDialogConfirm();
            evt.preventDefault();            
            var button = $(this);
            
            var d_title = $("#"+dialog).data("title");
            var d_btn_ok = $("#"+dialog).data("btn-ok");
            var d_btn_storno = $("#"+dialog).data("btn-storno");
            
            var buttons = {};
            buttons[d_btn_ok] = function(){window.location = $(button).attr("href")};
            buttons[d_btn_storno] = function(){$("#"+dialog).dialog("close")};
            
            $("#"+dialog).dialog({
                title: d_title,
                modal: true,
                draggable: false,
                width: 500,
                buttons: buttons
            });
        });
    });
}

function initFlashes() {
    $(".flashes_container .flashes").slideDown(200);
    $(".flashes .close").click(function() {
        $(this).parent().slideUp(200);
    });
    $(".flashes .flash.success").delay(3000).children(".close").click();
    $("body,html").animate({ scrollTop: 0 }, 200);
}

function initVideoList() {
    $(".video_container .description .name").click(function() {
        $(this).css({
            "white-space":"normal",
            "overflow":"visible"
        });
    });
}

function initLightGallery() {
  $('#lightgallery').lightGallery();
}

function initChangeToggleIcon() {
  $('#changeToggle').click(function() {
    if ($('#hamburger-icon').css('display') === 'none') {
      $('#hamburger-icon').show();
      $('#close-icon').hide();
    } else {
      $('#close-icon').show();
      $('#hamburger-icon').hide();
    }
  });
}

function initAll() {        
    //initSelectbox();
    initCkEditor();
    initFormSubmitters();
    initPhotoFileTree();
    initFileTree();
    initToggler();    
    initToggler2();
    initDialogConfirm();    
    initVideoList();
    
    pageAdminVideo();
    initVideo();
    
    pageAdminPhotogallery();
    initLightGallery();
    initChangeToggleIcon();
}

$(function() {
    
    $.nette.init();
    
    initAll();
    
    /*
    CKEDITOR.replace('frm-adminVideoForm-description',
    {
        toolbar : [{ name: 'basicstyles', items : [ 'Bold','Italic','NumberedList','BulletedList' ] }],
        bodyClass : 'job_detail_page article static_page',
        height: "330px",
        resize_enabled: false,
        allowedContent: true,
        forcePasteAsPlainText: true
    }); 
    */
   
       
    
});

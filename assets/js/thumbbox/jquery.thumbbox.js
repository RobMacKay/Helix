/**
 * ThumbBox - A jQuery Plugin for viewing images or other data in a modal window
 *
 * Demo and Documentation at http://ennuidesign.com/projects/thumbbox
 *
 * @version 1.0.0
 * @author Jason Lengstorf <jason.lengstorf@ennuidesign.com>
 * @copyright 2010 Ennui Design
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */
(function($){
    $.fn.thumbBox = function(options)
    {
        var o = $.extend($.fn.thumbBox.defaults, options),
            inuse = false;

        return this.each(function()
        {
            $(this).addClass('thumbbox-list');
            if($(this).find('li a img').length>0)
            {
                $(this)
                    .find('li a')
                        .bind('click', function(event){
                                event.preventDefault();

                                displayThumbbox(
                                        $(this).parent('li'),
                                        $(this).attr('href'),
                                        $(this).find('img').attr('alt')
                                    );
                            });

                // Preload images
                $(this)
                    .find('a')
                        .each(function(){
                                var plsrc = $(this).attr("href");
                                $('<img>').attr('src', plsrc);
                            });
            }
        });

        function displayThumbbox(el, src, imgCap)
        {
            var main = $('<div>')
                    .addClass('thumbbox-main-img')
                    .css({
                            "height":parseInt(o.fullHeight)-parseInt(o.previewHeight)
                                +parseInt(o.padding)+"px"
                        }),
                thumbs = $('.thumbbox-list').find('a').clone(),
                ul = $('<ul>')
                    .css({
                            "left":"0px",
                            "width":$('.thumbbox-list').find('a').length*(parseInt(o.previewWidth)
                                    +parseInt(o.border)/2)+parseInt(o.border)/2+"px"
                        })
                    .html(thumbs),
                max_images = Math.min(
                        $('.thumbbox-list').find('a').length,
                        Math.floor(parseInt(o.fullWidth)/(parseInt(o.previewWidth)
                            +parseInt(o.border))-1)
                    ),
                wrapper = $('<span>')
                    .addClass('thumbbox-main-thumbs-wrapper')
                    .css({
                        "height":parseInt(o.previewHeight)+parseInt(o.border)*2+"px",
                        "width":parseInt(o.border)*max_images/2
                            +parseInt(o.border)/2+parseInt(o.previewWidth)*max_images+"px"
                    })
                    .html(ul),
                prev = $("<a>")
                    .addClass("thumbbox-prev-btn")
                    .attr("href", "#")
                    .bind("click", function(){
                            return thumbSlide(1);
                        })
                    .html("&laquo; prev"),
                next = $("<a>")
                    .addClass("thumbbox-next-btn")
                    .attr("href", "#")
                    .bind("click", function(){
                            return thumbSlide(-1);
                        })
                    .html("next &raquo;"),
                width = Math.round(parseInt(o.fullWidth)+parseInt(o.padding)*2),
                height = Math.round(parseInt(o.fullHeight)+parseInt(o.padding)*2),
                margin = Math.round((parseInt(o.fullWidth)+parseInt(o.padding)*2)/2*-1);

            $("<a>")
                .addClass("thumbbox-close-btn")
                .attr("href","#")
                .bind("click", function(){
                        $(".thumbbox-overlay, .thumbbox-main")
                            .fadeOut(300, function(){$(this).remove();});
                        return false;
                    })
                .html("&#215;")
                .appendTo(main);

            $('body')
                .unbind('keypress')
                .bind('keypress', function(e){
                    var key = e.charCode ? e.charCode : e.keyCode ? e.keyCode : 0;
                    switch(key) {
                        case 37:
                            thumbSlide(1);
                            break;
                        case 39:
                            thumbSlide(-1);
                            break;
                        case 27:
                            $(".thumbbox-overlay, .thumbbox-main")
                                .fadeOut(300, function(){$(this).remove();});
                            $('body').unbind("keypress");
                            break;
                    }
                });

            $.fn.thumbBox.buildModal(main, options, true);

            $("<div>")
                .addClass("thumbbox-main-thumbs")
                .css({
                        "height":parseInt(o.previewHeight)+parseInt(o.padding)+"px"
                    })
                .html(wrapper)
                .append(prev)
                .append(next)
                .appendTo('.thumbbox-main');

            $('.thumbbox-main-thumbs-wrapper')
                .find('a')
                .wrap("<li></li>");

            $('.thumbbox-main-thumbs-wrapper')
                .find('li')
                .bind("click", function(event){
                        event.preventDefault();
                        selectThumb($(this));
                    });

            setCurrentThumb(src);
            thumbImageSize();
            changeMainImage(el, src, imgCap);
        }

        function setCurrentThumb(src)
        {
            $('.thumbbox-main-thumbs-wrapper')
                .find('li')
                .each(function(){
                        if( $(this).find('a').attr("href")==src )
                        {
                            $(this)
                                .addClass("thumbbox-current-img")
                                .find('img')
                                .fadeTo(300, 1);
                        }
                    });
        }

        function thumbImageSize()
        {
            $(".thumbbox-main-thumbs-wrapper>ul>li")
                .css({
                    "width":parseInt(o.previewWidth)+"px",
                    "height":parseInt(o.previewHeight)+"px"
                })
                .find('img')
                    .each(function(){
                            var w = $(this).width(),
                                h = $(this).height(),
                                max_w = parseInt(o.previewWidth),
                                max_h = parseInt(o.previewHeight),
                                r = Math.max(max_w/w, max_h/h),
                                d = {
                                    "width":Math.round(w*r),
                                    "height":Math.round(h*r),
                                    "top":Math.round((max_h-h*r)/2),
                                    "left":Math.round((max_w-w*r)/2)
                                };
                            $(this).css({
                                "width":d.width+"px",
                                "height":d.height+"px",
                                "margin-top":d.top+"px",
                                "margin-left":d.left+"px"
                            });
                        });
        }

        function mainImageWidth(img)
        {
            var w = img.width(),
                h = img.height(),
                max_w = parseInt(o.fullWidth)-parseInt(o.padding)-parseInt(o.border)
                max_h = parseInt(o.fullHeight)-parseInt(o.previewHeight)-parseInt(o.padding)-parseInt(o.border),
                r = Math.min(max_w/w, max_h/h),
                d = {
                    "width":Math.round(w*r-parseInt(o.border)),
                    "height":Math.round(h*r),
                    "margin":Math.round((max_h-h*r)/2+parseInt(o.padding))
                };
            img.css({
                "width":d.width+"px",
                "height":d.height+"px",
                "margin-top":d.margin+"px",
                "margin-left":"auto",
                "margin-right":"auto"
            });
            $('.thumbbox-main-img>img').fadeTo(300, 1);
        }

        function selectThumb(thumb)
        {
            changeMainImage(thumb, thumb.find('a').attr("href"), thumb.find('img').attr("alt"));
            thumb
                .addClass("thumbbox-current-img")
                .find('img')
                    .fadeTo(300, 1)
                    .end()
                .parent()
                    .children('li')
                        .not(thumb)
                        .attr("class", "")
                        .find('img')
                            .fadeTo(300, .5);
        }

        function changeMainImage(li, src, cap)
        {
            if(li.attr("class")!="thumbbox-current-img")
            {
                $('.thumbbox-main-img>img').fadeTo(300, 0);
                var img = $('<img>')
                        .attr("src", src)
                        .css("opacity", 0);
                $('.thumbbox-main-img-caption-toggle').remove();
                if(cap!==undefined)
                {
                    if(cap.match("<p>")==undefined)
                    {
                        var title = "<p>"+cap+"</p>";
                    }
                    else
                    {
                        var title = cap;
                    }
                    var caption = $("<div>")
                            .addClass("thumbbox-main-img-caption")
                            .html(title),
                        toggle = $('<a>')
                            .addClass("thumbbox-main-img-caption-toggle")
                            .attr("href", "#")
                            .bind("click", function(){
                                if(o.showCaption===false)
                                {
                                    $(".thumbbox-main-img-caption")
                                        .fadeTo(300, 1);
                                    $(this)
                                        .text("Hide Caption")
                                        .css({"color":o.lightColor});
                                    o.showCaption = true;
                                }
                                else
                                {
                                    $(".thumbbox-main-img-caption")
                                        .fadeTo(300, 0);
                                    $(this)
                                        .text("Show Caption")
                                        .css({"color":o.darkColor});
                                    o.showCaption = false;
                                }
                                return false;
                            })
                            .appendTo($('.thumbbox-main-thumbs'));
                    if(o.showCaption===false)
                    {
                        caption.css({"opacity":0});
                        toggle.text("Show Caption").css({"color":o.darkColor});
                    }
                    else
                    {
                        var colors = $.fn.thumbBox.hex2rgb(o.darkColor);
                        caption.css({
                                "opacity":1,
                                "background" : "rgba("+colors[0]+","+colors[1]
                                        +","+colors[2]+",.8)"
                            });
                        toggle.text("Hide Caption").css({"color":o.lightColor});
                    }
                }
                else
                {
                    var caption = null;
                    var toggle = null;
                }
                $(".thumbbox-main-img")
                    .html(img)
                    .append(caption);
                setTimeout(function(){mainImageWidth(img);},250);
            }
        }

        function thumbSlide(d)
        {
            if(inuse===false)
            {
                inuse = true;
                var slider_width = $(".thumbbox-main-thumbs-wrapper").width(),
                    total_width = $(".thumbbox-main-thumbs-wrapper>ul").width(),
                    move_distance = (parseInt(o.previewWidth)+parseInt(o.border)/2)*d,
                    cur_left = parseInt($(".thumbbox-main-thumbs-wrapper>ul").css("left")),
                    new_left = cur_left+move_distance,
                    min_left = slider_width-total_width;
                thumb = (d==1) ? $(".thumbbox-current-img").prev('li') : $(".thumbbox-current-img").next('li');
                if( thumb.find('img').attr('src')!==undefined )
                {
                    selectThumb(thumb);
                }
                if( new_left<=0 && new_left>=min_left )
                {
                    $(".thumbbox-main-thumbs-wrapper>ul")
                        .animate({
                                    "left":new_left+"px"
                                },
                            300,
                            "swing",
                            function(){
                                inuse=false;
                            });
                }
                else
                {
                    inuse = false;
                }
            }
            return false;
        }
    }

    $.fn.thumbBox.buildModal = function(data, options, is_thumbbox)
    {
        var opts = $.extend($.fn.thumbBox.defaults, options),
            close = $("<a>")
                .addClass("thumbbox-close-btn")
                .attr("href","#")
                .bind("click", function(){
                        $(".thumbbox-overlay, .thumbbox-main")
                            .fadeOut(300, function(){$(this).remove();});
                        return false;
                    })
                .html("&#215;"),
            content;

        // Check if this is a ThumbBox or something else
        if( is_thumbbox!==true )
        {
            content = '<div class="thumbbox-main-modal">' + data + '</div>';
        }
        else
        {
            content = data;
        }

        $('<div>')
			.addClass("thumbbox-overlay")
			.css({"opacity":"0"})
			.appendTo('html');

		$('<div>')
			.addClass("thumbbox-main")
			.css({
				"top":$(window).scrollTop()+25+"px",
                "width":parseInt(opts.fullWidth)+"px",
                "height":"auto",
				"margin-left":parseInt(opts.fullWidth)/-2+"px",
                "opacity" : 0
			})
			.html(content)
            .append(close)
			.appendTo("html");

        $.fn.thumbBox.setColors(opts);

        $('.thumbbox-overlay').fadeTo(300, .5);
        $('.thumbbox-main').fadeTo(500, 1);
    }

    $.fn.thumbBox.setColors = function(options)
    {
        var light = options.lightColor,
            dark = options.darkColor,
            darkRGB = $.fn.thumbBox.hex2rgb(dark),
            dark_color;

        if( $.support.opacity===true )
        {
            dark_color = "rgba(" + darkRGB[0] + ", "
                    + darkRGB[1] + ", "
                    + darkRGB[2] + ", .8)";
        }
        else
        {
            dark_color = dark;
        }

        $(".thumbbox-main,.thumbbox-main-img-caption")
            .css({
                    "backgroundColor" : dark_color
                });

        $(".thumbbox-main-img,.thumbbox-main-modal")
            .css("background", light);

        $(".thumbbox-main-thumbs,.thumbbox-main-thumbs-wrapper>ul,.thumbbox-main-thumbs-wrapper>ul>li,a.thumbbox-prev-btn,a.thumbbox-next-btn")
            .css("background", dark);

        $(".thumbbox-main-img-caption,.thumbbox-main-img-caption>h3,.thumbbox-main-img-caption>p,.thumbbox-main-img-caption>p>a,a.thumbbox-next-btn,a.thumbbox-prev-btn")
            .css("color", light);

        $("a.thumbbox-close-btn")
            .css("color", dark);

        if( options.showCaption===true )
        {
            $("a.thumbbox-main-img-caption-toggle")
                .css("color", light);
        }
        else
        {
            $("a.thumbbox-main-img-caption-toggle")
                .css("color", dark);
        }

        $(".thumbbox-main-img>img,.thumbbox-main-thumbs,.thumbbox-main-thumbs-wrapper>ul>li")
            .css("border-color", dark);
    }

    $.fn.thumbBox.hex2rgb = function(hex)
    {
        var a, b, c, d, e, f; // Vars to store the hex value characters

        if( hex.charAt(0)==='#' )
        {
            hex = hex.replace('#', '');
        }

        if( hex.length==6 )
        {
            a = parseInt($.fn.thumbBox.getDec(hex.charAt(0)));
            b = parseInt($.fn.thumbBox.getDec(hex.charAt(1)));
            c = parseInt($.fn.thumbBox.getDec(hex.charAt(2)));
            d = parseInt($.fn.thumbBox.getDec(hex.charAt(3)));
            e = parseInt($.fn.thumbBox.getDec(hex.charAt(4)));
            f = parseInt($.fn.thumbBox.getDec(hex.charAt(5)));
        }
        else if( hex.length==3 )
        {
            // For hex shorthand, convert to longhand
            a = parseInt($.fn.thumbBox.getDec(hex.charAt(0)));
            b = a;
            c = parseInt($.fn.thumbBox.getDec(hex.charAt(1)));
            d = c;
            e = parseInt($.fn.thumbBox.getDec(hex.charAt(2)));
            f = e;
        }
        else
        {
            // If we're here, the hex string is no good
            return false;
        }

        // Convert the values to RGB
        return Array(
                    a*16+b,
                    c*16+d,
                    e*16+f
                );
    }

    $.fn.thumbBox.getDec = function(str) {
        if( str.toLowerCase()==="a" )
        {
            return 10;
        }
        if( str.toLowerCase()==="b" )
        {
            return 11;
        }
        if( str.toLowerCase()==="c" )
        {
            return 12;
        }
        if( str.toLowerCase()==="d" )
        {
            return 13;
        }
        if( str.toLowerCase()==="e" )
        {
            return 14;
        }
        if( str.toLowerCase()==="f" )
        {
            return 15;
        }
        return str;
    }

    $.fn.thumbBox.defaults = {
                "previewWidth" : "60",
                "previewHeight" : "60",
                "fullWidth" : "576",
                "fullHeight" : "476",
                "darkColor" : "#000",
                "lightColor" : "#FFF",
                "padding" : "12",
                "border" : "2",
                "showCaption" : true
            };
})(jQuery)

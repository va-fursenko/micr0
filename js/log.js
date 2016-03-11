/**
 * Yippee-ki-yay, motherfucker!
 * It's a log js
 */


    $( document ).ready(function() {
	/** @todo Менять цвет кнопки принаведении на див, а не только на <img> */
        $('#scrollup img').mouseover(function(){
            $(this).animate({opacity: 0.65}, 100);
        }).mouseout(function(){
            $(this).animate({opacity: 1}, 100);
        });
	$('.scrollup').click(function(){
            window.scroll(0, 0);
	    //$('body,html').animate({ scrollTop: 0 }, 100);
            return false;
        });

        $(window).scroll(function(){
            if ($(document).scrollTop() > 0 ){
                $('#scrollup').fadeIn('fast');
            }else{
                $('#scrollup').fadeOut('fast');
            }
        });
    });

    /*
    window.onload = function() { // после загрузки страницы

        var scrollUp = document.getElementById('scrollup'); // найти элемент

        scrollUp.onmouseover = function() { // добавить прозрачность
            scrollUp.style.opacity = 0.3;
            scrollUp.style.filter  = 'alpha(opacity=30)';
        };

        scrollUp.onmouseout = function() { //убрать прозрачность
            scrollUp.style.opacity = 0.5;
            scrollUp.style.filter  = 'alpha(opacity=50)';
        };

        scrollUp.onclick = function() { //обработка клика
            window.scrollTo(0,0);
        };

        // show button
        window.onscroll = function () { // при скролле показывать и прятать блок
            if ( window.pageYOffset > 0 ) {
                scrollUp.style.display = 'block';
            } else {
                scrollUp.style.display = 'none';
            }
        };
    };
*/

$(document).ready(function(){
    var $for_sale = $('#for_sale');
    var $for_sale_child_links = $('#for_sale_child_links');
    var $jobs = $('#jobs');
    var $jobs_child_links = $('#jobs_child_links');
    var $housing = $('#housing');
    var $housing_child_links = $('#housing_child_links');
    var $personals = $('#personals');
    var $personals_child_links = $('#personals_child_links');
    var $community = $('#community');
    var $community_child_links = $('#community_child_links');
    var $services = $('#services');
    var $services_child_links = $('#services_child_links');
    var $hidden = $('#hidden');
    var $header_menu = $('#header_menu');

    var $style_child_links = $('.child_links');
    var status_display;

    $for_sale.click(function(){
        $style_child_links.css('display', 'none');
        $for_sale_child_links.css('display', 'initial');
    });

    $jobs.click(function (){
        $style_child_links.css('display', 'none');
        $jobs_child_links.css('display', 'initial');
    });

    $housing.click(function(){
        $style_child_links.css('display', 'none');
        $housing_child_links.css('display', 'initial');
    });

    $personals.click(function(){
        $style_child_links.css('display', 'none');
        $personals_child_links.css('display', 'initial');
    });

    $community.click(function(){
        $style_child_links.css('display', 'none');
        $community_child_links.css('display', 'initial');
    });

    $services.click(function(){
        $style_child_links.css('display', 'none');
        $services_child_links.css('display', 'initial');
    });

    $header_menu.click(function(){
        var visible = $hidden.is(":visible");
        setVisibleForSelector($hidden, visible);

    });
    function setVisibleForSelector(selector, visibleStatus){
        if(!visibleStatus){
            selector.show( "2000", function() {
            });
        }
        else{
                selector.hide( "1000", function() {
            });
        }
    }
});
$j(document).ready(function(){
    function ValidURL(str) {
        var pattern = new RegExp('^(https?:\\/\\/)?'+ // protocol
        '((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.?)+[a-z]{2,}|'+ // domain name
        '((\\d{1,3}\\.){3}\\d{1,3}))'+ // OR ip (v4) address
        '(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*'+ // port and path
        '(\\?[;&a-z\\d%_.~+=-]*)?'+ // query string
        '(\\#[-a-z\\d_]*)?$','i'); // fragment locator
        return pattern.test(str);
    }

    if($j('#paywithmollie').length > 0){
        $j(document).ajaxComplete(function(event, xhr, options){
            // console.log(event);
            // console.log(xhr);
            // console.log(options);
            var response = JSON.parse(xhr.responseText);
            var paymenturl = response.paymentURL;
    
            if(ValidURL(paymenturl)){
                window.location.replace(paymenturl);
            }
        });
    }
    
})

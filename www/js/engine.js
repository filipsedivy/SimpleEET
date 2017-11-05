// jQuery Engine
$(document).ready(function() {
    // Instalace
    $inputServiceObject = $("input[name='service']");
    $('.eet-production').hide();
    $($inputServiceObject).on('click', function(e) {
        // Testovací rozhraní
        if(e.target.value === 'playground')
        {
            $('.eet-production').hide();
        }
        // Produkční rozhraní
        else if(e.target.value === 'production')
        {
            $('.eet-production').show();
        }
    });
});
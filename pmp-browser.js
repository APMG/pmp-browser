/* PMP Browser jQuery example */

if (typeof PMPB ===  'undefined') PMPB = {};

"use strict";

PMPB.search = function(apiUrl) {
    var resultsDiv = $('#results');
    var spinner = '<img src="spinner.gif" /> Searching...';
    resultsDiv.append(spinner);
    //console.log('req: ' + apiUrl);
    $.getJSON(apiUrl, function(res) {
        //console.log(res);
        resultsDiv.html('');
        $.each(res.results, function(idx,r) {
            //console.log(r);
            resultsDiv.append('<div class="result">'+r.attributes.title+'</div>');
        });
    });
}

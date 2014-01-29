/* PMP Browser jQuery example */

if (typeof PMPB ===  'undefined') PMPB = {};

"use strict";

PMPB.search = function(apiUrl, resultFormatter) {
    var resultsDiv = $('#results');
    var spinner = '<img src="spinner.gif" /> Searching...';
    if (!resultFormatter) resultFormatter = PMPB.formatResult;
    resultsDiv.append(spinner);
    //console.log('req: ' + apiUrl);
    $.getJSON(apiUrl+'&xhr=1', function(res) {
        //console.log(res);
        resultsDiv.html('');
        if (res.total == 0) {
            resultsDiv.html('Sorry, no results.');
            return;
        }
        
        $.each(res.results, function(idx,r) {
            //console.log(r);
            resultsDiv.append(resultFormatter(r));
        });
    });
}

PMPB.formatResult = function(r) {
    //console.log(r);
    if (!r) return '';
    var url = r.href;
    var d = '<div class="result">';
       d += '<div class="title">';
       d += '<a class="title" href="?doc='+encodeURIComponent(url)+'">';
       d += r.attributes.title;
       d += '</a>';
       d += ' - <span class="published">'+r.attributes.published+'</span>';
       d += '</div>';
       d += '<div class="tags">';
       if (r.attributes.tags) {
           d += ' [' + r.attributes.tags.join('; ') + ']';
       }
       d += '</div>';
       d += '<div class="teaser">';
       d += (r.attributes.teaser || r.attributes.description || '');
       d += '</div>';
       d += '</div>';
    return d;
}

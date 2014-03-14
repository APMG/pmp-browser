/* PMP Browser jQuery example */

if (typeof PMPB ===  'undefined') PMPB = {};

"use strict";

PMPB.search = function(apiUrl, resultFormatter) {
    var resultsDiv = $('#results');
    var spinner = '<img src="spinner.gif" /> Searching...';
    if (!resultFormatter) resultFormatter = PMPB.formatResult;
    resultsDiv.append(spinner);
    //console.log('req: ' + apiUrl);
    var baseUri = apiUrl.replace(/&?offset=\d+/, '');
    $.getJSON(apiUrl+'&xhr=1', function(res) {
        console.log(res);
        resultsDiv.html('');
        if (res.total == 0) {
            resultsDiv.html('Sorry, no results.');
            return;
        }
        $('#pager').append('<div class="total">'+res.total+' results</div>');
        var pagerSettings = {
            spread: 2,
            theme: 'grey',
            total: parseInt(res.total),
            limit: parseInt(res.query.limit),
            index: parseInt(res.query.offset || 0),
            first: '|&#171;',
            prev:  '&#171;',
            last:  '&#187;|',
            next:  '&#187;',
            url:   function(idx) {
                var u = baseUri + '&offset='+(idx*res.query.limit);
                return u;
            }
        }; 
        //console.log(pagerSettings);
        $('#pager').wPaginate(pagerSettings);
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
    var thumbnail = PMPB.findImage(r);
    var d = '<div class="result">';
       d += '<div class="thumb"><img src="'+thumbnail+'" /></div>';
       d += '<div class="title">';
       d += '<a class="title" href="?doc='+encodeURIComponent(url)+'">';
       d += r.attributes.title;
       d += '</a>';
       d += ' - <span class="published">'+r.attributes.published+'</span>';
       d += '</div>';
       d += '<div class="uri"><a class="uri" href="'+url+'">'+url+'</a></div>';
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

PMPB.findImage = function(r) {
    var resImg = 'http://publicmediaplatform.org/wp-content/uploads/logo.png';
    if (!r.items) {
        return resImg;
    }
    $.each(r.items, function(idx, item) {
        if (item.links.profile[0].href.match(/\/image/)) {
            $.each(item.links.enclosure, function(idx2, img) {
                if (img.meta.crop == 'primary') {
                    resImg = img.href;
                }
            });
        }
    });
    return resImg;
}

                

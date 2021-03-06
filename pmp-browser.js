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
    var jqXHR = $.getJSON(apiUrl+'&xhr=1', function(res) {
        console.log(res);
        resultsDiv.html('');
        if (res.total == 0) {
            resultsDiv.html('Sorry, no results.');
            return;
        }
        $('#pager').append('<div class="api-url">'+res.uri+'</div>');
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
    })
        .done(function() {
            console.log('xhr is done');
        })
        .fail(function() {
            console.log('xhr failed');
            resultsDiv.html('Sorry, there was a server problem. Please try again later.');
        })
        .always(function() {
            console.log('xhr always ftw');
        });
}

PMPB.formatResult = function(r) {
    //console.log(r);
    if (!r) return '';
    var url = r.href;
    var thumbnail = PMPB.findImage(r);
    var profile   = r.links.profile[0].title || r.links.profile[0].href.match(/\/(\w+)$/)[1];
    var d = '<div class="result">';
       d += '<div class="thumb"><img src="'+thumbnail+'" /></div>';
       d += '<div class="title">';
       d += '<a class="title" href="?doc='+encodeURIComponent(url)+'">';
       d += r.attributes.title;
       d += '</a>';
       d += ' - <span class="published">'+r.attributes.published+'</span>';
       d += ' - <span class="profile">'+profile+'</span>';
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

PMPB.profileIsImage = function(item) {
    return item.links.profile[0].href.match(/\/image/);
}

PMPB.findImage = function(r) {
    var resImg = 'http://publicmediaplatform.org/wp-content/uploads/logo.png';
    if (!r.items && !PMPB.profileIsImage(r)) {
        return resImg;
    }
    if (PMPB.profileIsImage(r)) {
        $.each(r.links.enclosure, function(idx, img) {
            if (!img.meta) return;
            if (img.meta.crop == 'primary') {
                resImg = img.href;
            }
        });

        // if there's only one enclosure use it
        if (r.links.enclosure.length == 1) {
            resImg = r.links.enclosure[0].href;
        }
    }
    else {
        $.each(r.items, function(idx, item) {
            if (!item) return;
            if (PMPB.profileIsImage(item)) {
                $.each(item.links.enclosure, function(idx2, img) {
                    if (!img.meta) return;
                    // TODO better way to id thumbnail, maybe by size
                    if (img.meta.crop == 'primary' || img.meta.crop == 'square') {
                        resImg = img.href;
                    }
                });
                if (item.links.enclosure.length == 1) {
                    resImg = item.links.enclosure[0].href;
                }
            }
        });
    }
    return resImg;
}


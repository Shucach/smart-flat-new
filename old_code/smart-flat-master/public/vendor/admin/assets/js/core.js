/**
 * Author: Ivan Horobets
 * Company: Vanzzo Solution
 * Email: vanzzosolution@gmail.com
 */

'use strict';

/**
 * X-CSRF
 */
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
    },
});

/**
 * Action events
 *      #delete
 */
function actions($action) {
    var $delete = $action.find('.delete-item');

    $delete.on('click', function (e) {
        e.preventDefault();

        var $this = $(this);
        $.ajax({
            type: 'POST',
            url: $action.attr('data-actions'),
            data: {
                id: $this.attr('data-id'),
                action: 'delete',
            },
        }).done(function (data) {
            if (data.id) {
                $.Notification.notify(
                    'success',
                    'top left',
                    null,
                    data.message,
                );
                $this.closest('tr').remove();
            } else {
                $.Notification.notify('error', 'top left', 'Error!');
            }
        });
    });
}
actions($('.actions'));

/**
 * Slug
 *
 */
var slugGenerator = {
    translit: function (str) {
        var ru = {
                а: 'a',
                б: 'b',
                в: 'v',
                г: 'g',
                д: 'd',
                е: 'e',
                ё: 'e',
                ж: 'j',
                з: 'z',
                и: 'i',
                к: 'k',
                л: 'l',
                м: 'm',
                н: 'n',
                о: 'o',
                п: 'p',
                р: 'r',
                с: 's',
                т: 't',
                у: 'u',
                ф: 'f',
                х: 'h',
                ц: 'c',
                ч: 'ch',
                ш: 'sh',
                щ: 'shch',
                ы: 'y',
                э: 'e',
                ю: 'u',
                я: 'ya',
            },
            n_str = [];

        str = str.replace(/[ъь]+/g, '').replace(/й/g, 'i');

        for (var i = 0; i < str.length; ++i) {
            n_str.push(
                ru[str[i]] ||
                    (ru[str[i].toLowerCase()] == undefined && str[i]) ||
                    ru[str[i].toLowerCase()].replace(/^(.)/, function (match) {
                        return match.toUpperCase();
                    }),
            );
        }
        n_str = n_str.join('');
        n_str = n_str.replace(/ /g, '-');

        return n_str;
    },

    init: function ($inputSlug) {
        var $from = $('input[name="' + $inputSlug.attr('data-from') + '"]'),
            $this = this;
        $from.keyup(function () {
            $inputSlug.val($this.translit($(this).val()));
        });
    },
};

var $inputSlug = $('input[name="slug"]');
if ($inputSlug.length) {
    slugGenerator.init($inputSlug);
}

/**
 * Wysiwig
 */
var $textarea = $('.core-wysiwig');
if ($textarea.length) {
    tinymce.init({
        selector: '.core-wysiwig',
        theme: 'modern',
        height: 300,
        plugins: [
            'advlist autolink link image lists charmap print preview hr anchor pagebreak spellchecker',
            'searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking',
            'save table contextmenu directionality emoticons template paste textcolor',
        ],
        toolbar:
            'insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | l      ink image | print preview media fullpage | forecolor backcolor emoticons',
        style_formats: [
            { title: 'Bold text', inline: 'b' },
            { title: 'Red text', inline: 'span', styles: { color: '#ff0000' } },
            { title: 'Red header', block: 'h1', styles: { color: '#ff0000' } },
            { title: 'Example 1', inline: 'span', classes: 'example1' },
            { title: 'Example 2', inline: 'span', classes: 'example2' },
            { title: 'Table styles' },
            { title: 'Table row 1', selector: 'tr', classes: 'tablerow1' },
        ],
    });
}

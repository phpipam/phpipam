/**
 * @license MIT
 * @license http://opensource.org/licenses/MIT Massachusetts Institute of Technology
 * @copyright 2014 Patric Gutersohn
 * @author Patric Gutersohn
 * @example index.html BDT in action.
 * @link http://bdt.pguso.de Documentation
 * @version 1.0.0
 *
 * @summary BDT - Bootstrap Data Tables
 * @description sorting, paginating and search for bootstrap tables
 */

(function ($) {
    "use strict";

    /**
     * @type {number}
     */
    var actualPage = 1;
    /**
     * @type {number}
     */
    var pageCount = 0;
    /**
     * @type {number}
     */
    var pageRowCount = 0;
    /**
     * @type {string}
     */
    var pages = '';
    /**
     * @type {object}
     */
    var obj = null;
    /**
     * @type {boolean}
     */
    var activeSearch = false;
    /**
     * @type {string}
     */
    var arrowUp = '';
    /**
     * @type {string}
     */
    var arrowDown = '';
    /**
     * @type {string}
     */
    var searchFormClass = '';
    /**
     * @type {string}
     */
    var pageFieldText = '';
    /**
     * @type {string}
     */
    var searchFieldText = '';
    /**
     * @type {string}
     */
    var divClass = '';


    /* @cookies */
    function createCookie(name,value,days) {
        var date;
        var expires;

        if (typeof days === 'undefined') {
            date = new Date();
            date.setTime(date.getTime()+(days*24*60*60*1000));
            expires = "; expires="+date.toGMTString();
        }
        else {
    	    var expires = "";
        }

        document.cookie = name+"="+value+expires+"; path=/";
    }
    function readCookie(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for(var i=0;i < ca.length;i++) {
            var c = ca[i];
            while (c.charAt(0)==' ') c = c.substring(1,c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length,c.length);
        }
        return null;
    }

    $.fn.bdt = function (options, callback) {

        var settings = $.extend({
            pageRowCount: 10,
            arrowDown: 'fa-angle-down',
            arrowUp: 'fa-angle-up',
            searchFormClass: 'search-form clearfix',
            pageFieldText: 'Entries per Page:',
            searchFieldText: 'Search',
            divClass: 'text-right'
        }, options);

        /**
         * @type {object}
         */
        var tableBody = null;

        return this.each(function () {
            obj = $(this).addClass('bdt');
            tableBody = obj.find("tbody");
            pageRowCount = settings.pageRowCount;
            arrowDown = settings.arrowDown;
            arrowUp = settings.arrowUp;
            searchFormClass = settings.searchFormClass;
            pageFieldText = settings.pageFieldText;
            searchFieldText = settings.searchFieldText;
            divClass = settings.divClass;

            /**
             * search input field
             */
            obj.before(
                $('<div/>')
                    .addClass('table-header '+divClass)
                    .append (
                        $('<form/>')
                            .addClass(searchFormClass)
                            .attr('role', 'form')
                            .append(
                                $('<div class="form-group">')
                                .append(
                                    $('<input/>')
                                        .addClass('form-control input-sm')
                                        .attr('id', 'search')
                                        .attr('placeholder', searchFieldText)
                                )
                            )
                            .append(
                                $('<div class="form-group">')
                                .append($('<label/>')
                                            .addClass('control-label')
                                            .text(pageFieldText)
                                )
                                .append(
                                    $('<select/>')
                                        .attr('id', 'page-rows-form')
                                        .addClass('form-control input-sm')
                                        .append(
                                            $('<option>', {
                                                value: 25,
                                                text: 25
                                            })
                                        )
                                        .append(
                                            $('<option>', {
                                                value: 50,
                                                text: 50
                                            })
                                        )
                                        .append(
                                            $('<option>', {
                                                value: 100,
                                                text: 100
                                            })
                                        )
                                        .append(
                                            $('<option>', {
                                                value: 250,
                                                text: 250
                                            })
                                        )
                                         .append(
                                            $('<option>', {
                                                value: 500,
                                                text: 500
                                            })
                                        )
                                         .append(
                                            $('<option>', {
                                                value: 1000000,
                                                text: 'All'
                                            })
                                        )
                                    )
                            )
                    )
            );
            // select proper pagecnt from dropdown
            $('select#page-rows-form option[value='+pageRowCount+']').attr("selected","selected");

            if(tableBody.children('tr').length < 25)    { $('.table-header select').hide(); $('.table-header label').hide(); }

            /**
             * select field for changing row per page
             */
            obj.after(
                $('<div/>')
                    .addClass(divClass)
                    .attr('id', 'table-footer')
                    .append(
                        $('<div/>')
                            .addClass('table-info')
                            //.text('Showing 1 to 10 of 100 entries')
                    )

            );

            if (tableBody.children('tr').length > pageRowCount) {
                setPageCount(tableBody);
                addPages();
                paginate(tableBody, actualPage);
            }

            searchTable(tableBody);
            sortColumn(obj, tableBody);

            $('body').on('click', '.pagination li', function (event) {
                var listItem;

                if ($(event.target).is("a")) {
                    listItem = $(event.target).parent();
                } else {
                    listItem = $(event.target).parent().parent();
                }

                var page = listItem.data('page');

                if (!listItem.hasClass("disabled") && !listItem.hasClass("active")) {
                    paginate(tableBody, page);
                }
            });

            $('#page-rows-form').on('change', function () {
                var options = $(this).find('select');
                pageRowCount = $(this).val();

                // save cookie
                createCookie("table-page-size", pageRowCount, 365);

                setPageCount(tableBody);
                addPages();
                paginate(tableBody, 1);

/*
                if (tableBody.children('tr').length > pageRowCount) {
                    setPageCount(tableBody);
                    addPages();
                    paginate(tableBody, 1);
                }
*/
            });

        });

        /**
         * the main part of this function is out of this thread http://stackoverflow.com/questions/3160277/jquery-table-sort
         * @author James Padolsey http://james.padolsey.com
         * @link http://jsfiddle.net/spetnik/gFzCk/1953/
         * @param obj
         */
        function sortColumn(obj) {
            var table = obj;
            var oldIndex = 0;

            obj
                .find('thead th')
                .wrapInner('<span class="sort-element"/>')
                .append(
                    $('<span/>')
                        .addClass('sort-icon fa')
                )
                .each(function () {

                    var th = $(this);
                    var thIndex = th.index();
                    var inverse = false;
                    var addOrRemove = true;

                    th.click(function () {
                        if(!$(this).hasClass('disable-sorting')) {
                            if($(this).find('.sort-icon').hasClass(arrowDown)) {
                                $(this)
                                    .find('.sort-icon')
                                    .removeClass( arrowDown )
                                    .addClass(arrowUp);

                            } else {
                                $(this)
                                    .find('.sort-icon')
                                    .removeClass( arrowUp )
                                    .addClass(arrowDown);
                            }

                            if(oldIndex != thIndex) {
                                obj.find('.sort-icon').removeClass(arrowDown);
                                obj.find('.sort-icon').removeClass(arrowUp);

                                $(this)
                                    .find('.sort-icon')
                                    .toggleClass( arrowDown, addOrRemove );
                            }

                            table.find('td').filter(function () {

                                return $(this).index() === thIndex;

                            }).sortElements(function (a, b) {

                                return $.text([a]) > $.text([b]) ?
                                    inverse ? -1 : 1
                                    : inverse ? 1 : -1;

                            }, function () {

                                // parentNode is the element we want to move
                                return this.parentNode;

                            });

                            inverse = !inverse;
                            oldIndex = thIndex;
                        }
                    });

                });
        }

        /**
         * create li elements for pages
         */
        function addPages() {
            $('#table-nav').remove();
            pages = $('<ul/>');

            $.each(new Array(pageCount), function (index) {
                var additonalClass = '';
                var page = $();

                if ((index + 1) == 1) {
                    additonalClass = 'active';
                }

                pages
                    .append($('<li/>')
                        .addClass(additonalClass)
                        .data('page', (index + 1))
                        .append(
                            $('<a/>')
                                .text(index + 1)
                        )
                    );
            });

            /**
             * pagination, with pages and previous and next link
             */
            $('#table-footer')
                .addClass('row')
                .append(
                    $('<nav/>')
                        .addClass('')
                        .attr('id', 'table-nav')
                        .append(
                            pages
                                .addClass('pagination input-sm')
                                .prepend(
                                    $('<li/>')
                                        .addClass('disabled')
                                        .data('page', 'previous')
                                        .append(
                                            $('<a />')
                                                .append(
                                                    $('<span/>')
                                                        .attr('aria-hidden', 'true')
                                                        .html('&laquo;')
                                                )
                                                .append(
                                                    $('<span/>')
                                                        .addClass('sr-only')
                                                        .text('Previous')
                                                )
                                        )
                                )
                                .append(
                                    $('<li/>')
                                        .addClass('disabled')
                                        .data('page', 'next')
                                        .append(
                                            $('<a/>')
                                                .append(
                                                    $('<span/>')
                                                        .attr('aria-hidden', 'true')
                                                        .html('&raquo;')
                                                )
                                                .append(
                                                    $('<span/>')
                                                        .addClass('sr-only')
                                                        .text('Next')
                                                )
                                        )
                                )
                        )
                );

        }

        /**
         *
         * @param tableBody
         */
        function searchTable(tableBody) {
            $("#search").on("keyup", function () {
                $.each(tableBody.find("tr"), function () {

                    var text = $(this)
                        .text()
                        .replace(/ /g, '')
                        .replace(/(\r\n|\n|\r)/gm, "");

                    var searchTerm = $("#search").val();

                    if (text.toLowerCase().indexOf(searchTerm.toLowerCase()) == -1) {
                        $(this)
                            .hide()
                            .removeClass('search-item');
                    } else {
                        $(this)
                            .show()
                            .addClass('search-item');
                    }

                    if (searchTerm != '') {
                        activeSearch = true;
                    } else {
                        activeSearch = false;
                    }
                });


                if (tableBody.children('tr').length > pageRowCount) {
                    setPageCount(tableBody);
                    addPages();
                    paginate(tableBody, 1);
                }

            });
        }

        /**
         *
         * @param tableBody
         */
        function setPageCount(tableBody) {
            if (activeSearch) {
                pageCount = Math.ceil(tableBody.children('.search-item').length / pageRowCount);
            } else {
                pageCount = Math.ceil(tableBody.children('tr').length / pageRowCount);
            }

            if (pageCount == 0) {
                pageCount = 1;
            }
        }

        /**
         *
         * @param tableBody
         * @param page
         */
        function paginate(tableBody, page) {
            if (page == 'next') {
                page = actualPage + 1;
            } else if (page == 'previous') {
                page = actualPage - 1;
            }

            actualPage = page;

            var rows = (activeSearch ? tableBody.find(".search-item") : tableBody.find("tr"));
            var endRow = (pageRowCount * page);
            var startRow = (endRow - pageRowCount);
            var pagination = $('.pagination');

            rows
                .hide();

            rows
                .slice(startRow, endRow)
                .show();

            pagination
                .find('li')
                .removeClass('active disabled');

            pagination
                .find('li:eq(' + page + ')')
                .addClass('active');

            if (page == 1 && page == pageCount) {
                pagination
                    .find('li:last')
                    .addClass('disabled');

            } else if (page == 1) {
                pagination
                    .find('li:first')
                    .addClass('disabled');

            } else if (page == pageCount) {
                pagination
                    .find('li:last')
                    .addClass('disabled');
            }
        }
    }
}(jQuery));
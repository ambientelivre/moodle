// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Javascript to load and render the list of calendar events for a
 * given day range.
 *
 * @module     block_timeline/event_list
 * @copyright  2016 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(
[
    'jquery',
    'core/notification',
    'core/templates',
    'core/str',
    'core/user_date',
    'block_timeline/calendar_events_repository',
    'core/pending'
],
function(
    $,
    Notification,
    Templates,
    Str,
    UserDate,
    CalendarEventsRepository,
    Pending
) {

    var SECONDS_IN_DAY = 60 * 60 * 24;
    var courseview = false;

    var SELECTORS = {
        EMPTY_MESSAGE: '[data-region="no-events-empty-message"]',
        ROOT: '[data-region="event-list-container"]',
        EVENT_LIST_CONTENT: '[data-region="event-list-content"]',
        EVENT_LIST_WRAPPER: '[data-region="event-list-wrapper"]',
        EVENT_LIST_LOADING_PLACEHOLDER: '[data-region="event-list-loading-placeholder"]',
        TIMELINE_BLOCK: '[data-region="timeline"]',
        TIMELINE_SEARCH: '[data-action="search"]',
        MORE_ACTIVITIES_BUTTON: '[data-action="more-events"]',
        MORE_ACTIVITIES_BUTTON_CONTAINER: '[data-region="more-events-button-container"]'
    };

    var TEMPLATES = {
        EVENT_LIST_CONTENT: 'block_timeline/event-list-content',
        MORE_ACTIVITIES_BUTTON: 'block_timeline/event-list-loadmore',
        LOADING_ICON: 'core/loading'
    };

    /** @property {number} The total items will be shown on the first load. */
    const DEFAULT_LAZY_LOADING_ITEMS_FIRST_LOAD = 5;
    /** @property {number} The total items will be shown when click on the Show more activities button. */
    const DEFAULT_LAZY_LOADING_ITEMS_OTHER_LOAD = 10;

    /**
     * Hide the content area and display the empty content message.
     *
     * @param {object} root The container element
     */
    var hideContent = function(root) {
        root.find(SELECTORS.EVENT_LIST_CONTENT).addClass('hidden');
        root.find(SELECTORS.EMPTY_MESSAGE).removeClass('hidden');
    };

    /**
     * Show the content area and hide the empty content message.
     *
     * @param {object} root The container element
     */
    var showContent = function(root) {
        root.find(SELECTORS.EVENT_LIST_CONTENT).removeClass('hidden');
        root.find(SELECTORS.EMPTY_MESSAGE).addClass('hidden');
    };

    /**
     * Empty the content area.
     *
     * @param {object} root The container element
     */
    var emptyContent = function(root) {
        root.find(SELECTORS.EVENT_LIST_CONTENT).empty();
    };

    /**
     * Construct the template context from a list of calendar events. The events
     * are grouped by which day they are on. The day is calculated from the user's
     * midnight timestamp to ensure that the calculation is timezone agnostic.
     *
     * The return data structure will look like:
     * {
     *      eventsbyday: [
     *          {
     *              dayTimestamp: 1533744000,
     *              events: [
     *                  { ...event 1 data... },
     *                  { ...event 2 data... }
     *              ]
     *          },
     *          {
     *              dayTimestamp: 1533830400,
     *              events: [
     *                  { ...event 3 data... },
     *                  { ...event 4 data... }
     *              ]
     *          }
     *      ]
     * }
     *
     * Each day timestamp is the day's midnight in the user's timezone.
     *
     * @param {array} calendarEvents List of calendar events
     * @return {object}
     */
    var buildTemplateContext = function(calendarEvents) {
        var eventsByDay = {};
        var templateContext = {
            courseview,
            eventsbyday: []
        };

        calendarEvents.forEach(function(calendarEvent) {
            var dayTimestamp = calendarEvent.timeusermidnight;
            if (eventsByDay[dayTimestamp]) {
                eventsByDay[dayTimestamp].push(calendarEvent);
            } else {
                eventsByDay[dayTimestamp] = [calendarEvent];
            }
        });

        Object.keys(eventsByDay).forEach(function(dayTimestamp) {
            var events = eventsByDay[dayTimestamp];
            templateContext.eventsbyday.push({
                dayTimestamp: dayTimestamp,
                events: events
            });
        });

        return templateContext;
    };

    /**
     * Render the HTML for the given calendar events.
     *
     * @param {array} calendarEvents  A list of calendar events
     * @return {promise} Resolved with HTML and JS strings.
     */
    var render = function(calendarEvents) {
        var templateContext = buildTemplateContext(calendarEvents);
        var templateName = TEMPLATES.EVENT_LIST_CONTENT;

        return Templates.render(templateName, templateContext);
    };

    /**
     * Retrieve a list of calendar events from the server for the given
     * constraints.
     *
     * @param {Number} midnight The user's midnight time in unix timestamp.
     * @param {Number} limit Limit the result set to this number of items
     * @param {Number} daysOffset How many days (from midnight) to offset the results from
     * @param {int|undefined} daysLimit How many dates (from midnight) to limit the result to
     * @param {int|false} lastId The ID of the last seen event (if any)
     * @param {int|undefined} courseId Course ID to restrict events to
     * @param {string|undefined} searchValue Search value
     * @return {Promise} A jquery promise
     */
    var load = function(midnight, limit, daysOffset, daysLimit, lastId, courseId, searchValue) {
        var startTime = midnight + (daysOffset * SECONDS_IN_DAY);
        var endTime = daysLimit != undefined ? midnight + (daysLimit * SECONDS_IN_DAY) : false;

        var args = {
            starttime: startTime,
            limit: limit,
        };

        if (lastId) {
            args.aftereventid = lastId;
        }

        if (endTime) {
            args.endtime = endTime;
        }

        if (searchValue) {
            args.searchvalue = searchValue;
        }

        if (courseId) {
            // If we have a course id then we only want events from that course.
            args.courseid = courseId;
            return CalendarEventsRepository.queryByCourse(args);
        } else {
            // Otherwise we want events from any course.
            return CalendarEventsRepository.queryByTime(args);
        }
    };

    /**
     * Create a lazy-loading region for the calendar events in the given root element.
     *
     * @param {object} root The event list container element.
     * @param {object} additionalConfig Additional config options to pass to pagedContentFactory.
     */
<<<<<<< HEAD
    var init = function(root, additionalConfig = {}) {
        const pendingPromise = new Pending('block/timeline:event-init');
=======
    var loadEventsFromPageData = function(
        pageData,
        actions,
        midnight,
        lastIds,
        preloadedPages,
        courseId,
        daysOffset,
        daysLimit
    ) {
        var pageNumber = pageData.pageNumber;
        var limit = pageData.limit;
        var lastPageNumber = pageNumber;

        // This is here to protect us if, for some reason, the pages
        // are loaded out of order somehow and we don't have a reference
        // to the previous page. In that case, scan back to find the most
        // recent page we've seen.
        while (!lastIds.hasOwnProperty(lastPageNumber)) {
            lastPageNumber--;
        }
        // Use the last id of the most recent page.
        var lastId = lastIds[lastPageNumber];
        var eventsPromise = null;

        if (preloadedPages && preloadedPages.hasOwnProperty(pageNumber)) {
            // This page has been preloaded so use that rather than load the values
            // again.
            eventsPromise = preloadedPages[pageNumber];
        } else {
            // Load one more than the given limit so that we can tell if there
            // is more content to load after this.
            eventsPromise = load(midnight, limit + 1, daysOffset, daysLimit, lastId, courseId);
        }

        return eventsPromise.then(function(result) {
            if (!result.events.length) {
                // If we didn't get any events back then tell the paged content
                // that we're done loading.
                actions.allItemsLoaded(pageNumber);
                return [];
            }

            var calendarEvents = result.events.filter(function(event) {
                if (event.eventtype == "open" || event.eventtype == "opensubmission") {
                    var dayTimestamp = UserDate.getUserMidnightForTimestamp(event.timesort, midnight);
                    return dayTimestamp > midnight;
                }
                return true;
            });
            // We expect to receive limit + 1 events back from the server.
            // Any less means there are no more events to load.
            var loadedAll = calendarEvents.length <= limit;

            if (loadedAll) {
                // Tell the pagination that everything is loaded.
                actions.allItemsLoaded(pageNumber);
            } else {
                // Remove the last element from the array because it isn't
                // needed in this result set.
                calendarEvents.pop();
            }

            return calendarEvents;
        });
    };

    /**
     * Use the paged content factory to create a paged content element for showing
     * the event list. We only provide a page limit to the factory because we don't
     * know exactly how many pages we'll need. This creates a paging bar with just
     * next/previous buttons.
     *
     * This function specifies the callback for loading the event data that the user
     * is requesting.
     *
     * @param {int|array} pageLimit A single limit or list of limits as options for the paged content
     * @param {object} preloadedPages An object of preloaded page data. Page number as key, data promise as value.
     * @param {Number} midnight The user's midnight time in unix timestamp.
     * @param {object} firstLoad A jQuery promise to be resolved after the first set of data is loaded.
     * @param {int|undefined} courseId Course ID to restrict events to
     * @param {Number} daysOffset How many days (from midnight) to offset the results from
     * @param {int|undefined} daysLimit How many dates (from midnight) to limit the result to
     * @param {string} paginationAriaLabel String to set as the aria label for the pagination bar.
     * @param {object} additionalConfig Additional config options to pass to pagedContentFactory
     * @return {object} jQuery promise.
     */
    var createPagedContent = function(
        pageLimit,
        preloadedPages,
        midnight,
        firstLoad,
        courseId,
        daysOffset,
        daysLimit,
        paginationAriaLabel,
        additionalConfig
    ) {
        // Remember the last event id we loaded on each page because we can't
        // use the offset value since the backend can skip events if the user doesn't
        // have the capability to see them. Instead we load the next page of events
        // based on the last seen event id.
        var lastIds = {'1': 0};
        var hasContent = false;
        var config = $.extend({}, DEFAULT_PAGED_CONTENT_CONFIG, additionalConfig);

        return Str.get_string(
                'ariaeventlistpagelimit',
                'block_timeline',
                $.isArray(pageLimit) ? pageLimit[0].value : pageLimit
            )
            .then(function(string) {
                config.ariaLabels.itemsperpage = string;
                config.ariaLabels.paginationnav = paginationAriaLabel;
                return string;
            })
            .then(function() {
                return PagedContentFactory.createWithLimit(
                    pageLimit,
                    function(pagesData, actions) {
                        var promises = [];

                        pagesData.forEach(function(pageData) {
                            var pageNumber = pageData.pageNumber;
                            // Load the page data.
                            var pagePromise = loadEventsFromPageData(
                                pageData,
                                actions,
                                midnight,
                                lastIds,
                                preloadedPages,
                                courseId,
                                daysOffset,
                                daysLimit
                            ).then(function(calendarEvents) {
                                if (calendarEvents.length) {
                                    // Remember that we've loaded content.
                                    hasContent = true;
                                    // Remember the last id we've seen.
                                    var lastEventId = calendarEvents[calendarEvents.length - 1].id;
                                    // Record the id that the next page will need to start from.
                                    lastIds[pageNumber + 1] = lastEventId;
                                    // Get the HTML and JS for these calendar events.
                                    return render(calendarEvents, midnight);
                                } else {
                                    return calendarEvents;
                                }
                            })
                            .catch(Notification.exception);

                            promises.push(pagePromise);
                        });

                        $.when.apply($, promises).then(function() {
                            // Tell the calling code that the first page has been loaded
                            // and whether it contains any content.
                            firstLoad.resolve(hasContent);
                            return;
                        })
                        .catch(function() {
                            firstLoad.resolve(hasContent);
                        });

                        return promises;
                    },
                    config
                );
            });
    };

    /**
     * Create a paged content region for the calendar events in the given root element.
     * The content of the root element are replaced with a new paged content section
     * each time this function is called.
     *
     * This function will be called each time the offset or limit values are changed to
     * reload the event list region.
     *
     * @param {object} root The event list container element
     * @param {int|array} pageLimit A single limit or list of limits as options for the paged content
     * @param {object} preloadedPages An object of preloaded page data. Page number as key, data promise as value.
     * @param {string} paginationAriaLabel String to set as the aria label for the pagination bar.
     * @param {object} additionalConfig Additional config options to pass to pagedContentFactory
     */
    var init = function(root, pageLimit, preloadedPages, paginationAriaLabel, additionalConfig) {
>>>>>>> upstream/MOODLE_38_STABLE
        root = $(root);

        courseview = !!additionalConfig.courseview;

        // Create a promise that will be resolved once the first set of page
        // data has been loaded. This ensures that the loading placeholder isn't
        // hidden until we have all of the data back to prevent the page elements
        // jumping around.
        var firstLoad = $.Deferred();
        var eventListContent = root.find(SELECTORS.EVENT_LIST_CONTENT);
        var loadingPlaceholder = root.find(SELECTORS.EVENT_LIST_LOADING_PLACEHOLDER);
        var courseId = root.attr('data-course-id');
        var daysOffset = parseInt(root.attr('data-days-offset'), 10);
        var daysLimit = root.attr('data-days-limit');
        var midnight = parseInt(root.attr('data-midnight'), 10);
        const searchValue = root.closest(SELECTORS.TIMELINE_BLOCK).find(SELECTORS.TIMELINE_SEARCH).val();

        // Make sure the content area and loading placeholder is visible.
        // This is because the init function can be called to re-initialise
        // an existing event list area.
        emptyContent(root);
        showContent(root);
        loadingPlaceholder.removeClass('hidden');

        // Days limit isn't mandatory.
        if (daysLimit != undefined) {
            daysLimit = parseInt(daysLimit, 10);
        }

        // Create the lazy loading content element.
        return createLazyLoadingContent(root, firstLoad,
            DEFAULT_LAZY_LOADING_ITEMS_FIRST_LOAD, midnight, 0, courseId, daysOffset, daysLimit, searchValue)
            .then(function(html, js) {
                firstLoad.then(function(data) {
                    if (!data.hasContent) {
                        loadingPlaceholder.addClass('hidden');
                        // If we didn't get any data then show the empty data message.
                        return hideContent(root);
                    }

                    html = $(html);
                    // Hide the content for now.
                    html.addClass('hidden');
                    // Replace existing elements with the newly created lazy-loading region.
                    Templates.replaceNodeContents(eventListContent, html, js);

                    // Prevent changing page elements too much by only showing the content
                    // once we've loaded some data for the first time. This allows our
                    // fancy loading placeholder to shine.
                    html.removeClass('hidden');
                    loadingPlaceholder.addClass('hidden');

                    if (!data.loadedAll) {
                        Templates.render(TEMPLATES.MORE_ACTIVITIES_BUTTON, {courseview}).then(function(html) {
                            eventListContent.append(html);
                            setLastTimestamp(root, data.lastTimeStamp);
                            // Init the event handler.
                            initEventListener(root);
                            return html;
                        }).catch(function() {
                            return false;
                        });
                    }

                    return data;
                })
                .catch(function() {
                    return false;
                });

                return html;
            }).then(() => {
                return pendingPromise.resolve();
            })
            .catch(Notification.exception);
    };

    /**
     * Create a lazy-loading content element for showing the event list for the initial load.
     *
     * @param {object} root The event list container element.
     * @param {object} firstLoad A jQuery promise to be resolved after the first set of data is loaded.
     * @param {int} itemLimit Limit the number of items.
     * @param {Number} midnight The user's midnight time in unix timestamp.
     * @param {int} lastId The last event ID for each loaded page. Page number is key, id is value.
     * @param {int|undefined} courseId Course ID to restrict events to.
     * @param {Number} daysOffset How many days (from midnight) to offset the results from.
     * @param {int|undefined} daysLimit How many dates (from midnight) to limit the result to.
     * @param {string|undefined} searchValue Search value.
     * @return {object} jQuery promise resolved with calendar events.
     */
    const createLazyLoadingContent = (root, firstLoad, itemLimit, midnight, lastId,
        courseId, daysOffset, daysLimit, searchValue) => {
        return loadEventsForLazyLoading(
            root,
            itemLimit,
            midnight,
            lastId,
            courseId,
            daysOffset,
            daysLimit,
            searchValue
        ).then(data => {
            if (data.calendarEvents.length) {
                const lastEventId = data.calendarEvents.at(-1).id;
                const lastTimeStamp = data.calendarEvents.at(-1).timeusermidnight;
                firstLoad.resolve({
                    hasContent: true,
                    lastId: lastEventId,
                    lastTimeStamp: lastTimeStamp,
                    loadedAll: data.loadedAll
                });
                return render(data.calendarEvents, midnight);
            } else {
                firstLoad.resolve({
                    hasContent: false,
                    lastId: 0,
                    lastTimeStamp: 0,
                    loadedAll: true
                });
                return data.calendarEvents;
            }
        }).catch(Notification.exception);
    };

    /**
     * Handle the request from the lazy-loading region.
     * Uses the given data like course id, offset... to request the events from the server.
     *
     * @param {object} root The event list container element.
     * @param {int} itemLimit Limit the number of items.
     * @param {Number} midnight The user's midnight time in unix timestamp.
     * @param {int} lastId The last event ID for each loaded page.
     * @param {int|undefined} courseId Course ID to restrict events to.
     * @param {Number} daysOffset How many days (from midnight) to offset the results from.
     * @param {int|undefined} daysLimit How many dates (from midnight) to limit the result to.
     * @param {string|undefined} searchValue Search value.
     * @return {object} jQuery promise resolved with calendar events.
     */
    const loadEventsForLazyLoading = (root, itemLimit, midnight, lastId, courseId, daysOffset, daysLimit, searchValue) => {
        // Load one more than the given limit so that we can tell if there
        // is more content to load after this.
        const eventsPromise = load(midnight, itemLimit + 1, daysOffset, daysLimit, lastId, courseId, searchValue);
        let calendarEvents = [];
        let loadedAll = true;

        return eventsPromise.then(result => {
            if (!result.events.length) {
                return {calendarEvents, loadedAll};
            }

            // Determine if the overdue filter is applied.
            const overdueFilter = document.querySelector("[data-filtername='overdue']");
            const filterByOverdue = (overdueFilter && overdueFilter.getAttribute('aria-current'));

            calendarEvents = result.events.filter(event => {
                if (event.eventtype == 'open' || event.eventtype == 'opensubmission') {
                    const dayTimestamp = UserDate.getUserMidnightForTimestamp(event.timesort, midnight);
                    return dayTimestamp > midnight;
                }
                // When filtering by overdue, we fetch all events due today, in case any have elapsed already and are overdue.
                // This means if filtering by overdue, some events fetched might not be required (eg if due later today).
                return (!filterByOverdue || event.overdue);
            });

            loadedAll = calendarEvents.length <= itemLimit;

            if (!loadedAll) {
                // Remove the last element from the array because it isn't
                // needed in this result set.
                calendarEvents.pop();
            }

            if (calendarEvents.length) {
                const lastEventId = calendarEvents.at(-1).id;
                setOffset(root, lastEventId);
            }

            return {calendarEvents, loadedAll};
        });
    };

    /**
     * Load new events and append to current list.
     *
     * @param {object} root The event list container element.
     */
    const loadMoreEvents = root => {
        const midnight = parseInt(root.attr('data-midnight'), 10);
        const courseId = root.attr('data-course-id');
        const daysOffset = parseInt(root.attr('data-days-offset'), 10);
        const daysLimit = root.attr('data-days-limit');
        const lastId = getOffset(root);
        const eventListWrapper = root.find(SELECTORS.EVENT_LIST_WRAPPER);
        const searchValue = root.closest(SELECTORS.TIMELINE_BLOCK).find(SELECTORS.TIMELINE_SEARCH).val();
        const eventsPromise = loadEventsForLazyLoading(
            root,
            DEFAULT_LAZY_LOADING_ITEMS_OTHER_LOAD,
            midnight,
            lastId,
            courseId,
            daysOffset,
            daysLimit,
            searchValue
        );
        eventsPromise.then(data => {
            if (data.calendarEvents.length) {
                const renderPromise = render(data.calendarEvents);
                const lastTimestamp = getLastTimestamp(root);
                renderPromise.then((html, js) => {
                    html = $(html);

                    // Remove the date heading if it has the same value as the previous one.
                    html.find(`[data-timestamp="${lastTimestamp}"]`).remove();
                    Templates.appendNodeContents(eventListWrapper, html.html(), js);

                    if (!data.loadedAll) {
                        Templates.render(TEMPLATES.MORE_ACTIVITIES_BUTTON, {}).then(html => {
                            eventListWrapper.append(html);
                            setLastTimestamp(root, data.calendarEvents.at(-1).timeusermidnight);
                            // Init the event handler.
                            initEventListener(root);

                            return html;
                        }).catch(() => {
                            return false;
                        });
                    }

                    return html;
                }).catch(Notification.exception);
            }

            return data;
        }).then(() => {
            return disableMoreActivitiesButtonLoading(root);
        }).catch(Notification.exception);
    };

    /**
     * Return the offset value for lazy loading fetching.
     *
     * @param {object} element The event list container element.
     * @return {Number} Offset value.
     */
    const getOffset = element => {
        return parseInt(element.attr('data-lazyload-offset'), 10);
    };

    /**
     * Set the offset value for lazy loading fetching.
     *
     * @param {object} element The event list container element.
     * @param {Number} offset Offset value.
     */
    const setOffset = (element, offset) => {
        element.attr('data-lazyload-offset', offset);
    };

    /**
     * Return the timestamp value for lazy loading fetching.
     *
     * @param {object} element The event list container element.
     * @return {Number} Timestamp value.
     */
    const getLastTimestamp = element => {
        return parseInt(element.attr('data-timestamp'), 10);
    };

    /**
     * Set the timestamp value for lazy loading fetching.
     *
     * @param {object} element The event list container element.
     * @param {Number} timestamp Timestamp value.
     */
    const setLastTimestamp = (element, timestamp) => {
        element.attr('data-timestamp', timestamp);
    };

    /**
     * Add the "Show more activities" button and remove and loading spinner.
     *
     * @param {object} root The event list container element.
     */
    const enableMoreActivitiesButtonLoading = root => {
        const loadMoreButton = root.find(SELECTORS.MORE_ACTIVITIES_BUTTON);
        loadMoreButton.prop('disabled', true);
        Templates.render(TEMPLATES.LOADING_ICON, {}).then(html => {
            loadMoreButton.append(html);
            return html;
        }).catch(() => {
            // It's not important if this false so just do so silently.
            return false;
        });
    };

    /**
     * Remove the "Show more activities" button and remove and loading spinner.
     *
     * @param {object} root The event list container element.
     */
    const disableMoreActivitiesButtonLoading = root => {
        const loadMoreButtonContainer = root.find(SELECTORS.MORE_ACTIVITIES_BUTTON_CONTAINER);
        loadMoreButtonContainer.remove();
    };

    /**
     * Event initialise.
     *
     * @param {object} root The event list container element.
     */
    const initEventListener = root => {
        const loadMoreButton = root.find(SELECTORS.MORE_ACTIVITIES_BUTTON);
        loadMoreButton.on('click', () => {
            enableMoreActivitiesButtonLoading(root);
            loadMoreEvents(root);
        });
    };

    return {
        init: init,
        rootSelector: SELECTORS.ROOT,
    };
});

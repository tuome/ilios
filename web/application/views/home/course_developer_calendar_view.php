<?php
/**
 * Instructor calendar page template.
 */
$siteUrl = site_url();
$controllerURL = $siteUrl . '/calendar_controller';
$dashboardURL = $siteUrl . '/dashboard_controller';
$courseManagementURL = $siteUrl . '/course_management';
$learningMaterialsControllerURL = $siteUrl . '/learning_materials';
$programManagementURL = $siteUrl . '/program_management';
$managementConsoleURL = $siteUrl . '/management_console';
$viewsUrlRoot = getViewsURLRoot();
$viewsPath = getServerFilePath('views');
?><!doctype html>
<!-- paulirish.com/2008/conditional-stylesheets-vs-css-hacks-answer-neither/ -->
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" lang="en"> <![endif]-->
<!-- Consider adding a manifest.appcache: h5bp.com/d/Offline -->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en"> <!--<![endif]-->
<head>
    <meta charset="utf-8">

    <!-- Use the .htaccess and remove these lines to avoid edge case issues.
        More info: h5bp.com/i/378 -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

    <title><?php echo $title_bar_string; ?></title>
    <meta name="description" content="">

    <!-- Mobile viewport optimized: h5bp.com/viewport -->
    <meta name="viewport" content="width=device-width">

    <!-- Place favicon.ico and apple-touch-icon.png in the root directory: mathiasbynens.be/notes/touch-icons -->
    <link rel="stylesheet" href="<?php echo appendRevision($viewsUrlRoot . "css/ilios-styles.css"); ?>" media="all">
    <link rel="stylesheet" href="<?php echo appendRevision($viewsUrlRoot . "css/session-types.css"); ?>" media="all">
    <link rel="stylesheet" href="<?php echo appendRevision($viewsUrlRoot . "css/custom.css"); ?>" media="all">

    <style type="text/css"></style>
    <!-- More ideas for your <head> here: h5bp.com/d/head-Tips -->

    <!-- All JavaScript at the bottom, except this Modernizr build.
         Modernizr enables HTML5 elements & feature detects for optimal performance.
         Create your own custom Modernizr build: www.modernizr.com/download/ -->
    <script type="text/javascript" src="<?php echo $viewsUrlRoot; ?>scripts/third_party/modernizr-2.5.3.min.js"></script>

    <!-- Third party JS -->
    <script type="text/javascript" src="<?php echo appendRevision($viewsUrlRoot . "scripts/third_party/yui_kitchensink.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo appendRevision($viewsUrlRoot . "scripts/third_party/date_formatter.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo appendRevision($viewsUrlRoot . "scripts/third_party/md5-min.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo appendRevision($viewsUrlRoot . "scripts/third_party/dhtmlx/dhtmlxscheduler.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo appendRevision($viewsUrlRoot . "scripts/third_party/dhtmlx/ext/dhtmlxscheduler_recurring.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo appendRevision($viewsUrlRoot . "scripts/third_party/dhtmlx/ext/dhtmlxscheduler_agenda_view.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo appendRevision($viewsUrlRoot . "scripts/third_party/idle-timer.js"); ?>"></script>

        <!-- Ilios JS -->
    <script type="text/javascript" src="<?php echo $controllerURL . "/getI18NJavascriptVendor?lang=" . $lang; ?>"></script>
    <script type="text/javascript" src="<?php echo appendRevision($viewsUrlRoot . "scripts/ilios_base.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo appendRevision($viewsUrlRoot . "scripts/ilios_utilities.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo appendRevision($viewsUrlRoot . "scripts/ilios_ui.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo appendRevision($viewsUrlRoot . "scripts/ilios_dom.js"); ?>"></script>
    <script type="text/javascript">
        var baseURL = "<?php echo $siteUrl; ?>/";
        var controllerURL = "<?php echo $controllerURL; ?>/";    // expose this to our javascript land
        var courseManagementURL = "<?php echo $courseManagementURL; ?>/";       // similarly...
        var learningMaterialsControllerURL = "<?php echo $learningMaterialsControllerURL; ?>/";    // ...
        var programManagementURL = "<?php echo $programManagementURL; ?>/";     // similarly...

        var pageLoadedForStudent = false;
        var isCalendarView = true;

        ilios.namespace('home');        // assure the existence of this page's namespace
    </script>
    <script type="text/javascript" src="<?php echo appendRevision($viewsUrlRoot . "scripts/models/abstract_js_model_form.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo appendRevision($viewsUrlRoot . "scripts/models/preferences_model.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo appendRevision($viewsUrlRoot . "scripts/models/competency_model.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo appendRevision($viewsUrlRoot . "scripts/models/school_competency_model.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo appendRevision($viewsUrlRoot . "scripts/models/discipline_model.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo appendRevision($viewsUrlRoot . "scripts/models/course_model.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo appendRevision($viewsUrlRoot . "scripts/models/simplified_group_model.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo appendRevision($viewsUrlRoot . "scripts/models/independent_learning_model.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo appendRevision($viewsUrlRoot . "scripts/models/learning_material_model.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo appendRevision($viewsUrlRoot . "scripts/models/mesh_item_model.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo appendRevision($viewsUrlRoot . "scripts/models/objective_model.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo appendRevision($viewsUrlRoot . "scripts/models/offering_model.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo appendRevision($viewsUrlRoot . "scripts/models/program_cohort_model.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo appendRevision($viewsUrlRoot . "scripts/models/session_model.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo appendRevision($viewsUrlRoot . "scripts/models/user_model.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo appendRevision($viewsUrlRoot . "scripts/competency_base_framework.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo appendRevision($viewsUrlRoot . "scripts/course_model_support_framework.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo appendRevision($viewsUrlRoot . "scripts/learner_view_base_framework.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo appendRevision($viewsUrlRoot . "scripts/public_course_summary_base_framework.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo appendRevision($viewsUrlRoot . "home/calendar_item_model.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo appendRevision($viewsUrlRoot . "home/dashboard_calendar_support.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo appendRevision($viewsUrlRoot . "home/dashboard_dom.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo appendRevision($viewsUrlRoot . "home/dashboard_transaction.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo appendRevision($viewsUrlRoot . "home/reminder_model.js"); ?>"></script>

</head>

<body class="home yui-skin-sam">
    <div id="wrapper">
        <header id="masthead" class="clearfix headless">
<?php include_once $viewsPath . 'common/masthead_viewbar.inc.php'; ?>
        </header>
        <div id="main" class="headless" role="main">

            <div id="content" class="dashboard clearfix">
                <div class="content_container">
                    <div class="column primary clearfix">
                        <h3><?php echo $my_calendar_string; ?></h3>
<?php
    if (!$render_headerless && $show_view_switch) :
?>
                                    <a href="<?php echo $controllerURL; ?>/switchView?preferred_view=student" id="role_switch" class="tiny secondary radius button">
                                        <?php echo $switch_to_student_view_string; ?>
                                    </a>
<?php
    endif;
?>
                        <div class="calendar_tools clearfix">
                           <?php include $viewsPath . 'common/progress_div.php';
                                echo generateProgressDivMarkup('position:absolute; left: 25%;float:none;margin:0;');
                           ?>
                            <ul class="buttons right">
                                <li>
                                    <span id="calendar_filters_btn" title="<?php echo $calendar_filters_title; ?>" class="medium radius button">
                                        <span class="icon-search icon-alone"></span>
                                        <span class="screen-reader-text"><?php echo $calendar_filters_btn; ?></span>
                                    </span>
                                </li>
                                <li>
                                    <a href="<?php echo $siteUrl; ?>/calendar_exporter/exportICalendar/instructor" class="medium radius button" title="<?php echo $ical_download_title; ?>">
                                        <span class="icon-download icon-alone"></span>
                                        <span class="screen-reader-text"><?php echo $ical_download_button; ?></span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div id="calendar_filters_breadcrumb_block">
                            <div class="calendar_filter_titlebar">
                                <span id="calendar_filters_clear_search_link" class="icon-cancel" title="<?php echo $calendar_clear_search_filters; ?>"></span>
                                <span class="calendar_filters_breadcrumb_title"> <?php echo $calendar_search_mode_title; ?> : </span>
                            </div>
                            <span id="calendar_filters_breadcrumb_content"></span>
                        </div>
                        <div id="dhtmlx_scheduler_container" class="dhx_cal_container" style="position: absolute; top: 8em; bottom: 0; width: 99.8%; height: auto; float: none;">
                            <div class="dhx_cal_navline">
                                <div class="dhx_cal_prev_button">&nbsp;</div>
                                <div class="dhx_cal_next_button">&nbsp;</div>
                                <div class="dhx_cal_today_button"></div>
                                <div class="dhx_cal_date"></div>
                                <div class="dhx_cal_tab" name="day_tab" style="right:209px;"></div>
                                <div class="dhx_cal_tab" name="week_tab" style="right:145px;"></div>
                                <div class="dhx_cal_tab" name="month_tab" style="right:81px;"></div>
                                <div class="dhx_cal_tab" name="agenda_tab" style="right:17px;"></div>
                            </div>
                            <div class="dhx_cal_header"></div>
                            <div class="dhx_cal_data" id="dhx_cal_data"></div>
                        </div>
                        <!-- <div id="offering_summary_table_div" class="offering_summary_calendar_table"></div> -->
                    </div><!--end .primary.column -->

                    <div class="column secondary clearfix">
                        <div style="display: none;">
                            <label for="search_ilios_text"><?php echo $phrase_search_ilios_string; ?>:</label><br />
                            <input type="text" name="search_ilios_text" id="search_ilios_text" style="width: 200px; margin-bottom: 6px;" /><br />
                            <a href="" onclick="return false;" style="font-size: 9pt;"><?php echo $phrase_advanced_search_string; ?></a>
                        </div>

                        <div id="dashboard_calendar_filters_content">
<?php
    require_once $viewsPath .'common/calendar_filters_include.php';
    echo generateCalendarFiltersFormContent($calendar_filters_data);
?>
                        </div>

                        <div id="dashboard_inspector_content"></div>

                    </div><!--end .secondary.column -->
                </div><!--end .content_container -->
	     </div>
        </div>
    </div>
    <footer>
    <!-- reserve for later use -->
    </footer>

    <!-- overlays at the bottom - avoid z-index issues -->
    <div id="view-menu"></div>
<?php
    include $viewsPath . 'common/course_summary_view_include.php';
?>

    <script type="text/javascript">
        // register alert/inform overrides on window load
        YAHOO.util.Event.on(window, 'load', function() {
            window.alert = ilios.alert.alert;
            window.inform = ilios.alert.inform;
        });
<?php
    generateJavascriptRepresentationCodeOfPHPArray($preference_array, 'dbObjectRepresentation', false);
?>
        ilios.global.installPreferencesModel();
        ilios.global.preferencesModel.updateWithServerDispatchedObject(dbObjectRepresentation);

<?php include_once $viewsPath . 'common/load_school_competencies.inc.php'; ?>

        YAHOO.util.Event.onDOMReady(ilios.home.calendar.initCalendar);
        YAHOO.util.Event.onDOMReady(ilios.home.transaction.loadAllOfferings);
        YAHOO.util.Event.onDOMReady(ilios.home.calendar.initFilterHooks);

    </script>
</body>
</html>
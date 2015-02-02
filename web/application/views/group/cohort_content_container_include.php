<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @file cohort_content_container_include.php
 *
 * Includes template.
 * This view-template renders the "program and cohort" content container onto the group management page.
 *
 */
?>
<!-- content_container start -->
<div class="content_container">
    <div class="master_button_container clearfix">
        <ul class="buttons left">
            <li>
                <a id="find_cohort_and_program" href="" class="small radius button"
                   onClick="ilios.ui.onIliosEvent.fire({action: 'sac_dialog_open', event: 'find_cohort_and_program'}); return false;">
                    <?php echo t('groups.select_program'); ?></a>
            </li>
        </ul>
        <ul class="buttons right">
            <li>
                <button id="save_all_dirty_to_draft" class="medium radius button" disabled='disabled'><?php echo t('general.phrases.save_all'); ?></button>
            </li>
        </ul>
    </div>
    <form id="cohort_form" method="POST" action="http://ilios.local/ilios.php/group_management/willNeverSubmit"
          onsubmit="return false;">
        <div class="entity_container level-1">
            <div class="hd clearfix">
                <div class="toggle">
                    <a href="#" id="show_more_or_less_link"
                       onclick="ilios.utilities.toggle('course_more_or_less_div', this); return false;" >
                        <i class="icon-plus" aria-hidden = "true"> </i>
                    </a>
                </div>
                <ul>

                    <li class="title">
                        <span class="data-type"><?php echo t('groups.program_title'); ?></span>
                        <span class="data" id="program_cohort_title"></span>
                    </li>
                    <li class="short-title">
                        <span class="data-type"><?php echo t('general.phrases.program_title_short'); ?></span>
                        <span class="data" id="program_title_short"></span>
                    </li>
                    <li class="enrollment">
                        <span class="data-type"><?php echo t('general.phrases.current_enrollment'); ?></span>
                        <span class="data" id="current_enrollment"></span>
                    </li>
                </ul>
            </div>
            <div id="course_more_or_less_div" class="bd" style="display:none">


            </div><!--close div.bd-->
        </div><!-- entity_container close -->
    </form>
    <button class="small secondary radius button"
            disabled="disabled" id="all_edit_member_link"
            onClick="ilios.ui.onIliosEvent.fire({action: 'em_dialog_open', event: 'add_new_members_picker_show_dialog', container_number: '-1'});">
        <?php echo t('groups.add_new_to_all_group'); ?>
    </button>

    <div class="collapse_children_toggle_link">
        <button class="small secondary radius button"
                onclick="ilios.gm.subgroup.changeBreadcrumbViewLevelOrSidestep('-1'); return false;"
                id="open_cohort" style="display: none;"><?php echo t('groups.open_cohort'); ?></button>
        <button class="small secondary radius button groups_collapsed" onclick="ilios.gm.collapseOrExpandGroups(false, false); return false;"
                id="expand_groups_link"
                style="display: none;"><?php echo t('general.phrases.expand_all'); ?></button>
    </div>

    <div style="clear: both;"></div>

    <div id="breadcrumb_group_trail"></div>
    <div id="group_container"></div>
    <div id="breadcrumbed_suffixed_group_trail"></div>


    <div class="add_primary_child_link">
        <button class="small secondary radius button" onclick="ilios.gm.transaction.handleManualGroupAdd();"
                id="general_new_add_group_link" disabled="disabled"><?php echo t('groups.add_new_group'); ?></button>
    </div>
</div>
<!-- content_container end -->

<div class="tabdialog" id="empty_group_add_dialog">
    <div class="hd" id="empty_group_add_dialog_title"></div>
    <div class="bd" style="position: relative;">
        <div class="dialog_wrap">
            <p>
            <?php echo t('groups.empty_group_dialog.text'); ?>
            </p>
            <ul class="no-bullets margin-l">
                <li>
                    <input type="radio" name="ega_radio" id="ega_radio_yes" checked
                        onclick="ilios.ui.radioButtonSelected(this);"/>
                    <label for="ega_radio_yes" id="ega_radio_yes_label">
                        <?php echo t('groups.empty_group_dialog.yes'); ?>
                    </label>
                </li>
                <li>
                    <input type="radio" name="ega_radio" id="ega_radio_no"
                        onclick="ilios.ui.radioButtonSelected(this);"/>
                    <label for="ega_radio_no" id="ega_radio_no_label"
                        style="font-weight: normal;">
                        <?php echo t('groups.empty_group_dialog.no'); ?>
                    </label>
                </li>
            </ul>
        </div>
    </div>
    <div class="ft"></div>
</div> <!-- end #empty_group_add_dialog -->

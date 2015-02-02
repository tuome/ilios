ilios.namespace('common.lm');

ilios.common.lm.learningMaterialsDetailsDialog = null;
ilios.common.lm.learningMaterialsDetailsModel = null;

// @private
ilios.common.lm.buildLearningMaterialLightboxDOM = function () {
    var Event = YAHOO.util.Event;
    var element = null;

    var handleCancel = function () {

        //check to see if the learningMaterialDetailsModel is dirty
        if(ilios.common.lm.learningMaterialsDetailsModel.isDirty){
            //if it's dirty, it has changed, so add the update learning material process here
            var cnumber = this.cnumber;
            var lmnumber = this.lmnumber;
            //initiate the update
            ilios.cm.transaction.updateLearningMaterial(cnumber, lmnumber);
            //then close the dialog
            this.cancel();
        } else {
            //it hasn't changed, so do nothing and just close the dialog
            this.cancel();
        }

    };

    //set the text of the 'save' button to 'Done'
    var doneStr = ilios_i18nVendor.getI18NString('general.terms.done');

/*
* There is a save modification case, when the LM author is the present authenticated user -- TODO

    var handleSave = function () {
        validateLightboxSave(this);
    };
    var saveStr = ilios_i18nVendor.getI18NString('general.terms.save');
    var buttonArray = [{text: saveStr, handler: handleSave, isDefault: true},
                       {text: doneStr, handler: handleCancel}];
*/

    var buttonArray = [{text: doneStr, handler: handleCancel}];

    var panelWidth = "620px";
    var dialog = new YAHOO.widget.Dialog('ilios_learning_material_lightbox',
                                         {width: panelWidth, modal: true, visible: false,
                                          constraintoviewport: false, buttons: buttonArray});
    var displayOnTriggerHandler = null;

    dialog.showDialogPane = function () {
        ilios.common.lm.populateLearningMaterialMetadataDialog(
                                        ilios.common.lm.learningMaterialsDetailsModel);

        dialog.center();
        dialog.show();
    };

    // Render the Dialog
    dialog.render();

    displayOnTriggerHandler = function (type, handlerArgs) {
        if (handlerArgs[0].action == 'lm_metadata_dialog_open') {
            dialog.cnumber = handlerArgs[0].cnumber;
            dialog.lmnumber = handlerArgs[0].lmnumber;
            dialog.showDialogPane();
        }
    };
    ilios.ui.onIliosEvent.subscribe(displayOnTriggerHandler);

    ilios.common.lm.learningMaterialsDetailsDialog = dialog;

    element = document.getElementById('ilios_lm_required_checkbox');
    Event.addListener(element, 'click', function (e) {
        var toggle = (! ilios.common.lm.learningMaterialsDetailsModel.isRequired());
        var containerNumber = ilios.common.lm.learningMaterialsDetailsDialog.cnumber;
        var model = null;

        if (containerNumber == -1) {
            model = ilios.cm.currentCourseModel;
        } else {
            model = ilios.cm.currentCourseModel.getSessionForContainer(containerNumber);
        }

        ilios.common.lm.learningMaterialsDetailsModel.setRequired(toggle);

        //instead of setting the course/session to dirty and notifying the listeners, now that the lm
        // is decoupled, let's just set the learning material model to dirty, so we can test it for
        // changes when closing the lm dialog.
        ilios.common.lm.learningMaterialsDetailsModel.setDirty();
    });

    element = document.getElementById('ilios_lm_mesh_link');
    Event.addListener(element, 'click', function (e) {
        ilios.ui.onIliosEvent.fire({
            action: 'mesh_picker_dialog_open',
            model_in_edit: ilios.common.lm.learningMaterialsDetailsModel
        });
        return false;
    });

    element = document.getElementById('ilios_lm_notes_link');
    Event.addListener(element, 'click', function (e) {
        ilios.ui.onIliosEvent.fire({
            action: 'elmn_dialog_open',
            model_in_edit: ilios.common.lm.learningMaterialsDetailsModel
        });
        return false;
    });
};

YAHOO.util.Event.onDOMReady(ilios.common.lm.buildLearningMaterialLightboxDOM);


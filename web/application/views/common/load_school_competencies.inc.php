<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @file load_school_competencies.inc.php
 * 
 * Includes template.
 * 
 * Populates the school competencies map on page load.
 * Put this inside the javascript-block at the bottom of each page template where applicable.
 *
 * Expects the following template variables to be present:
 *
 *     $school_competencies ... a JSON-formatted representation of competencies/subdomains, 
 *                              grouped by their owning schools.
 */
?>

// load school competencies
YAHOO.util.Event.onDOMReady(function () {
    var s, map;
    var jsonStr = '<?php echo $school_competencies; ?>';

    try {
        s = YAHOO.lang.JSON.parse(jsonStr);
        map = ilios.competencies.convertSchoolCompetencyHierarchiesIntoLookupMap(s);
    } catch (e) {
        ilios.global.defaultAJAXFailureHandler(null, e);
        return;
    }

    if (map !== undefined) {
        ilios.competencies.setSchoolCompetencies(map);
    }
});

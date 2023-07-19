<?php
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
 * Atto text editor integration version file.
 *
 * @package    atto_bunnybutton
 * @copyright  bunny 2009 - 2016
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Initialize this plugin
 */
function atto_bunnybutton_strings_for_js() {

    global $PAGE;

    $PAGE->requires->strings_for_js(
        array(
            'insert',
            'cancel',
            'dialogtitle'
        ),
        'atto_bunnybutton'
    );
}

/**
 * Return the js params required for this module.
 *
 * @param int $elementid
 * @param array $options
 * @param array $fpoptions
 * @return array of additional params to pass to javascript init function for this module.
 */
function atto_bunnybutton_params_for_js($elementid, $options, $fpoptions) {
    global $USER, $COURSE, $DB;

    $coursecontext = context_course::instance($COURSE->id);

    // Gets bunny folder ID and for course from database on the server to which the course was provisioned.
    // If the course has not been provisioned, this will not return a value and the user will be able to select
    //  folders and videos from the server specified as default during the plugin setup.
    $bunnyid = $DB->get_field('block_bunny_foldermap', 'bunny_id', array('moodleid' => $coursecontext->instanceid));
    $servername = $DB->get_field('block_bunny_foldermap', 'bunny_server', array('moodleid' => $coursecontext->instanceid));
    $instancename = get_config('block_bunny', 'instance_name');

    $usercontextid = context_user::instance($USER->id)->id;
    $disabled = false;

    // Config array.
    $params = array();
    $params['usercontextid'] = $usercontextid;
    $params['coursecontext'] = $bunnyid;
    $params['servename'] = $servername;

    $params['instancename'] = $instancename;

    // Add disabled param.
    $params['disabled'] = true;

    // Add our default server.
    $params['defaultserver'] = get_config('atto_bunnybutton', 'defaultserver');

    return $params;
}

function uploadFile($localPath, $path, $apiAccessKey, $baseurl) {
    // Open the local file
    $fileStream = fopen($localPath, "r");
    if ($fileStream == false) {
        throw new Exception("The local file could not be opened.");
    }
    $dataLength = filesize($localPath);
    return sendHttpRequest($path, "PUT", $fileStream, $dataLength, null, $apiAccessKey, $baseurl);
}
function sendHttpRequest($url, $method = "GET", $uploadFile = NULL, $uploadFileSize = NULL, $downloadFileHandler = NULL, $apiAccessKey, $baseurl) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseurl . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
    curl_setopt($ch, CURLOPT_FAILONERROR, 0);

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "AccessKey: {$apiAccessKey}",
    ));
    if ($method == "PUT" && $uploadFile != NULL) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_UPLOAD, 1);
        curl_setopt($ch, CURLOPT_INFILE, $uploadFile);
        curl_setopt($ch, CURLOPT_INFILESIZE, $uploadFileSize);
    } else if ($method != "GET") {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    }


    if ($method == "GET" && $downloadFileHandler != NULL) {
        curl_setopt($ch, CURLOPT_FILE, $downloadFileHandler);
    }


    $output = curl_exec($ch);
    $curlError = curl_errno($ch);
    $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlError) {
        throw new Exception("An unknown error has occured during the request. Status code: " . $curlError);
    }

    if ($responseCode == 404) {
        throw new Exception($url);
    } else if ($responseCode == 401) {
        throw new Exception($apiAccessKey);
    } else if ($responseCode < 200 || $responseCode > 299) {
        throw new Exception("An unknown error has occured during the request. Status code: " . $responseCode);
    }

    return $output;
}

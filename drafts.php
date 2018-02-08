<?php

require_once('../../config.php');
require_once 'lib.php';

$page_url = '/blocks/quickmail/drafts.php';

$page_params = [
    'courseid' => optional_param('courseid', 0, PARAM_INT),
    'duplicateid' => optional_param('duplicateid', 0, PARAM_INT),
];

////////////////////////////////////////
/// AUTHENTICATION
////////////////////////////////////////

require_login();
$page_context = context_system::instance();
$PAGE->set_context($page_context);
$PAGE->set_url(new moodle_url($page_url, $page_params));
block_quickmail_plugin::require_user_capability('cansend', $page_context);

////////////////////////////////////////
/// CONSTRUCT PAGE
////////////////////////////////////////

$PAGE->set_pagetype('block-quickmail');
$PAGE->set_pagelayout('standard');
$PAGE->set_title(block_quickmail_plugin::_s('pluginname') . ': ' . block_quickmail_plugin::_s('drafts'));
$PAGE->navbar->add(block_quickmail_plugin::_s('pluginname'));
$PAGE->navbar->add(block_quickmail_plugin::_s('drafts'));
$PAGE->set_heading(block_quickmail_plugin::_s('pluginname') . ': ' . block_quickmail_plugin::_s('drafts'));
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/blocks/quickmail/style.css'));
$PAGE->requires->js_call_amd('block_quickmail/draft-index', 'init');

$renderer = $PAGE->get_renderer('block_quickmail');

// handle draft duplication, if necessary

// if ($page_params['duplicateid']) {
//     try {
//         // attempt to duplicate the draft
//         \block_quickmail\drafter\drafter::duplicate_draft_for_user_id($page_params['duplicateid'], $USER->id);

//         // redirect back to this page
//         redirect(new \moodle_url('/blocks/quickmail/drafts.php', ['courseid' => $page_params['courseid']]), 'Your draft has been successfully duplicated.');
//     } catch (\block_quickmail\drafter\exceptions\drafter_authentication_exception $e) {
//         print_error('no_permission', 'block_quickmail');
//     } catch (\block_quickmail\drafter\exceptions\drafter_critical_exception $e) {
//         print_error('critical_error', 'block_quickmail');
//     }
// }

////////////////////////////////////////
/// INSTANTIATE FORM
////////////////////////////////////////

$manage_drafts_form = \block_quickmail\forms\manage_drafts_form::make(
    $page_context, 
    $USER, 
    $page_params['courseid']
);

////////////////////////////////////////
/// HANDLE REQUEST
////////////////////////////////////////

$request = block_quickmail_request::for_route('draft')->with_form($manage_drafts_form);

////////////////////////////////////////
/// HANDLE DELETE REQUEST
////////////////////////////////////////
if ($request->to_delete_draft()) {
    
    // attempt to fetch the draft message
    if ( ! $draft_message = block_quickmail\persistents\message::find_user_draft_or_null($request->data->delete_draft_id, $USER->id)) {
        // redirect and notify of error
        $request->redirect_as_error(block_quickmail_plugin::_s('draft_no_record'), $page_url, ['courseid' => $page_params['courseid']]);
    }

    // attempt to soft delete draft
    $draft_message->soft_delete();
}

// get all (unsent) message drafts belonging to this user and course
$draft_messages = block_quickmail\persistents\message::get_all_unsent_drafts_for_user($USER->id, $page_params['courseid']);

$rendered_draft_message_index = $renderer->draft_message_index_component([
    'draft_messages' => $draft_messages,
    'user' => $USER,
    'course_id' => $page_params['courseid'],
]);

$rendered_manage_drafts_form = $renderer->manage_drafts_component([
    'manage_drafts_form' => $manage_drafts_form,
]);

echo $OUTPUT->header();
echo $rendered_draft_message_index;
echo $rendered_manage_drafts_form;
echo $OUTPUT->footer();
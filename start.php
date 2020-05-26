<?php
/* For license terms, see /license.txt */
 
require_once __DIR__.'/../../main/inc/global.inc.php';
require_once __DIR__.'/config.php';

$student = api_is_student();
$teacher = api_is_teacher();
$userId = api_get_user_id();
$is_admin = api_is_platform_admin();

$is_allowed_to_edit = api_is_allowed_to_edit(false, true, false, false);

api_protect_course_script(true);
$zoomplugin = ZoomApiPlugin::create();
$em = Database::getManager();
$show_record_only_for_admin = $zoomplugin->get('show_record_only_for_admin');
$pageTitle = $zoomplugin->get_lang('plugin_title');

if (!$pageTitle) {
    api_not_allowed(true);
}
 
$zoomlib = new ZoomApi();

$interbreadcrumb[] = [
        'url' => api_get_self().'?'.api_get_cidreq(true, false),
        'name' => $pageTitle,
    ];

$htmlHeadXtra[] = '<script type="text/javascript">
	$(document).ready(function () {
		$("#horareuniao").datetimepicker({
			altField: "#horareuniao2",
			altFieldTimeOnly: false,
			altFormat: "yy-mm-dd",
			altTimeFormat: "HH:mm:ss",
			altSeparator: "T"
		});
		$("#show_hide_password a").on("click", function(event) {
	        event.preventDefault();
	        if($("#show_hide_password input").attr("type") == "text"){
	            $("#show_hide_password input").attr("type", "password");
	            $("#show_hide_password i").addClass( "fa-eye-slash" );
	            $("#show_hide_password i").removeClass( "fa-eye" );
	        }else if($("#show_hide_password input").attr("type") == "password"){
	            $("#show_hide_password input").attr("type", "text");
	            $("#show_hide_password i").removeClass( "fa-eye-slash" );
	            $("#show_hide_password i").addClass( "fa-eye" );
	        }
	    });
	});
</script>';

$htmlHeadXtra[] = '<style type="text/css">
.bs-callout+.bs-callout {
    margin-top: -5px;
}
.bs-callout-info h4 {
    color: #1b809e !important;
}
.bs-callout-danger h4 {
    color: #ce4844 !important;
}
.bs-callout-warning h4 {
    color: #aa6708 !important;
}
.bs-callout h4 {
    margin-top: 0;
    margin-bottom: 5px;
}
.bs-callout-info {
    border-left-color: #1b809e !important;
}
.bs-callout-danger {
    border-left-color: #ce4844 !important;
}
.bs-callout-warning {
    border-left-color: #aa6708 !important;
}
.bs-callout {
    padding: 20px;
    margin: 20px 0;
    border: 1px solid #eee;
    border-left-width: 5px;
    border-radius: 3px;
}
</style>';




$template = new Template($pageTitle);



$urlform = api_get_self().'?'.api_get_cidreq(true, false).'&mode=';
$mode = isset($_REQUEST["mode"]) ? Database::escape_string($_REQUEST["mode"]) : null;
$meeting_id = isset($_REQUEST["meeting_id"]) ? Database::escape_string($_REQUEST["meeting_id"]) : null;
$useridregistrant = isset($_REQUEST["useridregistrant"]) ? Database::escape_string($_REQUEST["useridregistrant"]) : null;
$template->assign('urlform', $urlform);
$template->assign('student', $student);
$template->assign('teacher', $teacher);
$template->assign('is_allowed_to_edit', $is_allowed_to_edit);

switch ($mode) {
	case 'create':
		if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }

        $type = isset($_REQUEST["type"]) ? Database::escape_string($_REQUEST["type"]) : null;
        $ispast = isset($_REQUEST["past"]) ? Database::escape_string($_REQUEST["past"]) : null;
		$topic = isset($_REQUEST["topic"]) ? Database::escape_string($_REQUEST["topic"]) : null;
		$start_time = isset($_REQUEST["start_time"]) ? Database::escape_string($_REQUEST["start_time"]) : null;
		$duration = isset($_REQUEST["durationmeeting"]) ? Database::escape_string($_REQUEST["durationmeeting"]) : null;
		$password = isset($_REQUEST["passwordmeeting"]) ? Database::escape_string($_REQUEST["passwordmeeting"]) : null;
		$setregistrants = isset($_REQUEST["setregistrants"]) ? Database::escape_string($_REQUEST["setregistrants"]) : null;
		
		if (isset($topic)) {
			$creationParams['topic'] = $topic;
			$creationParams['type'] = $type;
			$creationParams['start_time'] = $start_time;
			$creationParams['duration'] = $duration;
			$creationParams['password'] = $password;
			$creationParams['setregistrants'] = $setregistrants;
			$zoomCreateMeeting = $zoomlib->createMeeting($creationParams);
			if ($zoomCreateMeeting) {
				$message = Display::return_message($zoomplugin->get_lang('MsgMeetingCreateSuccess'), 'success');
			}
			else {
				$message = Display::return_message($zoomplugin->get_lang('MsgMeetingCreateError'), 'error');
			}
			$listmeeting = $zoomlib->listMeeting();
			$pastlistmeeting = $zoomlib->listPastMeeting();

			$template->assign('listmeeting', $listmeeting);
			$template->assign('pastlistmeeting', $pastlistmeeting);
			$content = $template->fetch('zoomapi/view/z_start.tpl');
		} else {
			$passwordgenerated = api_generate_password();
			
			$template->assign('passwordgenerated', $passwordgenerated);
			$content = $template->fetch('zoomapi/view/z_create_meeting.tpl');
		}
		
		break;
	
	case 'delete':
		if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
		$deletemeeting = $zoomlib->deleteMeeting($meeting_id);
		if ($deletemeeting) {
			$message = Display::return_message($zoomplugin->get_lang('MsgMeetingDeleteSuccess'), 'success');
		} else {
			$message = Display::return_message($zoomplugin->get_lang('MsgMeetingDeleteError'), 'error');
		}
		$listmeeting = $zoomlib->listMeeting();
		$pastlistmeeting = $zoomlib->listPastMeeting();

		$template->assign('listmeeting', $listmeeting);
		$template->assign('pastlistmeeting', $pastlistmeeting);
		$content = $template->fetch('zoomapi/view/z_start.tpl');
		break;

	case 'delregistrant':
		if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        $add = $zoomlib->delMeetingRegistrants($meeting_id,$useridregistrant);
		$userList = $zoomlib->get_user_list($meeting_id);
		$getMeeting = $zoomlib->getMeetingDetails($meeting_id);
		$template->assign('userList', $userList);
		$template->assign('getMeeting', $getMeeting[0]);
		$content = $template->fetch('zoomapi/view/z_meeting_details.tpl');

		break;

	case 'addregistrant':
		if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        $userInfo = api_get_user_info($useridregistrant);
		$add = $zoomlib->addMeetingRegistrants($meeting_id,$userInfo);
		$userList = $zoomlib->get_user_list($meeting_id);
		$getMeeting = $zoomlib->getMeetingDetails($meeting_id);
		$template->assign('userList', $userList);
		$template->assign('getMeeting', $getMeeting[0]);
		$content = $template->fetch('zoomapi/view/z_meeting_details.tpl');
		break;

	case 'details':
		
		$userList = $zoomlib->get_user_list($meeting_id);
		$coachList = $zoomlib->get_coach_list($meeting_id);
		$getMeeting = $zoomlib->getMeetingDetails($meeting_id);
		
		$template->assign('participantsList', $participantsList);
		$template->assign('userreginfo', $userRegInfo);
		$template->assign('userList', $userList);
		$template->assign('coachList', $coachList);
		$template->assign('getMeeting', $getMeeting[0]);
		$content = $template->fetch('zoomapi/view/z_meeting_details.tpl');
		break;

	case 'pastdetails':
		
		$getMeeting = $zoomlib->getPastMeetingDetails($meeting_id);
		$participantsList = $zoomlib->listPastMeetingRegistrants($meeting_id);
		$ispastdetail = true;
		
		$getRecordings = $zoomlib->getRecordings($meeting_id);
		//print_r($getRecordings);
		$template->assign('getRecordings', $getRecordings);
		$template->assign('participantsList', $participantsList);
		$template->assign('ispastdetail', $ispastdetail);
		$template->assign('is_admin', $is_admin);
		$template->assign('show_record_only_for_admin', $show_record_only_for_admin);
		$template->assign('getMeeting', $getMeeting);
		$content = $template->fetch('zoomapi/view/z_meeting_details.tpl');
		break;

	case 'endmeeting':
		if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
		//print_r($meeting_id);
		# code...
		$endmeeting = $zoomlib->endMeeting($meeting_id);
		if ($endmeeting) {
			$message = Display::return_message($zoomplugin->get_lang('MsgMeetingEndSuccess'), 'success');
		} else {
			$message = Display::return_message($zoomplugin->get_lang('MsgMeetingEndError'), 'error');
		}
		$listmeeting = $zoomlib->listMeeting();
		$template->assign('listmeeting', $listmeeting);
		$content = $template->fetch('zoomapi/view/z_start.tpl');
		break;

	default:
		$listmeeting = $zoomlib->listMeeting();
		$pastlistmeeting = $zoomlib->listPastMeeting();

		$template->assign('listmeeting', $listmeeting);
		$template->assign('pastlistmeeting', $pastlistmeeting);
		//$content = "Nenhuma informação selecionada";
		$content = $template->fetch('zoomapi/view/z_start.tpl');
		break;
}

$actionLinks = "";

$actionLinks .= Display::toolbarButton(
        $zoomplugin->get_lang('CreateNewMeeting'),
        $urlform."create",
        'plus',
        'default'
    );

$actionLinks .= Display::toolbarButton(
        $zoomplugin->get_lang('ListMeetings'),
        $urlform,
        'list',
        'default'
    );

$template->assign(
        'actions',
        Display::toolbarAction('toolbar', [$actionLinks])
    );


$template->assign('header', $pageTitle);
$template->assign('message', $message);
$template->assign('content', $content);
$template->display_one_col_template();

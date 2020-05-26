<?php
/* For licensing terms, see /license.txt */

/**
 * Zoom API Integration
 * @package Chamilo\Plugin\ZoomAPI
 */ 
class ZoomApi
{
    public $userCompleteName = '';
    public $debug = false;
    public $pluginEnabled = false;
    public $userId = 0;
    public $plugin;
    private $courseCode;
    private $courseId;
    public $sectable = '';
    public $plugin_name;
    public $course_plugin;

    /**
     * Constructor (generates a connection to the API and the Chamilo settings
     * required for the connection to the video conference server)
     * @param string $host
     * @param string $salt
     * @param bool $isGlobalConference
     * @param int $isGlobalPerUser
     */
    public function __construct() {
        $this->courseCode = api_get_course_id();
        $this->courseId = api_get_course_int_id();

        // Initialize video server settings from global settings
        $this->plugin = ZoomApiPlugin::create();
        $plugin = ZoomApiPlugin::create();
        $this->zoomPluginEnabled = $plugin->get('tool_enable');
        $this->zoomPluginClientId = $plugin->get('clientid');
        $this->zoomPluginClientSecret = $plugin->get('clientsecret');
        $this->zoomPluginRedirectUri = api_get_path(WEB_PLUGIN_PATH).'zoomapi/callback.php';

        $this->tablemeeting = Database::get_main_table('plugin_zoom_meeting');
        $this->tablemeetingregistrants = Database::get_main_table('plugin_zoom_meeting_registrants');
        $this->tabletoken = Database::get_main_table('plugin_zoom_token');


        if ($this->zoomPluginEnabled === 'true') {
            $userInfo = api_get_user_info();
            if (empty($userInfo)) {
                // If we are following a link to a global "per user" conference
                // then generate a random guest name to join the conference
                // because there is no part of the process where we give a name
                $this->userCompleteName = 'Guest'.rand(1000, 9999);
            } else {
                $this->userCompleteName = $userInfo['complete_name'];
            }

            $this->pluginEnabled = true;
        }

        
    }

    private function _requiredParam($param) {
        /* Process required params and throw errors if we don't get values */
        if ((isset($param)) && ($param != '')) {
            return $param;
        }
        elseif (!isset($param)) {
            throw new Exception('Missing parameter.');
        }
        else {
            throw new Exception(''.$param.' is required.');
        }
    }

    private function _optionalParam($param) {
        /* Pass most optional params through as set value, or set to '' */
        /* Don't know if we'll use this one, but let's build it in case. */
        if ((isset($param)) && ($param != '')) {
            return $param;
        }
        else {
            $param = '';
            return $param;
        }
    }

    public function authorizationZoom() {
        $url = "https://zoom.us/oauth/authorize?response_type=code&client_id=".$this->zoomPluginClientId."&redirect_uri=".$this->zoomPluginRedirectUri;
    }

    public function is_table_empty() {
        
        $sql = "SELECT id FROM ".$this->tabletoken."";
        $rs = Database::query($sql);
        if (Database::num_rows($rs)) {
            return false;
        }
        return true;
    }
  
    public function get_access_token() {

        $sql = "SELECT access_token FROM ".$this->tabletoken."";
        $rs = Database::query($sql);
        $row = [];
        if (Database::num_rows($rs) > 0) {
            $row = Database::fetch_array($rs, 'ASSOC');
        }
        return json_decode($row['access_token']);
    }
  
    public function get_refresh_token() {
        $result = $this->get_access_token();
        return $result->refresh_token;
    }
  
    public function update_access_token($token) {
        if($this->is_table_empty()) {

            $params['access_token'] = $token;

            Database::insert($this->tabletoken, $params);

            $updatemsg = "Access token inserted successfully";
        } else {

            $params['access_token'] = $token;

            Database::update($this->tabletoken, $params, array('id = ? ' => $params['id']));

            $updatemsg = "Access token updated successfully";
        }
        return $updatemsg;
    }

    public function createMeeting($creationParams) {
        $client = new GuzzleHttp\Client(['base_uri' => 'https://api.zoom.us']);
 
        $arr_token = $this->get_access_token();
        $accessToken = $arr_token->access_token;

        try {
            $response = $client->request('POST', '/v2/users/me/meetings', [
                "headers" => [
                    "Authorization" => "Bearer ".$accessToken.""
                ],
                'json' => [
                    "topic" => $creationParams['topic'],
                    "type" => $creationParams['type'],
                    "start_time" => $creationParams['start_time'],
                    "duration" => $creationParams['duration'],
                    "password" => $creationParams['password'],
                    //"timezone" => "America/Sao_Paulo",
                    "settings" => [
                                "approval_type" => 0,
                                "auto_recording" => 'cloud',
                                "registrants_email_notification" => false,
                                ],
                    
                ],
            ]);


            $data = json_decode($response->getBody(), true);
            
            $meeting_id = $this->get_string_between($data['start_url'], '/s/', '?');

            
            $userId = api_get_user_id();
            $z_meetings['c_id'] = api_get_course_int_id();
            $z_meetings['session_id'] = api_get_session_id();
            $z_meetings['user_id'] = $userId;

            $z_meetings['created_at'] = $data['created_at'];
            $z_meetings['duration'] = $data['duration'];
            $z_meetings['host_id'] = $data['host_id'];
            $z_meetings['zoom_id'] = $data['id'];
            $z_meetings['join_url'] = $data['join_url'];
            $z_meetings['meeting_id'] = $meeting_id;
            $z_meetings['start_time'] = $data['start_time'];
            $z_meetings['start_url'] = $data['start_url'];
            $z_meetings['status'] = $data['status'];
            $z_meetings['timezone'] = $data['timezone'];
            $z_meetings['topic'] = $data['topic'];
            $z_meetings['type'] = $data['type'];
            $z_meetings['uuid'] = $data['uuid'];


            $resultinsert = Database::insert($this->tablemeeting, $z_meetings);

            if ($creationParams['setregistrants']) {
               
                $this->addRegMeetingRegistrants($meeting_id);
            }

            return $resultinsert;

     
        } catch(Exception $e) {
            if( 401 == $e->getCode() ) {
                $refresh_token = $this->get_refresh_token();
     
                $client = new GuzzleHttp\Client(['base_uri' => 'https://zoom.us']);
                $response = $client->request('POST', '/oauth/token', [
                    "headers" => [
                        "Authorization" => "Basic ". base64_encode($this->zoomPluginClientId.':'.$this->zoomPluginClientSecret)
                    ],
                    'form_params' => [
                        "grant_type" => "refresh_token",
                        "refresh_token" => $refresh_token
                    ],
                ]);
                $this->update_access_token($response->getBody());
     
                $this->createMeeting($creationParams);
            } else {
                $message = Display::addFlash(Display::return_message($e->getMessage(), 'error'));
                return false;

            }
        }
    }

    public function listMeeting(){

        $conditions = [
                'c_id = ? AND session_id = ? AND status != ?'  => [
                    api_get_course_int_id(),
                    api_get_session_id(),
                    'finished',
                ],
            ];

        $result = Database::select('meeting_id', $this->tablemeeting, ['where' => $conditions]);
        $meetinglist = array();
        if ($result) {
            foreach ($result as $meeting) {

                $meetingjson = $this->getMeetingInfo(null,null,$meeting['meeting_id']);

                if ($meetingjson){
                    if (api_is_student()){
                    $meetingjson['join_url_student'] = $this->getJoinMeetingUserURL($meeting['meeting_id']);
                    }
                    $date = substr($meetingjson['start_time'],0,strpos($meetingjson['start_time'],"T"));
                    $hour = $this->get_string_between($meetingjson['start_time'],'T','Z');
                    $meetingjson['status_orig'] = $meetingjson['status'];
                    $meetingjson['uuid'] = json_encode($meetingjson['uuid']);
                    if ($meetingjson['status'] == "started") {
                        $meetingjson['status_started'] = true;
                    } else {
                        $meetingjson['status_started'] = false;
                    }
                    $meetingjson['status'] = $this->stringStatus($meetingjson['status']);
                    $meetingjson['start_datetime'] = $date ." ". $hour;
                    $meetingjson['start_datetime'] = api_convert_and_format_date($meetingjson['start_datetime'],DATE_TIME_FORMAT_LONG);
                    $meetinglist[] = $meetingjson;
                    $params['created_at'] = $meetingjson['created_at'];
                    $params['duration'] = $meetingjson['duration'];
                    $params['host_id'] = $meetingjson['host_id'];
                    $params['meeting_id'] = $meetingjson['id'];
                    $params['join_url'] = $meetingjson['join_url'];
                    $params['start_time'] = $meetingjson['start_time'];
                    $params['start_url'] = $meetingjson['start_url'];
                    $params['status'] = $meetingjson['status_orig'];
                    $params['timezone'] = $meetingjson['timezone'];
                    $params['topic'] = $meetingjson['topic'];
                    $params['type'] = $meetingjson['type'];
                    $params['uuid'] = $meetingjson['uuid'];
                    try {
                        Database::update($this->tablemeeting, $params, array('meeting_id = ? ' => $params['meeting_id']));
                    } catch (Exception $e) {
                        Database::update($this->tablemeeting, $params, array('uuid = ? ' => $params['uuid']));
                    }
                } else {
                    $params['status'] = 'finished';
                    Database::update($this->tablemeeting, $params, array('meeting_id = ? ' => $meeting['meeting_id']));
                }
                
            }
        }

        return $meetinglist;
    }


    public function listPastMeeting(){

        $conditions = [
                'c_id = ? AND session_id = ? AND status = ?'  => [
                    api_get_course_int_id(),
                    api_get_session_id(),
                    'finished',
                ],
            ];
        
        $result = Database::select('*', $this->tablemeeting, ['where' => $conditions]);
        $meetinglist = array();
        if ($result) {
            foreach ($result as $meetingjson) {

                    $date = substr($meetingjson['start_time'],0,strpos($meetingjson['start_time'],"T"));
                    $hour = $this->get_string_between($meetingjson['start_time'],'T','Z');
                    $meetingjson['status_orig'] = $meetingjson['status'];
                    $meetingjson['uuid'] = json_encode($meetingjson['uuid']);
                    if ($meetingjson['status'] == "started") {
                        $meetingjson['status_started'] = true;
                    } else {
                        $meetingjson['status_started'] = false;
                    }
                    $meetingjson['status'] = $this->stringStatus($meetingjson['status']);
                    $meetingjson['start_datetime'] = $date ." ". $hour;
                    $meetingjson['start_datetime'] = api_convert_and_format_date($meetingjson['start_datetime'],DATE_TIME_FORMAT_LONG);
                    $meetinglist[] = $meetingjson;

                
            }
        }

        
        

        return $meetinglist;
    }

    public function deleteMeeting($meeting_id) {

        $client = new GuzzleHttp\Client(['base_uri' => 'https://api.zoom.us']);
        
        $arr_token = $this->get_access_token();
        $accessToken = $arr_token->access_token;
        try {
            $response = $client->request('DELETE', '/v2/meetings/'.$meeting_id.'', [
            "headers" => [
            "Authorization" => "Bearer ".$accessToken.""
            ]
            ]);

            $resultdelete = Database::delete($this->tablemeeting, array('meeting_id = ? ' => $meeting_id));

            if ($result) {
                return true;
            }
            else {
                return false;
            }
            
             
     
        } catch(Exception $e) {
            if( 401 == $e->getCode() or 400 == $e->getCode() ) {
                $refresh_token = $this->get_refresh_token();
     
                $client = new GuzzleHttp\Client(['base_uri' => 'https://zoom.us']);
                $response = $client->request('POST', '/oauth/token', [
                    "headers" => [
                        "Authorization" => "Basic ". base64_encode($this->zoomPluginClientId.':'.$this->zoomPluginClientSecret)
                    ],
                    'form_params' => [
                        "grant_type" => "refresh_token",
                        "refresh_token" => $refresh_token
                    ],
                ]);
                $this->update_access_token($response->getBody());
     
                $this->deleteMeeting($meeting_id);
            } else {
                $message = Display::addFlash(Display::return_message($e->getMessage(), 'error'));
                return false;
                
            }
        }

    }

    public function getCreateMeetingUrl($creationParams) {

    }

    public function getJoinMeetingUserURL($meeting_id) {
        $conditions = [
                'c_id = ? AND session_id = ? AND meeting_id = ? AND user_id = ?'  => [
                    api_get_course_int_id(),
                    api_get_session_id(),
                    $meeting_id,
                    api_get_user_id(),
                ],
            ];
        
        $join_url = Database::select('join_url', $this->tablemeetingregistrants, ['where' => $conditions]);
        
        return $join_url[0]['join_url'];
    }

    public function endMeeting($meeting_id) {
        $client = new GuzzleHttp\Client(['base_uri' => 'https://api.zoom.us']);
 
        $arr_token = $this->get_access_token();
        $accessToken = $arr_token->access_token;
        
        try {
            $response = $client->request('PUT', '/v2/meetings/'.$meeting_id.'/status', [
                "headers" => [
                    "Authorization" => "Bearer ".$accessToken.""
                ],
                'json' => [
                    "action" => "end"
                ],
            ]);
            $params['status'] = 'finished';
            Database::update($this->tablemeeting, $params, array('meeting_id = ? ' => $meeting_id));
            return true;
        } catch(Exception $e) {
            if( 401 == $e->getCode() ) {
                $refresh_token = $this->get_refresh_token();
     
                $client = new GuzzleHttp\Client(['base_uri' => 'https://zoom.us']);
                $response = $client->request('POST', '/oauth/token', [
                    "headers" => [
                        "Authorization" => "Basic ". base64_encode($this->zoomPluginClientId.':'.$this->zoomPluginClientSecret)
                    ],
                    'form_params' => [
                        "grant_type" => "refresh_token",
                        "refresh_token" => $refresh_token
                    ],
                ]);
                $this->update_access_token($response->getBody());
     
                $this->endMeeting($meeting_id);
            } else {
                $message = Display::addFlash(Display::return_message($e->getMessage(), 'error'));
                return false;
                
            }
        }
    }

    public function getMeetingInfo($start_url = null, $uuid = null, $meeting_id = null) {
        
        $occurrence_id = "";
        
        if (is_null($meeting_id)) {
            $fullstring = $start_url;
            $meeting_id = $this->get_string_between($fullstring, '/s/', '?');
            
            if (!is_null($uuid)) {
            $uuid = trim($uuid,'"');
            $occurrence_id = '"json" => [
                    "occurrence_id" => '.$uuid.'
                ],';
            }
        }
         
        $client = new GuzzleHttp\Client(['base_uri' => 'https://api.zoom.us']);
        $arr_token = $this->get_access_token();
        $accessToken = $arr_token->access_token;

        try {
            $response = $client->request('GET', '/v2/meetings/'.$meeting_id.'', [
                "headers" => [
                    "Authorization" => "Bearer ".$accessToken.""
                ],
                $occurrence_id
            ]);
     
            $data = json_decode($response->getBody(), true);
            
            return $data;

     
        } catch(Exception $e) {
            if( 401 == $e->getCode() ) {
                $refresh_token = $this->get_refresh_token();
     
                $client = new GuzzleHttp\Client(['base_uri' => 'https://zoom.us']);
                $response = $client->request('POST', '/oauth/token', [
                    "headers" => [
                        "Authorization" => "Basic ". base64_encode($this->zoomPluginClientId.':'.$this->zoomPluginClientSecret)
                    ],
                    'form_params' => [
                        "grant_type" => "refresh_token",
                        "refresh_token" => $refresh_token
                    ],
                ]);
                $this->update_access_token($response->getBody());
     
                $this->getMeetingInfo($start_url,$uuid,$meeting_id);
            } else {
                $message = Display::addFlash(Display::return_message($e->getMessage(), 'error'));
                
            }
        }
    }


    public function getPastMeetingDetails($meeting_id) {

        $conditions = [
                'c_id = ? AND session_id = ? AND status = ? AND meeting_id = ?'  => [
                    api_get_course_int_id(),
                    api_get_session_id(),
                    'finished',
                    $meeting_id,
                ],
            ];
        
        $uuid = Database::select('uuid', $this->tablemeeting, ['where' => $conditions]);

        $uuid = trim($uuid[0]['uuid'],'"');
        $uuid_doubleEncoded = htmlentities($uuid, ENT_COMPAT, 'utf-8', true);

        $client = new GuzzleHttp\Client(['base_uri' => 'https://api.zoom.us']);
        $arr_token = $this->get_access_token();
        $accessToken = $arr_token->access_token;

        try {
            $response = $client->request('GET', '/v2/past_meetings/'.$uuid_doubleEncoded.'', [
                "headers" => [
                    "Authorization" => "Bearer ".$accessToken.""
                ],
            ]);
            
            $data = json_decode($response->getBody(), true);
            
            

            $date = substr($data['start_time'],0,strpos($data['start_time'],"T"));
            $hour = $this->get_string_between($data['start_time'],'T','Z');
            $data['start_datetime'] = $date ." ". $hour;
            $data['start_datetime'] = api_convert_and_format_date($data['start_datetime'],DATE_TIME_FORMAT_LONG);

            $dateend = substr($data['end_time'],0,strpos($data['end_time'],"T"));
            $hourend = $this->get_string_between($data['end_time'],'T','Z');
            $data['end_datetime'] = $dateend ." ". $hourend;
            $data['end_datetime'] = api_convert_and_format_date($data['end_datetime'],DATE_TIME_FORMAT_LONG);

            return $data;

     
        } catch(Exception $e) {
            if( 401 == $e->getCode() ) {
                $refresh_token = $this->get_refresh_token();
     
                $client = new GuzzleHttp\Client(['base_uri' => 'https://zoom.us']);
                $response = $client->request('POST', '/oauth/token', [
                    "headers" => [
                        "Authorization" => "Basic ". base64_encode($this->zoomPluginClientId.':'.$this->zoomPluginClientSecret)
                    ],
                    'form_params' => [
                        "grant_type" => "refresh_token",
                        "refresh_token" => $refresh_token
                    ],
                ]);
                $this->update_access_token($response->getBody());
     
                $this->getPastMeetingDetails($meeting_id);
            } elseif ( 400 == $e->getCode() ) {
                $message = Display::addFlash(Display::return_message($this->plugin->get_lang('PastMeetingDetailError400'), 'error'));
                
            } elseif ( 300 == $e->getCode() ) {
                $message = Display::addFlash(Display::return_message($this->plugin->get_lang('PastMeetingDetailError300'), 'error'));
            } else {
                $message = Display::addFlash(Display::return_message($this->plugin->get_lang('PastMeetingDetailError'), 'error'));
            }
        }
    }


    public function getMeetingDetails($meeting_id){
       
            $meetingjson = $this->getMeetingInfo(null,null,$meeting_id);
            
            $date = substr($meetingjson['start_time'],0,strpos($meetingjson['start_time'],"T"));
            $hour = $this->get_string_between($meetingjson['start_time'],'T','Z');

            $meetingjson['uuid'] = json_encode($meetingjson['uuid']);
            if ($meetingjson['status'] == "started") {
                $meetingjson['status_started'] = true;
            } else {
                $meetingjson['status_started'] = false;
            }
            $meetingjson['status'] = $this->stringStatus($meetingjson['status']);
            $meetingjson['start_datetime'] = $date ." ". $hour;
            $meetingjson['start_datetime'] = api_convert_and_format_date($meetingjson['start_datetime'],DATE_TIME_FORMAT_LONG);
            $meetinglist[] = $meetingjson;

        return $meetinglist;
    }

    public function addMeetingRegistrants($meeting_id,$user) {
        
        //Display::addFlash(Display::return_message($user, 'error'));
        //print_r($user);
        $client = new GuzzleHttp\Client(['base_uri' => 'https://api.zoom.us']);
        $arr_token = $this->get_access_token();
        $accessToken = $arr_token->access_token;

        try {
            $response = $client->request('POST', '/v2/meetings/'.$meeting_id.'/registrants', [
                "headers" => [
                    "Authorization" => "Bearer ".$accessToken.""
                ],
                'json' => [
                    "email" => $user['email'],
                    "first_name" => $user['firstname'],
                    "last_name" => $user['lastname']
                ],
            ]);
     
            $data = json_decode($response->getBody(), true);
            
            $z_meetingregistrants['c_id'] = api_get_course_int_id();
            $z_meetingregistrants['session_id'] = api_get_session_id();
            $z_meetingregistrants['user_id'] = $user['id'];
            $z_meetingregistrants['meeting_id'] = $meeting_id;
            $z_meetingregistrants['join_url'] = $data['join_url'];
            $z_meetingregistrants['registrant_id'] = $data['registrant_id'];
            $z_meetingregistrants['start_time'] = $data['start_time'];
            $z_meetingregistrants['topic'] = $data['topic'];
            
            
            $resultinsertregistrants = Database::insert($this->tablemeetingregistrants, $z_meetingregistrants);
            Display::addFlash(Display::return_message($user['firstname']." ".$user['lastname']." foi registrado(a) na reunião com sucesso!", 'success'));
            return $resultinsertregistrants;

     
        } catch(Exception $e) {
            if( 401 == $e->getCode()) {
                $refresh_token = $this->get_refresh_token();
     
                $client = new GuzzleHttp\Client(['base_uri' => 'https://zoom.us']);
                $response = $client->request('POST', '/oauth/token', [
                    "headers" => [
                        "Authorization" => "Basic ". base64_encode($this->zoomPluginClientId.':'.$this->zoomPluginClientSecret)
                    ],
                    'form_params' => [
                        "grant_type" => "refresh_token",
                        "refresh_token" => $refresh_token
                    ],
                ]);
                $this->update_access_token($response->getBody());
     
                $this->addMeetingRegistrants($meeting_id,$user);
            } else {
                $message = Display::addFlash(Display::return_message($e->getMessage(), 'error'));
                
            }
        }
    }

    public function delMeetingRegistrants($meeting_id,$useridregistrant) {
        
        $userInfo = api_get_user_info($useridregistrant);

        $conditions = [
                'c_id = ? AND session_id = ? AND meeting_id = ? AND user_id = ?'  => [
                    api_get_course_int_id(),
                    api_get_session_id(),
                    $meeting_id,
                    $useridregistrant,
                ],
            ];

        $registrant_id = Database::select('registrant_id', $this->tablemeetingregistrants, ['where' => $conditions]);
        
        $client = new GuzzleHttp\Client(['base_uri' => 'https://api.zoom.us']);
        $arr_token = $this->get_access_token();
        $accessToken = $arr_token->access_token;
        
        if ($registrant_id) {
            try {
                $response = $client->request('PUT', '/v2/meetings/'.$meeting_id.'/registrants/status', [
                    "headers" => [
                        "Authorization" => "Bearer ".$accessToken.""
                    ],
                    'json' => [
                        "action" => "cancel",
                        "registrants" => [
                            [
                            "id" => $registrant_id[0]['registrant_id'],
                            //"email" => $userInfo['email'],
                            ],
                        ],
                    ],
                ]);
         

                $resultdelete = Database::delete($this->tablemeetingregistrants, $conditions);
                if ($resultdelete) {
                    Display::addFlash(Display::return_message($userInfo['firstname']." ".$userInfo['lastname']." foi cancelado(a) da reunião com sucesso!", 'success'));
                } else {
                    Display::addFlash(Display::return_message($userInfo['firstname']." ".$userInfo['lastname']." foi cancelado(a) do reunião no zoom, mas houve algum erro ao apagar na plataforma.", 'error'));
                }
                
                return true;

         
            } catch(Exception $e) {
                if( 401 == $e->getCode()) {
                    $refresh_token = $this->get_refresh_token();
         
                    $client = new GuzzleHttp\Client(['base_uri' => 'https://zoom.us']);
                    $response = $client->request('POST', '/oauth/token', [
                        "headers" => [
                            "Authorization" => "Basic ". base64_encode($this->zoomPluginClientId.':'.$this->zoomPluginClientSecret)
                        ],
                        'form_params' => [
                            "grant_type" => "refresh_token",
                            "refresh_token" => $refresh_token
                        ],
                    ]);
                    $this->update_access_token($response->getBody());
         
                    $this->delMeetingRegistrants($meeting_id,$user_id);
                } else {
                    $message = Display::addFlash(Display::return_message($e->getMessage(), 'error'));
                    
                }
            }
        } else {
            $message = Display::addFlash(Display::return_message('Erro ao selecionar aluno', 'error'));
        }

        
    }

    public function addRegMeetingRegistrants($meeting_id){


            $userList = $this->get_user_list($meeting_id);

            foreach ($userList as $value) {
                
                $addregistrants = $this->addMeetingRegistrants($meeting_id, $value);
            }

            $coachList = $this->get_coach_list($meeting_id);

            foreach ($coachList as $value) {
                
                $addcoachregistrants = $this->addMeetingRegistrants($meeting_id, $value);
            }
            
    }

    public function get_string_between($string, $start, $end){
            $string = ' ' . $string;
            $ini = strpos($string, $start);
            if ($ini == 0) return '';
            $ini += strlen($start);
            $len = strpos($string, $end, $ini) - $ini;
            return substr($string, $ini, $len);
    }

    public function get_user_list($meeting_id = null) {
        $session_id = api_get_session_id();
        if ($session_id != 0) {
            $withsession = true;
        } else {
            $withsession = false;
        }

        $courseCode = api_get_course_info();
        
        $userList = CourseManager::get_student_list_from_course_code($courseCode['code'],$withsession,$session_id);
        $i = 0;

        
        foreach ($userList as $value) {
            
            if ($meeting_id) {
                $isregistrants = $this->isRegistrants($value['id'], $meeting_id);
                $useridList[$i]['join_url'] = $isregistrants[0]['join_url'];
                
            }
            $useridList[$i]['id'] = $value['id'];
            $useridList[$i]['email'] = $value['email'];
            $useridList[$i]['firstname'] = $value['firstname'];
            $useridList[$i]['lastname'] = $value['lastname'];
            $useridList[$i]['user_id'] = $value['user_id'];
            $i++;
            
        }
        
    return $useridList;  
    }

    public function get_coach_list($meeting_id = null) {
        $session_id = api_get_session_id();
        if ($session_id != 0) {
            $withsession = true;
        } else {
            $withsession = false;
        }

        $courseCode = api_get_course_info();
        
        $coachList = CourseManager::get_coach_list_from_course_code($courseCode['code'],$session_id);
        $i = 0;

        
        foreach ($coachList as $value) {
            
            if ($meeting_id) {
                $isregistrants = $this->isRegistrants($value['id'], $meeting_id);
                $coachidList[$i]['join_url'] = $isregistrants[0]['join_url'];
                
            }
            $coachidList[$i]['id'] = $value['id'];
            $coachidList[$i]['email'] = $value['email'];
            $coachidList[$i]['firstname'] = $value['firstname'];
            $coachidList[$i]['lastname'] = $value['lastname'];
            $coachidList[$i]['user_id'] = $value['user_id'];
            $i++;
            
        }
        
    return $coachidList;  
    }
    

    public function stringStatus($status){
            switch ($status) {
                case 'waiting':
                    $statustext = $this->plugin->get_lang('waiting');
                    break;
                case 'started':
                    $statustext = $this->plugin->get_lang('started');
                    break;
                case 'finished':
                    $statustext = $this->plugin->get_lang('finished');
                    break;
                default:
                    $statustext = '';
                    break;
            }
            return $statustext;
    }

    public function isRegistrants($user_id, $meeting_id) {
        
            $conditions = [
                'meeting_id = ? AND user_id = ?'  => [
                    $meeting_id,
                    $user_id,
                ],
            ];
            $meetingregistrants = Database::select('join_url,registrant_id', $this->tablemeetingregistrants, ['where' => $conditions]);

            return $meetingregistrants;
    }

    public function listMeetingRegistrants($meeting_id) {

        $client = new GuzzleHttp\Client(['base_uri' => 'https://api.zoom.us']);
        $arr_token = $this->get_access_token();
        $accessToken = $arr_token->access_token;

        try {
            $response = $client->request('GET', '/v2/meetings/'.$meeting_id.'/registrants', [
                "headers" => [
                    "Authorization" => "Bearer ".$accessToken.""
                ],
                'json' => [
                    "page_size" => '300',
                ],
            ]);
     
            $data = json_decode($response->getBody(), true);
            
            return $data;

     
        } catch(Exception $e) {
            if( 401 == $e->getCode()) {
                $refresh_token = $this->get_refresh_token();
     
                $client = new GuzzleHttp\Client(['base_uri' => 'https://zoom.us']);
                $response = $client->request('POST', '/oauth/token', [
                    "headers" => [
                        "Authorization" => "Basic ". base64_encode($this->zoomPluginClientId.':'.$this->zoomPluginClientSecret)
                    ],
                    'form_params' => [
                        "grant_type" => "refresh_token",
                        "refresh_token" => $refresh_token
                    ],
                ]);
                $this->update_access_token($response->getBody());
     
                $this->listMeetingRegistrants($meeting_id);
            } else {
                Display::addFlash(Display::return_message($e->getMessage(), 'error'));
                
            }
        }
    }

    public function listPastMeetingRegistrants($meeting_id) {


        $conditions = [
                'meeting_id = ?'  => [
                    $meeting_id,
                ],
            ];
        $uuid = Database::select('uuid', $this->tablemeeting, ['where' => $conditions]);
        $uuid = trim($uuid[0]['uuid'],'"');
        $uuid_doubleEncoded = htmlentities($uuid, ENT_COMPAT, 'utf-8', true);

        $client = new GuzzleHttp\Client(['base_uri' => 'https://api.zoom.us']);
        $arr_token = $this->get_access_token();
        $accessToken = $arr_token->access_token;

        try {
            $response = $client->request('GET', '/v2/past_meetings/'.$uuid_doubleEncoded.'/participants', [
                "headers" => [
                    "Authorization" => "Bearer ".$accessToken.""
                ],
                'json' => [
                    "page_size" => '300',
                ],
            ]);
     
            $data = json_decode($response->getBody(), true);
            
            return $data;

     
        } catch(Exception $e) {
            if( 401 == $e->getCode()) {
                $refresh_token = $this->get_refresh_token();
     
                $client = new GuzzleHttp\Client(['base_uri' => 'https://zoom.us']);
                $response = $client->request('POST', '/oauth/token', [
                    "headers" => [
                        "Authorization" => "Basic ". base64_encode($this->zoomPluginClientId.':'.$this->zoomPluginClientSecret)
                    ],
                    'form_params' => [
                        "grant_type" => "refresh_token",
                        "refresh_token" => $refresh_token
                    ],
                ]);
                $this->update_access_token($response->getBody());
     
                $this->listPastMeetingRegistrants($meeting_id);
            } elseif ( 400 == $e->getCode() ) {
                $message = Display::addFlash(Display::return_message($this->plugin->get_lang('PastListRegistrantsError400'), 'error'));
                //echo $e->getMessage();
            } elseif ( 404 == $e->getCode() ) {
                $message = Display::addFlash(Display::return_message($this->plugin->get_lang('PastListRegistrantsError400'), 'error'));
            } elseif ( 300 == $e->getCode() ) {
                $message = Display::addFlash(Display::return_message($this->plugin->get_lang('PastMeetingDetailError300'), 'error'));
            } else {
                $message = Display::addFlash(Display::return_message($this->plugin->get_lang('PastMeetingDetailError'), 'error'));
            }
        }
    }

    public function getRecordings($meeting_id) {

        $conditions = [
                'c_id = ? AND session_id = ? AND status = ? AND meeting_id = ?'  => [
                    api_get_course_int_id(),
                    api_get_session_id(),
                    'finished',
                    $meeting_id,
                ],
            ];

        $uuid = Database::select('uuid', $this->tablemeeting, ['where' => $conditions]);

        $uuid = trim($uuid[0]['uuid'],'"');
        $uuid_doubleEncoded = htmlentities($uuid, ENT_COMPAT, 'utf-8', true);

        $client = new GuzzleHttp\Client(['base_uri' => 'https://api.zoom.us']);
        $arr_token = $this->get_access_token();
        $accessToken = $arr_token->access_token;

        try {
            $response = $client->request('GET', '/v2/meetings/'.$uuid_doubleEncoded.'/recordings', [
                "headers" => [
                    "Authorization" => "Bearer ".$accessToken.""
                ],
            ]);
            
            $data = json_decode($response->getBody(), true);
            

            $date = substr($data['start_time'],0,strpos($data['start_time'],"T"));
            $hour = $this->get_string_between($data['start_time'],'T','Z');
            $data['start_time'] = $date ." ". $hour;
            $data['start_time'] = api_convert_and_format_date($data['start_time'],DATE_TIME_FORMAT_LONG);
            $data['total_size'] = $this->formatBytes($data['total_size']);



            foreach($data['recording_files'] as &$value){
                
                    $datestart = substr($value['recording_start'],0,strpos($value['recording_start'],"T"));
                    $hourstart = $this->get_string_between($value['recording_start'],'T','Z');
                    $value['recording_start'] = $datestart ." ". $hourstart;
                    $value['recording_start'] = api_convert_and_format_date($value['recording_start'],DATE_TIME_FORMAT_LONG);
                    
                    $dateend = substr($value['recording_end'],0,strpos($value['recording_end'],"T"));
                    $hourend = $this->get_string_between($value['recording_end'],'T','Z');
                    $value['recording_end'] = $dateend ." ". $hourend;
                    $value['recording_end'] = api_convert_and_format_date($value['recording_end'],DATE_TIME_FORMAT_LONG);
                
                    $value['file_size'] = $this->formatBytes($value['file_size']);
                    $value['recording_type'] = $this->recordType($value['recording_type']);

            }

            return $data;

     
        } catch(Exception $e) {
            if( 401 == $e->getCode() ) {
                $refresh_token = $this->get_refresh_token();
     
                $client = new GuzzleHttp\Client(['base_uri' => 'https://zoom.us']);
                $response = $client->request('POST', '/oauth/token', [
                    "headers" => [
                        "Authorization" => "Basic ". base64_encode($this->zoomPluginClientId.':'.$this->zoomPluginClientSecret)
                    ],
                    'form_params' => [
                        "grant_type" => "refresh_token",
                        "refresh_token" => $refresh_token
                    ],
                ]);
                $this->update_access_token($response->getBody());
     
                $this->getPastMeetingDetails($meeting_id);
            } elseif ( 400 == $e->getCode() ) {
                $message = Display::addFlash(Display::return_message($this->plugin->get_lang('ListRecordingsError400'), 'error'));
                //echo $e->getMessage();
            } elseif ( 404 == $e->getCode() ) {
                $message = Display::addFlash(Display::return_message($this->plugin->get_lang('ListRecordingsError400'), 'error'));
            } else {
                $message = Display::addFlash(Display::return_message($this->plugin->get_lang('ListRecordingsError'), 'error'));
            }
        }
    }


    public function getDeleteRecordingsUrl($recordingParams) {

    }

    function recordType($type) {

        switch ($type) {
            case 'shared_screen_with_speaker_view(CC)':
                $type = $this->plugin->get_lang('shared_screen_with_speaker_view(CC)');
                break;
            case 'shared_screen_with_speaker_view':
                $type = $this->plugin->get_lang('shared_screen_with_speaker_view');
                break;
            case 'shared_screen_with_gallery_view':
                $type = $this->plugin->get_lang('shared_screen_with_gallery_view');
                break;
            case 'speaker_view':
                $type = $this->plugin->get_lang('speaker_view');
                break;
            case 'gallery_view':
                $type = $this->plugin->get_lang('gallery_view');
                break;
            case 'shared_screen':
                $type = $this->plugin->get_lang('shared_screen');
                break;
            case 'audio_only':
                $type = $this->plugin->get_lang('audio_only');
                break;
            case 'audio_transcript':
                $type = $this->plugin->get_lang('audio_transcript');
                break;
            case 'chat_file':
                $type = $this->plugin->get_lang('chat_file');
                break;
            case 'TIMELINE':
                $type = $this->plugin->get_lang('TIMELINE');
                break;
            default:
                $type = $this->plugin->get_lang('recordtype_default');
                break;
        }
        return $type;
    }


    function formatBytes($size, $precision = 2) {
        $base = log($size, 1024);
        $suffixes = array('', 'K', 'M', 'G', 'T');   

        return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
    }
}

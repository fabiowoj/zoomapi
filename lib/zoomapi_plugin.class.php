<?php
/* For licensing terms, see /license.txt */

/* To show the plugin course icons you need to add these icons:
 * main/img/icons/22/plugin_name.png
 * main/img/icons/64/plugin_name.png
 * main/img/icons/64/plugin_name_na.png
*/
/**
 *  Zoom plugin
 */
 
class ZoomApiPlugin extends Plugin
{

    public $isCoursePlugin = true;

    // When creating a new course this settings are added to the course

    /**
     * Zoom Plugin constructor.
     */
    protected function __construct()
    {
        parent::__construct(
            '1.0',
            'Fábio Wojcikiewicz Almeida',
            [
                'tool_enable' => 'boolean',
                'clientid' => 'text',
                'clientsecret' => 'text',
                'show_record_only_for_admin' => 'boolean',
            ]
        );

        $this->isAdminPlugin = true;
    }

    
    public static function create()
    {
        static $result = null;
        return $result ? $result : $result = new self();
    }


    public function get_name()
    {
        return 'zoomapi';
    }
    /**
     * Install
     */
    public function install()
    {
       $sql = "CREATE TABLE IF NOT EXISTS plugin_zoom_meeting (
                id INT unsigned NOT NULL auto_increment PRIMARY KEY,
                c_id INT unsigned DEFAULT 0,
                session_id INT unsigned DEFAULT 0,
                user_id INT unsigned NOT NULL DEFAULT 0,
                calendar_id INT DEFAULT 0,
                created_at VARCHAR(255) NOT NULL,
                duration INT unsigned DEFAULT 0,
                host_id VARCHAR(255) NOT NULL DEFAULT '',
                zoom_id INT unsigned DEFAULT 0,
                join_url VARCHAR(255) NOT NULL DEFAULT '',
                meeting_id BIGINT NOT NULL DEFAULT 0,
                start_time VARCHAR(255) NOT NULL,
                start_url VARCHAR(255) NOT NULL DEFAULT '',
                status VARCHAR(255) NOT NULL DEFAULT '',
                timezone VARCHAR(255) NOT NULL DEFAULT '',
                topic VARCHAR(255) NOT NULL DEFAULT '',
                type INT NOT NULL DEFAULT 0,
                uuid VARCHAR(255) NOT NULL DEFAULT ''               
                )";
        Database::query($sql);

        $sql = "CREATE TABLE IF NOT EXISTS plugin_zoom_meeting_registrants (
                id INT unsigned NOT NULL auto_increment PRIMARY KEY,
                c_id INT unsigned DEFAULT 0,
                session_id INT unsigned DEFAULT 0,
                user_id INT unsigned NOT NULL DEFAULT 0,
                meeting_id INT unsigned DEFAULT 0,
                join_url VARCHAR(255) NOT NULL DEFAULT '',
                registrant_id VARCHAR(255) NOT NULL DEFAULT '',
                start_time VARCHAR(255) NOT NULL DEFAULT '',
                topic VARCHAR(255) NOT NULL DEFAULT ''              
                )";
        Database::query($sql);


        Database::query(
            "CREATE TABLE IF NOT EXISTS plugin_zoom_token (
             `id` int(11) NOT NULL AUTO_INCREMENT,
             `access_token` text NOT NULL,
             PRIMARY KEY (`id`)
            );"
        );
        

        // Installing course settings
        $this->install_course_fields_in_all_courses(); 

        $sqlupdate = "UPDATE `c_tool` SET `visibility`=0 WHERE `name`= 'zoomapi'";
        Database::query($sqlupdate);
    }

    
    /**
     * Uninstall
     */
    public function uninstall()
    {
        $t_settings = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
        $t_options = Database::get_main_table(TABLE_MAIN_SETTINGS_OPTIONS);
        $t_tool = Database::get_course_table(TABLE_TOOL_LIST);

        $variables = [
            'clientid',
            'clientsecret',
            'tool_enable',
            'show_record_only_for_admin',
        ];

        foreach ($variables as $variable) {
            $sql = "DELETE FROM $t_settings WHERE variable = '$variable'";
            Database::query($sql);
        }

        $sql = "DELETE FROM $t_options WHERE variable = 'zoomapi_plugin'";
        Database::query($sql);

        // hack to get rid of Database::query warning (please add c_id...)
        $sql = "DELETE FROM $t_tool WHERE name = 'zoomapi' AND c_id != 0";
        Database::query($sql);

        Database::query('DROP TABLE IF EXISTS plugin_zoom_meeting');
        Database::query('DROP TABLE IF EXISTS plugin_zoom_token');

        // Deleting course settings
        $this->uninstall_course_fields_in_all_courses($this->course_settings);
    }



}
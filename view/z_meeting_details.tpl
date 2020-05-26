<p class="lead">{{ getMeeting.topic|e|nl2br }}
{% if ispastdetail %}
	<div class="panel panel-default">
		<div class="panel-body">
	  		
			{% if not student %}
				
				<p><i class="fa fa-clock-o fa-fw" aria-hidden="true"></i>&nbsp; <strong>{{ 'Duration'|get_plugin_lang('ZoomApiPlugin') }}:</strong> <em>{{ getMeeting.duration }}</em></p>
				<p><i class="fa fa-calendar fa-fw" aria-hidden="true"></i>&nbsp; <strong>{{ 'start_datetime'|get_plugin_lang('ZoomApiPlugin') }}:</strong> <em>{{ getMeeting.start_datetime }}</em></p>
				<p><i class="fa fa-calendar fa-fw" aria-hidden="true"></i>&nbsp; <strong>{{ 'end_datetime'|get_plugin_lang('ZoomApiPlugin') }}:</strong> <em>{{ getMeeting.end_datetime }}</em></p>
				<p><i class="fa fa-users fa-fw" aria-hidden="true"></i>&nbsp; <strong>{{ 'TotalParticipants'|get_plugin_lang('ZoomApiPlugin') }}:</strong> <em>{{ getMeeting.participants_count }}</em></p>
				
				{% include 'zoomapi/view/z_list_registrants.tpl' %}
				
				{% if show_record_only_for_admin %}
					{% if is_admin %}
						{% if getRecordings %}
						{% include 'zoomapi/view/z_recordings.tpl' %}
						{% endif %}
					{% endif %}
				{% else %}
					{% if getRecordings %}
					{% include 'zoomapi/view/z_recordings.tpl' %}
					{% endif %}
				{% endif %}				
				
			{% endif %}
		</div>
	</div>
</p>
{% else %}
	{% if student and getMeeting.status_started %}
		{% if userreginfo %}
			<a href="{{userreginfo.join_url_student}}" class="btn btn-primary pull-right" target="_new" role="button">{{ 'Enter'|get_plugin_lang('ZoomApiPlugin') }} <i class="fa fa-video-camera fa-fw" aria-hidden="true"></i></a>
		{% else %}
			<a href="#" class="btn btn-warning pull-right" role="button" disabled="disabled">{{ 'Notallowedtoenter'|get_plugin_lang('ZoomApiPlugin') }} <i class="fa fa-exclamation fa-fw" aria-hidden="true"></i></a>
		{% endif %}
	{% elseif teacher and getMeeting.status_started %}
	<a href="{{getMeeting.start_url}}" class="btn btn-primary pull-right" target="_new" role="button">{{ 'ResumeMeeting'|get_plugin_lang('ZoomApiPlugin') }} <i class="fa fa-video-camera fa-fw" aria-hidden="true"></i></a>
	{% elseif teacher and not getMeeting.status_started %}
	<a href="{{getMeeting.start_url}}" class="btn btn-primary pull-right" target="_new" role="button">{{ 'StartMeeting'|get_plugin_lang('ZoomApiPlugin') }} <i class="fa fa-video-camera fa-fw" aria-hidden="true"></i></a>
	{% else %}
	<a href="#" class="btn btn-primary pull-right" role="button" disabled="disabled">{{ 'WaitforStart'|get_plugin_lang('ZoomApiPlugin') }} <i class="fa fa-video-camera fa-fw" aria-hidden="true"></i></a>
	{% endif %}
</p>


	<div class="panel panel-default">
		<div class="panel-body">
	  		
	    	<p><span class="label label-info">{{getMeeting.status}}</span></p>
			<p><i class="fa fa-calendar" aria-hidden="true"></i>&nbsp; {{getMeeting.start_datetime}}</p>
			{% if not student %}
				{% include 'zoomapi/view/z_list_registrants.tpl' %}
			{% endif %}
		</div>
	</div>
{% endif %}
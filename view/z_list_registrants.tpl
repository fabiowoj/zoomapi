{% if ispastdetail %}
<div class="col-md-12">
	<div class="bs-callout bs-callout-warning" id="callout-btn-group-accessibility">
    <h4>{{ 'Participants'|get_plugin_lang('ZoomApiPlugin') }}</h4>
    <br>
    <ul class="list-group">
	{% for partlist in participantsList.participants %}
  		<li class="list-group-item">{{partlist.name}}</li>
	{% endfor %}
	</ul>
	</div>
</div>
{% else %}
<h4>{{ 'CoachList'|get_plugin_lang('ZoomApiPlugin') }}</h4>
<table class="table table-striped">
	<thead>
		<tr>
			<th>{{ 'Name'|get_plugin_lang('ZoomApiPlugin') }}</th>
			<th>{{ 'Lastname'|get_plugin_lang('ZoomApiPlugin') }}</th>
			<th>{{ 'Email'|get_plugin_lang('ZoomApiPlugin') }}</th>
			<th>{{ 'Actions'| get_lang }}</th>
		</tr>
	</thead>
	<tbody>
		{% for coachlist in coachList %}
		<tr>
			<td>{{coachlist.firstname}}</td>
			<td>{{coachlist.lastname}}</td>
			<td>{{coachlist.email}}</td>
			<td class="text-left">
				{% if coachlist.join_url %}
					<a class="btn btn-danger btn-sm" href="{{ urlform }}delregistrant&meeting_id={{getMeeting.id}}&useridregistrant={{coachlist.user_id}}"><i class="fa fa-user-plus"></i> {{ 'CancelReg'|get_plugin_lang('ZoomApiPlugin') }}</a></td>
				{% else %}
					<a class="btn btn-primary btn-sm" href="{{ urlform }}addregistrant&meeting_id={{getMeeting.id}}&useridregistrant={{coachlist.user_id}}"><i class="fa fa-user-plus"></i> {{ 'RegInMeeting'|get_plugin_lang('ZoomApiPlugin') }}</a></td>
				{% endif %}

				
		</tr>
		{% endfor %}
	</tbody>
</table>
<br>
<h4>{{ 'StudentList'|get_plugin_lang('ZoomApiPlugin') }}</h4>
<table class="table table-striped">
	<thead>
		<tr>
			<th>{{ 'Name'|get_plugin_lang('ZoomApiPlugin') }}</th>
			<th>{{ 'Lastname'|get_plugin_lang('ZoomApiPlugin') }}</th>
			<th>{{ 'Email'|get_plugin_lang('ZoomApiPlugin') }}</th>
			<th>{{ 'Actions'| get_lang }}</th>
		</tr>
	</thead>
	<tbody>
		{% for list in userList %}
		<tr>
			<td>{{list.firstname}}</td>
			<td>{{list.lastname}}</td>
			<td>{{list.email}}</td>
			<td class="text-left">
				{% if list.join_url %}
					<a class="btn btn-danger btn-sm" href="{{ urlform }}delregistrant&meeting_id={{getMeeting.id}}&useridregistrant={{list.user_id}}"><i class="fa fa-user-plus"></i> {{ 'CancelReg'|get_plugin_lang('ZoomApiPlugin') }}</a></td>
				{% else %}
					<a class="btn btn-primary btn-sm" href="{{ urlform }}addregistrant&meeting_id={{getMeeting.id}}&useridregistrant={{list.user_id}}"><i class="fa fa-user-plus"></i> {{ 'RegInMeeting'|get_plugin_lang('ZoomApiPlugin') }}</a></td>
				{% endif %}

				
		</tr>
		{% endfor %}
	</tbody>
</table>
{% endif %}
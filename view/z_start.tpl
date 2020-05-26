{%  if header %}
    <p class="lead">{{ header|e|nl2br }}</p>
{%  endif %}
<div class="col-md-3">
<i class="fa fa-video-camera fa-5x text-primary" aria-hidden="true"></i>
</div>
<div class="col-md-9">
  <div class="col-md-12">
    <div class="bs-callout bs-callout-info" id="callout-btn-group-accessibility">
      <h4>{{ 'NextMeetings'|get_plugin_lang('ZoomApiPlugin') }}</h4>
      <br>
      {% if listmeeting %}

        {% for list in listmeeting %}

          <div class="panel panel-default">
            <div class="panel-heading">
              {% if not student %}
              <div class="btn-group pull-right">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fa fa-caret-down fa-fw" aria-hidden="true"></i>
                </a>
                <ul class="dropdown-menu">
                  <li><a href="#" data-href="{{ urlform }}endmeeting&meeting_id={{list.id}}" data-toggle="modal" data-target="#confirm-end"><i class="fa fa-stop-circle fa-fw" aria-hidden="true"></i>&nbsp; {{ 'EndMeeting'|get_plugin_lang('ZoomApiPlugin') }}</a></li>
                  <li><a href="{{ urlform }}details&meeting_id={{list.id}}"><i class="fa fa-cog fa-fw" aria-hidden="true"></i>&nbsp; {{ 'DetailMeeting'|get_plugin_lang('ZoomApiPlugin') }}</a></li>
                  <li role="separator" class="divider"></li>
                  <li><a href="#" data-href="{{ urlform }}delete&meeting_id={{list.id}}" data-toggle="modal" data-target="#confirm-delete"><i class="fa fa-trash fa-fw" aria-hidden="true"></i>&nbsp; {{ 'DeleteMeeting'|get_plugin_lang('ZoomApiPlugin') }}</a></li>
                </ul>
              </div>
              <h3 class="panel-title"><a href="{{ urlform }}details&meeting_id={{list.id}}" title="{{list.topic}}">{{list.topic}}</a></h3>
              {% else %}
              <h3 class="panel-title">{{list.topic}}</h3>
              {% endif %}
            </div>
            <div class="panel-body">
              {% if student and list.status_started %}
              <a href="{{list.join_url_student}}" class="btn btn-primary pull-right" target="_new" role="button">{{ 'Enter'|get_plugin_lang('ZoomApiPlugin') }} <i class="fa fa-video-camera fa-fw" aria-hidden="true"></i></a>
              {% elseif teacher and list.status_started %}
              <a href="{{list.start_url}}" class="btn btn-primary pull-right" target="_new" role="button">{{ 'ResumeMeeting'|get_plugin_lang('ZoomApiPlugin') }} <i class="fa fa-video-camera fa-fw" aria-hidden="true"></i></a>
              {% elseif teacher and not list.status_started %}
              <a href="{{list.start_url}}" class="btn btn-primary pull-right" target="_new" role="button">{{ 'StartMeeting'|get_plugin_lang('ZoomApiPlugin') }} <i class="fa fa-video-camera fa-fw" aria-hidden="true"></i></a>
              {% else %}
              <a href="#" class="btn btn-primary pull-right" role="button" disabled="disabled">{{ 'WaitforStart'|get_plugin_lang('ZoomApiPlugin') }} <i class="fa fa-video-camera fa-fw" aria-hidden="true"></i></a>
              {% endif %}
              <p><span class="label label-info">{{list.status}}</span></p>
            <p><i class="fa fa-calendar" aria-hidden="true"></i>&nbsp; {{list.start_datetime}}</p>
            </div>
          </div>
        {% endfor %}
        {% else %}
          <div class="alert alert-info" role="alert">{{ 'NoMeetingSchedule'|get_plugin_lang('ZoomApiPlugin') }}</div>
        {% endif %}    
    </div>
  </div>
  


  <div class="col-md-12">
    <div class="bs-callout bs-callout-warning" id="callout-btn-group-accessibility">
      <h4>{{ 'PastMeetings'|get_plugin_lang('ZoomApiPlugin') }}</h4>
      <br>
      {% if pastlistmeeting %}

      {% for pastlist in pastlistmeeting %}

        <div class="panel panel-default">
          <div class="panel-heading">
            {% if not student %}
            <div class="btn-group pull-right">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <i class="fa fa-caret-down fa-fw" aria-hidden="true"></i>
              </a>
              <ul class="dropdown-menu">
                <li><a href="{{ urlform }}pastdetails&meeting_id={{pastlist.meeting_id}}&past=true"><i class="fa fa-cog fa-fw" aria-hidden="true"></i>&nbsp; {{ 'DetailMeeting'|get_plugin_lang('ZoomApiPlugin') }}</a></li>
                <li role="separator" class="divider"></li>
                <li><a href="#" data-href="{{ urlform }}delete&meeting_id={{pastlist.meeting_id}}" data-toggle="modal" data-target="#confirm-delete"><i class="fa fa-trash fa-fw" aria-hidden="true"></i>&nbsp; {{ 'DeleteMeeting'|get_plugin_lang('ZoomApiPlugin') }}</a></li>
              </ul>
            </div>
            <h3 class="panel-title"><a href="{{ urlform }}pastdetails&meeting_id={{pastlist.meeting_id}}&past=true" title="{{pastlist.topic}}">{{pastlist.topic}}</a></h3>
            {% else %}
            <h3 class="panel-title">{{pastlist.topic}}</h3>
            {% endif %}
            
          </div>
          <div class="panel-body">
            <p><span class="label label-info">{{pastlist.status}}</span></p>
          <p><i class="fa fa-calendar" aria-hidden="true"></i>&nbsp; {{pastlist.start_datetime}}</p>
          </div>
        </div>
      {% endfor %}
    {% else %}
      <div class="alert alert-info" role="alert">{{ 'NoPastMeetingSchedule'|get_plugin_lang('ZoomApiPlugin') }}</div>
    {% endif %}
    </div>
  </div>
</div>

<!-- Modal CONFIRM DELETE -->
<div class="modal fade" id="confirm-delete" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4>{{ 'ConfirmDelete'|get_plugin_lang('ZoomApiPlugin') }}</h4>
            </div>
            <div class="modal-body">
              <p>{{ 'ConfirmDeleteMsg'|get_plugin_lang('ZoomApiPlugin') }}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ 'Cancel'|get_plugin_lang('ZoomApiPlugin') }}</button>
                <a class="btn btn-danger btn-okdel">{{ 'YesDelete'|get_plugin_lang('ZoomApiPlugin') }}</a>
            </div>
        </div>
    </div>
</div>

<!-- Modal CONFIRM END -->
<div class="modal fade" id="confirm-end" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4>{{ 'ConfirmEnd'|get_plugin_lang('ZoomApiPlugin') }}</h4>
            </div>
            <div class="modal-body">
              <p>{{ 'ConfirmEndMsg'|get_plugin_lang('ZoomApiPlugin') }}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ 'Cancel'|get_plugin_lang('ZoomApiPlugin') }}</button>
                <a class="btn btn-danger btn-okend">{{ 'YesEnd'|get_plugin_lang('ZoomApiPlugin') }}</a>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
	$('#confirm-delete').on('show.bs.modal', function(e) {
    $(this).find('.btn-okdel').attr('href', $(e.relatedTarget).data('href'));
});
	$('#confirm-end').on('show.bs.modal', function(e) {
    $(this).find('.btn-okend').attr('href', $(e.relatedTarget).data('href'));
});
</script>
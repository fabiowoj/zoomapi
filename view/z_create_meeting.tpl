<form method="POST" action="{{ urlform }}create">
	<div class="col-md-4">
	  <div class="form-group">
	    <label for="topico">{{ 'Topic'|get_plugin_lang('ZoomApiPlugin') }}</label>
	    <input type="text" class="form-control" id="topico" aria-describedby="topicoHelp" placeholder="{{ 'Topic'|get_plugin_lang('ZoomApiPlugin') }}" name="topic" required="required" value="">
	    <small id="topicoHelp" class="form-text text-muted">{{ 'TopicHelp'|get_plugin_lang('ZoomApiPlugin') }}.</small>
	  </div>
	  <div class="form-group">
	    <label for="horareuniao">{{ 'TimeOfMeeting'|get_plugin_lang('ZoomApiPlugin') }}</label>
	    <input type="text" class="form-control" id="horareuniao">
	    <input type="hidden" class="form-control" id="horareuniao2" name="start_time" required="required" value="">
	  </div>
	</div>
	<div class="col-md-4">
	  <div class="form-group">
	    <label for="type">{{ 'TypeOfMeeting'|get_plugin_lang('ZoomApiPlugin') }}</label><br>
	    <select class="selectpicker show-tick" id="type" title="{{ 'TypeOfMeetingHelp'|get_plugin_lang('ZoomApiPlugin') }}" name="type" required="required">
		  <option value="1">{{ 'Immediate'|get_plugin_lang('ZoomApiPlugin') }}</option>
		  <option value="2" selected="selected">{{ 'Scheduled'|get_plugin_lang('ZoomApiPlugin') }}</option>
		</select>
	  </div>
	  <br>
	  <div class="form-group">
		  <div class="checkbox">
		    <label>
		      <input type="checkbox" name="setregistrants" checked="checked" value="1"> <strong>{{ 'SetAllRegistrants'|get_plugin_lang('ZoomApiPlugin') }}</strong>
		    </label>
		  </div>
	  </div>
	</div>
	<div class="col-md-4">
	  <div class="form-group">
	    <label for="durationmeeting">{{ 'DurationOfMeeting'|get_plugin_lang('ZoomApiPlugin') }}</label>
	    <input type="number" class="form-control" id="durationmeeting" name="durationmeeting" aria-describedby="durationHelp" required="required" value="">
	    <small id="durationHelp" class="form-text text-muted">{{ 'DurationOfMeetingHelp'|get_plugin_lang('ZoomApiPlugin') }}</small>
	  </div>
	  <div class="form-group">
	  	<label for="passwordmeeting">{{ 'PasswordMeeting'|get_plugin_lang('ZoomApiPlugin') }}</label>
	  	<div class="input-group" id="show_hide_password">
	      <input type="text" class="form-control" id="passwordmeeting" name="passwordmeeting" required="required" value="{{ passwordgenerated }}">
	      <div class="input-group-addon">
	        <a href=""><i class="fa fa-eye" aria-hidden="true"></i></a>
	      </div>
	    </div>
	  </div>
	</div>
	<br>
	<div class="col-md-12">
  		<button type="submit" class="btn btn-primary btn-block">{{ 'CreateNewMeeting'|get_plugin_lang('ZoomApiPlugin') }}</button>
  	</div>
	
</form>


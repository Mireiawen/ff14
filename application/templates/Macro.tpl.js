// XIVDB Tooltips setup
// https://github.com/xivdb/tooltips
var skip_tooltips = false;
var xivdb_tooltips =
{
	// Hide the loader/NoJS and show the actual tool
	includeHiddenLinks: true,
	event_tooltipsLoaded: function()
	{
		$("#macro_nojs").hide();
		$("#macro_container").show();
	},
	event_tooltipsError: function()
	{
		bootbox.alert({
			'title': '{t}Error loading tooltips{/t}',
			'message': '{t}Unable to load tooltips from XIVDB{/t}',
			'onEscape': true,
			'backdrop': true,
		});
		skip_tooltips = true;
		$("#macro_nojs").hide();
		$("#macro_container").show();
	},
};

// Initialize the system
$(function() 
{
	// Sortable skill list setup
	$("#sortable").sortable(
	{
		revert: true,
		update: function (event, ui)
		{
			// Recalculate CP after sorting
			CalculateCP();
		}
	});
	$("#macro-steps ul, #macro-steps li").disableSelection();
	
	// Prevent link clicks in the skill selecton list,
	// add new skill to the list instead
	$("#skill-list ul li").click(function(e)
	{
		e.preventDefault();
		AddAction($("a", this).attr("data-xivdb-id"));
	});
	
	// Reset the macro
	$("#button_reset").click(function(e)
	{
		ResetMacro();
	});

	// Generate the macro
	$("#button_generate").click(function(e)
	{
		GenerateMacros();
	});

	// Save the macro
	$("#button_save").click(function(e)
	{
		SaveMacro();
	});
	
	// Show or hide the echo settings
	$("#macro_echo").change(function()
	{
		if ($("option:selected", this).val() == 0)
		{
			$("#echo_parameters").hide();
		}
		else
		{
			$("#echo_parameters").show();
		}
	});
	
});

// Clear the Macro list
function ResetMacro()
{
	$("#macro-steps ul").empty();
	CalculateCP();
}

// Add an action to the end of the list
function AddAction(id, calculate = true)
{
	// Get the macro steps container
	skills = $("#macro-steps ul");
	
	// Find the skill information
	skill = $("#skill-list [data-xivdb-id='" + id + "']:first");
	name = skill.attr('data-name');
	icon = parseInt(skill.attr('data-icon'));
	cost = parseInt(skill.attr('data-cost'));
	buff = parseInt(skill.attr('data-buff'));
	img = $("#skill-list [data-xivdb-id='" + id + "'] img:first").clone();
	
	// Prepare the template data
	tmpl.regexp = /([\s'\\])(?!(?:[^[]|\[(?!%))*%\])|(?:\[%(=|#)([\s\S]+?)%\])|(\[%)|(%\])/g;
	data =
	{
		'xivdb_id': id,
		'cp_cost': cost,
		'name': skill.attr('data-name'),
		'type': buff,
		'link': sprintf('https://xivdb.com/action/%s', id),
		'img': sprintf('https://secure.xivdb.com/img/game/%06d/%06d.png', icon-icon%1000, icon),
	};

	// Append the template to the steps
	skills.append(tmpl('tmpl-skill', data));

	// Recalculate the tooltips
	if (!skip_tooltips)
	{
		XIVDBTooltips.get();
	}
	
	// Recalculate the CP amounts
	if (calculate)
	{
		CalculateCP();
	}
}

// Remove an action from the list
function RemoveAction(elem)
{
	// Remove the element from the steps list
	$(elem).parents('li').remove();
	
	// Recalculate the CP amounts
	CalculateCP();
}

// Recalculate the CP
function CalculateCP()
{
	// The element with CP calculation
	var cp_node = $("#macro_cp");
	
	// Current and maximum required CP
	var cp = 0;
	var max_cp = 0;
	
	// Current Comfort Zone steps
	var comfort_zone = 0;
	
	// Set the initial value to 0
	cp_node.text(0);
	
	// Go through all macro steps
	$("#macro-steps li").each(function()
	{
		// Prepare the template data
		tmpl.regexp = /([\s'\\])(?!(?:[^[]|\[(?!%))*%\])|(?:\[%(=|#)([\s\S]+?)%\])|(\[%)|(%\])/g;
		data = 
		{
			'cz_step': comfort_zone,
			'cp_cost': cp,
		};
		
		// Parse the Comfort Zone buff
		if (comfort_zone)
		{
			comfort_zone--;
			cp -= {$smarty.const.COMFORT_ZONE_TICK};
		}
		
		// Check if current step activates the Comfort Zone buff
		xivdb_id = $(this).attr('data-xivdb-id');
		if (xivdb_id == {$smarty.const.COMFORT_ZONE_ID})
		{
			comfort_zone = {$smarty.const.COMFORT_ZONE_DURATION}
		}
		
		// Add the CP cost
		cp += parseInt($(this).attr('data-cost'));
		data.cp_cost = cp;
		
		// Check the max CP cost
		max_cp = Math.max(cp, max_cp);
		
		// Update the current step information
		$(".info", this).html(tmpl('tmpl-step-info', data));
		
		// Update the CP calculator
		cp_node.text(max_cp);
	});
}

// The actual macro worker
function GenerateMacros()
{
	// Number of macros
	var macros = 0;
	
	// Lines in current macro
	var lines = Array();
	
	// Name of the macro
	var macro_name = $('#macro_name').val();
	
	// Show the macro echos at all
	var macro_echo = $('#macro_echo').val();
	
	// Macro skill and buff wait times
	var wait_skill = parseInt($("#macro_wait_skill").val());
	var wait_buff = parseInt($("#macro_wait_buff").val());
	
	// Macro line number
	if (macro_echo == 1)
	{
		var lines_per_macro = {$smarty.const.MACRO_MAX_ROWS} - 1;
	}
	else
	{
		var lines_per_macro = {$smarty.const.MACRO_MAX_ROWS};
	}
	
	// Macro text holder
	var macro_fields = $('#macro-text');
	macro_fields.empty();
	
	// Go through the steps
	$("#macro-steps li").each(function(index, elem)
	{
		// Check if we should wait for buff or skill
		if (parseInt($(this).attr("data-type")) == 1)
		{
			wait = wait_buff;
		}
		else
		{
			wait = wait_skill;
		}
		
		// Append the macro line
		lines.push(sprintf('/ac "%s" <me><wait.%d>', $(this).attr("data-name"), wait));
		
		// Check if we are below the line limit
		if (lines.length < lines_per_macro)
		{
			return;
		}
		
		// Add to number of macros
		macros++;
		
		// Check if we are the last element
		if ($(this)[0] === $("#macro-steps li").last()[0])
		{
			// The main will handle the last macro write
			return;
		}
		
		// Show the end echo
		tmpl.regexp = /([\s'\\])(?!(?:[^[]|\[(?!%))*%\])|(?:\[%(=|#)([\s\S]+?)%\])|(\[%)|(%\])/g;
		if (macro_echo == 1)
		{
			// Prepare the template data
			macro_data = 
			{
				'n': macros,
				'name': macro_name,
			}
			
			// Macro end and done message
			lines.push(sprintf('/echo %s', tmpl($('#macro_end').val(), macro_data)));
		}
		
		// Add the macro itself
		data = 
		{
			'n': macros,
			'name': macro_name,
			'lines': lines,
		};
		macro_fields.append(tmpl('tmpl-macro', data));
		lines = Array();
	});
	
	// Add to number of macros
	macros++;
	
	// Show the done echo
	tmpl.regexp = /([\s'\\])(?!(?:[^[]|\[(?!%))*%\])|(?:\[%(=|#)([\s\S]+?)%\])|(\[%)|(%\])/g;
	if (macro_echo == 1)
	{
		// Prepare the template data
		macro_data = 
		{
			'n': macros,
			'name': macro_name,
		}
			
		// Macro end and done message
		lines.push(sprintf('/echo %s', tmpl($('#macro_done').val(), macro_data)));
	}
	
	data = 
	{
		'n': macros,
		'name': macro_name,
		'lines': lines,
	};
	macro_fields.append(tmpl('tmpl-macro', data));
}

// Save the macro
function SaveMacro()
{
	// Prepare the data array
	data =
	{
		'macro_wait_skill': $('#macro_wait_skill').val(),
		'macro_wait_buff': $('#macro_wait_buff').val(),
		'macro_echo': $('#macro_echo').val(),
		'macro_name': $('#macro_name').val(),
		'macro_end': $('#macro_end').val(),
		'macro_done': $('#macro_done').val(),
		'skills': [],
	};
	
	// Go through the steps
	$("#macro-steps li").each(function(index, elem)
	{
		data['skills'].push($(this).attr('data-xivdb-id'));
	});
	
	// Do the actual AJAX call
	console.log(data);
	$.ajax(
	{
		url: "u:ajax/Store",
		type: "post",
		data: JSON.stringify([data]),
		success: function(data)
		{
			if ('hash' in data)
			{
				title = '{t}Macro saved{/t}';
				url = "f:handler/" + data.hash;
				message = sprintf('{t escape=no}Your macro has been saved. Please use <strong><a href="%s">%s</a></strong> to access it.{/t}', url, url);
			}
			else
			{
				title = '{t}Error{/t}';
				message = sprintf('{t escape=no}There was an error when saving the macro: %s{/t}', data);
			}
			
			bootbox.alert({
				'title': title,
				'message': message,
				'onEscape': true,
				'backdrop': true,
			});
			console.log(data);
		}
	});
}

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
	restore = parseInt(skill.attr('data-restore'));
	buff = parseInt(skill.attr('data-buff'));
	img = $("#skill-list [data-xivdb-id='" + id + "'] img:first").clone();
	
	// Prepare the template data
	tmpl.regexp = /([\s'\\])(?!(?:[^[]|\[(?!%))*%\])|(?:\[%(=|#)([\s\S]+?)%\])|(\[%)|(%\])/g;
	data =
	{
		'xivdb_id': id,
		'cp_cost': cost,
		'cp_restore': restore,
		'name': skill.attr('data-name'),
		'type': buff,
		'link': sprintf('https://xivdb.com/action/%s', id),
		'img': sprintf('https://secure.xivdb.com/img/game/%06d/%06d.png', icon-icon%1000, icon),
	};
	
	// Create template of the macro step
	skill = $.parseHTML(tmpl('tmpl-skill', data));
	
	// Prevent link clicks in the macro listing
	// ask user instead
	link = $('a', skills);
	link.off('click');
	link.click(function(e)
	{
		// Get the dialog template
		url = $(this).attr('href');
		d = { 'url': url, };
		msg = tmpl('confirm-dialog', d);
		bootbox.confirm({
			title: '{t}Confirm leaving the generator{/t}',
			message: msg,
			buttons:
			{
				cancel:
				{
					label: '{t}No{/t}',
					className: 'btn-success',
				},
				confirm:
				{
					label: '{t}Yes{/t}',
					className: 'btn-danger',
				},
			},
			callback: function(result)
			{
				if (result)
				{
					window.location = url;
				}
			},
		});
		e.preventDefault();
	});
	
	// Append the template to the steps
	skills.append(skill);
	
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
	
	// Current buff statuses
	var steady_hand = 0;
	var inner_quiet = 0;
	var great_strides = 0;
	var ingenuity = 0;
	var waste_not = 0;
	var innovation = 0;
	var name_of_wind = 0;
	var name_of_fire = 0;
	var name_of_ice = 0;
	var name_of_earth = 0;
	var name_of_lightning = 0;
	var name_of_water = 0;
	var heart = 0;
	var initial_preparations = 0;
	var rumination = 0;
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
			'cp_cost': cp,
			'steady_hand': steady_hand,
			'inner_quiet': inner_quiet,
			'great_strides': great_strides,
			'ingenuity': ingenuity,
			'waste_not': waste_not,
			'innovation': innovation,
			'name_of_wind': name_of_wind,
			'name_of_fire': name_of_fire,
			'name_of_ice': name_of_ice,
			'name_of_earth': name_of_earth,
			'name_of_lightning': name_of_lightning,
			'name_of_water': name_of_water,
			'heart': heart,
			'initial_preparations': initial_preparations,
			'rumination': rumination,
			'comfort_zone': comfort_zone,
		};
		
		// Steady Hand
		if (steady_hand)
		{
			steady_hand--;
		}
		
		// Parse the Great Strides buff
		if (great_strides)
		{
			great_strides--;
		}
		
		// Parse the Ingenuity buff
		if (ingenuity)
		{
			ingenuity--;
		}
		
		// Parse the Waste Not buff
		if (waste_not)
		{
			waste_not--;
		}
		
		// Parse the Innovation buff
		if (innovation)
		{
			innovation--;
		}
		
		// Parse the Name of element buffs
		if (name_of_wind)
		{
			name_of_wind--;
		}
		
		if (name_of_fire)
		{
			name_of_fire--;
		}
		
		if (name_of_ice)
		{
			name_of_ice--;
		}
		
		if (name_of_earth)
		{
			name_of_earth--;
		}
		
		if (name_of_lightning)
		{
			name_of_lightning--;
		}
		
		if (name_of_water)
		{
			name_of_water--;
		}
		
		// Parse the Heart of the Specialist buff
		if (heart)
		{
			heart--;
		}
		
		// Parse the Comfort Zone buff
		if (comfort_zone)
		{
			comfort_zone--;
			cp -= {$smarty.const.COMFORT_ZONE_TICK};
		}
		
		// Reset rumination for every step
		rumination = 0;
		
		// Parse the buff knowledge
		xivdb_id = parseInt($(this).attr('data-xivdb-id'));
		switch (xivdb_id)
		{
			// Check for Stady Hand/Steady Hand II
			case {$smarty.const.STEADY_HAND_ID}:
			case {$smarty.const.STEADY_HAND_2_ID}:
				steady_hand = {$smarty.const.STEADY_HAND_DURATION};
				break;
				
			// Check if current step activates the Inner Quiet buff
			case {$smarty.const.INNER_QUIET_ID}:
				if (inner_quiet == 0)
				{
					inner_quiet = 1;
				}
				break;
			
			// Check if current step activates the Great Strides buff
			case {$smarty.const.GREAT_STRIDES_ID}:
				great_strides = {$smarty.const.GREAT_STRIDES_DURATION};
				break;
			
			// Check if current step activates the Ingenuity or Ingenuity II buff
			case {$smarty.const.INGENUITY_ID}:
			case {$smarty.const.INGENUITY_2_ID}:
				ingenuity = {$smarty.const.INGENUITY_DURATION};
				break;
				
			// Check if current step activates the Waste Not or Waste Not II buff
			case {$smarty.const.WASTE_NOT_ID}:
				waste_not = {$smarty.const.WASTE_NOT_DURATION};
				break;
				
			case {$smarty.const.WASTE_NOT_2_ID}:
				waste_not = {$smarty.const.WASTE_NOT_2_DURATION};
				break;
				
			// Check if current step activates Innovation
			case {$smarty.const.INNOVATION_ID}:
				innovation = {$smarty.const.INNOVATION_DURATION};
				break;
				
			// Check if current step activates Name of the element
			case {$smarty.const.NAME_OF_WIND_ID}:
				name_of_wind = {$smarty.const.NAME_OF_DURATION};
				break;
				
			case {$smarty.const.NAME_OF_FIRE_ID}:
				name_of_fire = {$smarty.const.NAME_OF_DURATION};
				break;
				
			case {$smarty.const.NAME_OF_ICE_ID}:
				name_of_ice = {$smarty.const.NAME_OF_DURATION};
				break;
				
			case {$smarty.const.NAME_OF_EARTH_ID}:
				name_of_earth = {$smarty.const.NAME_OF_DURATION};
				break;
				
			case {$smarty.const.NAME_OF_LIGHTNING_ID}:
				name_of_lightning = {$smarty.const.NAME_OF_DURATION};
				break;
				
			case {$smarty.const.NAME_OF_WATER_ID}:
				name_of_water = {$smarty.const.NAME_OF_DURATION};
				break;
				
			// Heart of the specialist actions
			case {$smarty.const.HEART_OF_THE_CARPENTER}:
			case {$smarty.const.HEART_OF_THE_BLACKSMITH}:
			case {$smarty.const.HEART_OF_THE_ARMORER}:
			case {$smarty.const.HEART_OF_THE_GOLDSMITH}:
			case {$smarty.const.HEART_OF_THE_LEATHERWORKER}:
			case {$smarty.const.HEART_OF_THE_WEAVER}:
			case {$smarty.const.HEART_OF_THE_ALCHEMIST}:
			case {$smarty.const.HEART_OF_THE_CULINARIAN}:
				heart = {$smarty.const.HEART_OF_THE_DURATION};
				break;
				
			// Initial preparations
			case {$smarty.const.INITIAL_PREPARATIONS_ID}:
				initial_preparations = 1;
				break;
				
			// Specialist action: Reinforce
			case {$smarty.const.REINFORCE_ID}:
				initial_preparations = 0;
				break;
				
			// Specialist action: Refurbish
			case {$smarty.const.REFURBISH_ID}:
				initial_preparations = 0;
				cp -= {$smarty.const.REFURBISH_TICK};
				break;
				
			// Specialist action: Reflect
			case {$smarty.const.REFLECT_ID}:
				initial_preparations = 0;
				if (inner_quiet)
				{
					inner_quiet += {$smarty.const.REFLECT_TICK};
					if (inner_quiet > {$smarty.const.INNER_QUIET_MAX_STACK})
					{
						inner_quiet = {$smarty.const.INNER_QUIET_MAX_STACK};
					}
				}
				break;
			
			// Check for touch action for Inner Quiet
			// @note: we expect all touches to succeed
			case {$smarty.const.BASIC_TOUCH_ID}:
			case {$smarty.const.STANDARD_TOUCH_ID}:
			case {$smarty.const.ADVANCED_TOUCH_ID}:
			case {$smarty.const.HASTY_TOUCH_ID}:
			case {$smarty.const.HASTY_TOUCH_2_ID}:
			case {$smarty.const.PRUDENT_TOUCH_ID}:
			case {$smarty.const.FOCUSED_TOUCH_ID}:
			case {$smarty.const.TRAINED_HAND_ID}:
				if (great_strides)
				{
					great_strides = 0;
				}
				
				if (inner_quiet)
				{
					inner_quiet++;
					if (inner_quiet > {$smarty.const.INNER_QUIET_MAX_STACK})
					{
						inner_quiet = {$smarty.const.INNER_QUIET_MAX_STACK};
					}
				}
				break;
			
			case {$smarty.const.PRECISE_TOUCH_ID}:
			case {$smarty.const.PATIENT_TOUCH_ID}:
				if (great_strides)
				{
					great_strides = 0;
				}
				
				if (inner_quiet)
				{
					inner_quiet+=2;
					if (inner_quiet > {$smarty.const.INNER_QUIET_MAX_STACK})
					{
						inner_quiet = {$smarty.const.INNER_QUIET_MAX_STACK};
					}
				}
				break;
			
			// Innovative Touch
			case {$smarty.const.INNOVATIVE_TOUCH_ID}:
				if (great_strides)
				{
					great_strides = 0;
				}
				
				if (inner_quiet)
				{
					inner_quiet++;
					if (inner_quiet > {$smarty.const.INNER_QUIET_MAX_STACK})
					{
						inner_quiet = {$smarty.const.INNER_QUIET_MAX_STACK};
					}
				}
				
				innovation = {$smarty.const.INNOVATION_DURATION};
				break;
			
			// Byregot's Blessing and Brow
			case {$smarty.const.BYREGOTS_BLESSING_ID}:
			case {$smarty.const.BYREGOTS_BROW_ID}:
				inner_quiet = 0;
				
				if (great_strides)
				{
					great_strides = 0;
				}
				break;
			
			// Byregot's Miracle
			case {$smarty.const.BYREGOTS_MIRACLE_ID}:
				inner_quiet = Math.floor(inner_quiet / 2);
				
				if (great_strides)
				{
					great_strides = 0;
				}
				break;
				
			// Rumination
			case {$smarty.const.RUMINATION_ID}:
				if (inner_quiet > 1)
				{
					inner_quiet--;
					rumination = (21 * inner_quiet - inner_quiet * inner_quiet + 10) / 2;
					cp -= rumination;
					if (cp < 0)
					{
						cp = 0;
					}
					inner_quiet = 0;
				}
				break;
				
			// Check if current step activates the Comfort Zone buff
			case {$smarty.const.COMFORT_ZONE_ID}:
				comfort_zone = {$smarty.const.COMFORT_ZONE_DURATION};
				break;
		}
		
		// Add the CP cost
		cp += parseInt($(this).attr('data-cost'));
		
		// Check the max CP cost
		max_cp = Math.max(cp, max_cp);
		
		// Add the CP restore
		cp -= parseInt($(this).attr('data-restore'));
		if (cp < 0)
		{
			cp = 0;
		}
		data.cp_cost = cp;
		
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
		}
	});
}

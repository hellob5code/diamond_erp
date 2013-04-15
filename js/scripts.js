/**
 * Custom JS scripts used to help
 * main functionality of the application.
 * 
 * Loaded on SIAF
 * 
 * @author Marko Aleksic <psybaron@gmail.com>
 */
(function(){

	$(document).on("click",".check-all", function(e) {
		$(this).closest('table.data-grid').find('input[type=checkbox]').prop('checked', this.checked);
	});

	$(document).on("click",".confirm-delete", function(e) {
		console.log(e);
		bootbox.animate(false);
	    bootbox.confirm("Sure you want to proceed?", function(result) {
	        if(result){window.location.href = e.target.href;}
	    });
	    return false;
	});
		
	//Prevents Form re-submission
	$('form').submit(function() {
	    $(this).submit(function() {
	        return false;
	    });
	    return true;
	});

})();

// $(document).keypress(function(e){
// 	/*
// 	 * If keyboard key "v" is pressed (#118)
// 	 * Redirects to Insert page
// 	 */
// 	  if(e.charCode == 118){  
// 		var link = $('a.insert').attr('href');
		
// 		if(link !== undefined)
// 			location.replace(link);
// 	  }
	  
// 	  /*
// 	   * If keyboard key "enter" is pressed (#13)
// 	   * Form submited to default action
// 	   */
// 	  if(e.charCode == 13){  
// 			$('form').submit(function(){});
// 	  }
// });

//Diamond ERP API global object
var cd = (function(){

	var obj = {};

	obj.notify = function(text, type){

		$.pnotify.defaults.title = "Diamond ERP";

		$.pnotify.defaults.sticker = false;

		$.pnotify.defaults.delay = 1750;

		var pnotify_opt = {
			text: text,
			type: type,
			shadow: false,
			opacity: .9
		};
		$.pnotify(pnotify_opt);
	};

	obj.completeJobOrders = function(url){

		var ids = $(".job-order:checked").map(function(i,n) {
	        return $(n).val();
	    }).get();

		if(ids.length == 0){
			this.notify("Потребно е да селектирате барем една ставка");
			return false;
		}

		$.post(url,{ids:JSON.stringify(ids)}, function(data) {
		  if(data) location.reload(true);
		}, 'json');
	}

	obj.dropdownTasks = function(url,data){

		var data;
		var tasks = $("select#tasks");
		var uname = $("input#uname");
		var task = $("input[name=task_fk]");
		/*
		 * When an employee is changed, searches the tasks assigned
		 * to this employee, and populates the dropdown
		 *
		 */
		$("select#employee").on("change",function() {
			var employee = $(this).val();	
		    tasks.select2("enable");
		    if(task.val()){
		    	task.val('');
				uname.val('');
				tasks.select2("val","");
		    }
			$.getJSON(url,{employee:employee}, function(result) {
				data = result;
				var options = '<option></option>';
				$.each(result, function(i, row){
					 options += '<option value="' + row.id + '" data-uname="'+ row.uname +'">' + row.taskname + '</option>';
			    });
			    tasks.html(options);
			});
		});
		
		/*
		 * When task is changed, populates the hidden task ID 
		 *	and unit of measure of the same task
		 */	
		$("select#tasks").on("change",function(e) {	
			task.val($(this).val());
			if(e.val !== ''){
				uname.val(data[this.selectedIndex-1].uname);  
			} else {
				uname.val('');
			}
		});
	}

	return obj;
})();
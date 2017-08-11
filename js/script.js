jQuery(document).ready(function($) {
	
	//Namespace
	$.fac = function(){};
	
	$.fac.setMsg = function(text){
		msgEl = $($.fac.id($.fac.statMsgId));
		msgEl.show().html(text);
		msgEl.focus();
	
	}
	
	$.fac.timer_delay = 1000;
	
	//HTML Constants
	
	$.fac.statMsgId = "picasa_sync_msg";
	$.fac.disconDlg = undefined;
	$.fac.disconDlgId = "disconnect-album-check-dialog";
	
	$.fac.syncDlg = undefined;
	$.fac.syncDlgId = "sync-album-dialog";
	$.fac.importWaitingId = 'fac-album-waiting-msg-container';
	
	
	//CSS Helpers
	
	$.fac.id= function(id){
		return "#"+id;
	}
	
	$.fac.cls =function(cls){
		return "."+cls;
	}
	
	$.fac.in_progress = null;
	
	$.fac.proto_progress = {
		title: null,
		remote_album_id: null, //for fetching what post is being worked with
		//Private members
		_total: null,
		_retrieved: -1,
		_timer: null, 
		_pid: getParam('post'),
		_isNewPost: false,
		_startUpdating: false,
		
		// ** State & Status
		
		start: function(){
			if(this._pid == "null"){
				$.fac.setMsg(this.title.toLowerCase() + "ing..."); //Set msg in the meantime
				this.getCountAlternate();
				this._isNewPost = true;
			}else{
				this.update();
				this.setTimer();
			}
		},

		
		setTimer: function(){
			this._timer = window.setInterval(function(obj){ obj.update();}, $.fac.timer_delay, this);
		},
		
		stop: function(){
			window.clearInterval(this._timer);
			this._timer = null;
		},
		
		complete: function(srv_txt){
			this.stop();
			$.fac.setMsg(this.title + " complete.");
			
			strt= srv_txt.lastIndexOf("url:");
			
			if(strt != -1){
				href = srv_txt.substring(4, srv_txt.length);
				href = href.replace("amp;", "");
				location.replace(href);
			}else if(this._isNewPost){
				this._redirectToPost();
			}
			else{
				location.reload();
			}
		},
		
		//Using the post id from within this object redirect the location to the post
		_redirectToPost: function(){
			wp_ad= 'wp-admin/';
			path = window.location.href;
			subEnd = path.indexOf(wp_ad) + wp_ad.length; //cut path write after wp_admin
			base = path.substr(0, subEnd);
			location.replace(base + "post.php?action=edit&post="+this._pid); //Redirect to post edit
		},
		
		error: function(srv_txt){
			this.stop();
		
			setTimeout(function(){$.fac.setMsg('Unknown Server Error. Sync failed.');}, $.fac.timer_delay + 500);
		},
		
		// ** UI
		
		draw: function(){
			if(this._startUpdating){
				$.fac.setMsg(this.title+"ing: "+ this._retrieved +" of " + this._total + " retrieved.");
			}	
		
		},
		
		// ** Actions
		
		getCount: function(){
			this._ajaxy(Actions.totalImages, function(val){ this._total = val; if(!this._startUpdating){ $.fac.setMsg(this.title+"ing: 0 of " + this._total + " retrieved.");} }, { pid:  this._pid });
		}, 
		
		getCountAlternate: function(){
			this._ajaxy(Actions.totalImages, 
				function(val){ 
					this._total = val; 
					$.fac.setMsg( this.title + "ing " + this._total + " images.");
				 }, { rid:  this.remote_album_id });
		},
		
		onRetrieve: function(val){
			if(this._startUpdating && val == this._total){ //finished syncing
				this.complete("");
			}
			
			if(this._retrieved != -1 && val > this._retrieved){ this._startUpdating = true;}
			this._retrieved = val; 
			
			this.draw();
		},
		
		getRetrieved: function(){
			this._ajaxy(Actions.actualImages, this.onRetrieve, { pid:  this._pid });
		},
		
		update: function(){
			//update both for now since to cover case when album being switched
			this.getCount(); 
			this.getRetrieved();	
		},
		
		_ajaxy: function(action, fnc, data_args){
			fin_data_args  = $.extend({ action: action}, data_args);
			
			$.ajax({
				url: ajaxurl,
				context: this,
				type: 'POST',
				data: fin_data_args,
				success: fnc });
		}
	}
	
	$.fac.get_progress = function(type, remote_id){
		var obj = Object.create($.fac.proto_progress);
		obj.title =  type;
		obj.remote_album_id = remote_id; 
		return obj;
	}
	
	
	$.fac.syncAlbumModal = function(incgid){
		
		
		if ($.fac.syncDlg == undefined){
			out = "<div style='margin: 10px 15px, width: auto, height: 40px' id='" + $.fac.syncDlgId + "'>";
			out += "<table><tbody>";
			out += "<tr><td><input type='checkbox' id='sync_album_description'/><label for='sync_album_description'>Import Album Description</label></td></tr>";
			out += "<tr><td><input type='checkbox' id='sync_photo_captions'/><label for='sync_photo_captions'>Import Photo Captions</label></td></tr>";
			out += "</table></tbody>";
			out +="</div>"
			
			$(document.body).append(out);
			$.fac.syncDlg = $($.fac.id($.fac.syncDlgId));
		}
		
		var $here = $.fac.syncDlg;
	    
		$here.dialog({                   
	        'dialogClass'   : 'wp-dialog',           
	        'modal'         : true,
			'title': "Remove Album Link",
	        'autoOpen'      : false, 
	        'closeOnEscape' : true,      
			width: 250,
			height: 40,
	        'buttons'       : [
		
			{
	           	text:"Cancel",
				click: function() {
	                $(this).dialog('close');
	            }
		},
		
			{
				text: "Sync",
				click: function(){
					inc_al_desc = $('#sync_album_description').val();
					inc_ph_desc = $('#sync_photo_captions').val();
					$.fac.sync_album({gid: incgid, al_desc: inc_al_desc, ph_desc: inc_ph_desc}, "Sync");
					$(this).dialog('close');
				}

			}
		
		] 

		});
		
		$here.dialog('open');
		$here.width(250);
		$here.height(40);
		$here.css('margin', '10px 15px');
		
	}
	
	$.fac.sync_album = function(args, text){
		
		disableBtns();
		$.fac.in_progress = $.fac.get_progress(text, args.gid);
		$.fac.in_progress.start();
		
		jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			data: { action: Actions.connect , pid: getParam('post') , gid: args.gid, al_desc: args.al_desc, ph_desc: args.ph_desc},
			success: function(r) {
					$.fac.setMsg(text+' Complete');
					enableBtns();
					$.fac.in_progress.complete(r);
			},
			error: function(r) {
				enableBtns();
				$.fac.in_progress.error(r);
			}
		});
	}
	
	//User Interactions
	$.fac.interact = function(){	
	}
	
	$.fac.interact.disconnectAlbum = function(incgid){
			//Start up modal to check if user is sure of disconnect

			if ($.fac.disconDlg == undefined){
				$(document.body).append("<div style='margin: 10px 15px, width: auto, height: 40px' id='" + $.fac.disconDlgId + "'><p class='description'>Are you sure you'd like to remove this album link?</p></div>");
				$.fac.disconDlg = $($.fac.id($.fac.disconDlgId));
			}
	
			
			var $here = $.fac.disconDlg;
		    
			$here.dialog({     
				'dialogId': 'disc_modal',              
		        'dialogClass'   : 'wp-dialog disc_modal',           
		        'modal'         : true,
				'title': "Remove Album Link",
		        'autoOpen'      : false, 
		        'closeOnEscape' : true,      
				width: 250,
				height: 40,
		        'buttons'       : [
			
				{
		           	text:"Cancel",
					click: function() {
		                $(this).dialog('close');
		            }
			},
			
				{
					text: "Remove",
					click: function(){
						disableBtns();
						$here.dialog('close');
						
						$.ajax({
							data: { action: Actions.disconnect , pid: getParam('post') , gid: incgid}, 
							url: ajaxurl, 
							type: "POST",
						
							success: function(r){
								enableBtns();
								$.fac.setMsg("Removing link...");
								
								setTimeout("location.reload()", 2000);

							 }, //In 2 secs refresh the page
							error: function(r){
								$.fac.setMsg(r);
								enableBtns();
				 			}
						})

					}
				}
			
			] 

			});
			
			$here.dialog('open');
			
			modal = jQuery('.disc_modal');
			topVal = modal.css('top');
			topVal = topVal.substring(0, topVal.indexOf('px'));
			topVal = parseInt(topVal) - 150;
			modal.css('top', topVal.toString() +'px');
			
			jQuery('#disconnect-album-check-dialog').width(220);
			jQuery('#disconnect-album-check-dialog').height(40);
			jQuery('#disconnect-album-check-dialog').css('margin', '10px 15px');
			
	}
	
	
	
	//Non refactored code
	
	Cons = {
		submitBtnClass: 'button-primary'
	} 
	
	Actions = {
		getConnectables: "fac_get_connectables", 
		connect: "fac_sync_album",
		disconnect: "fac_diassociate_album",
		totalImages: 'fac_remote_album_image_count',
		actualImages: 'fac_local_album_image_count',
		latestAlbumId: 'fac_latest_post_id', 
		syncingPostId: 'fac_syncing_post_id'
	}

		
	var $c = {
		modId: 'import-connected-modal', //modal element id
		conTitle: "Album", //connectable title
		btnId: "import-submit-button", //submit ajax post button
		albSelId: "albums-list", //album select el id
		openModBtnId: "import-connected-btn"
	}
	
	$('body').append("<div id='" + $c.modId + "'/>");
	
	function disable(name, tag){
		$(tag ? tag : 'button' +":contains('"+name+"')").attr("disabled","disabled"); 
	}
	
	function enable(name, tag){
			$(tag ? tag : "button" +":contains('"+name+"')").prop("disabled", null); 
	}
	
	function disableBtns(){
			disable("Sync");
			disable("Import");
			disable("Remove");
			$('#import-connected-btn').attr('disabled', 'disabled');
			$("#fotomoto-picasa-sync .error a").attr('disabled', 'disabled');
			$('#picasa_sync_link').attr('disabled', 'disabled');
	}
	
	function enableBtns(){
			enable("Sync");
			enable("Import");
			enable("Remove");
			$("#fotomoto-picasa-sync .error a").prop('disabled', null);
			$('#import-connected-btn').prop('disabled', null);
			$('#picasa_sync_link').prop('disabled', null);
	}
	
	var populateConnectables = function(){
		
		jQuery.ajax({
			url: ajaxurl, type: 'GET', data: { action: Actions.getConnectables },
			success: function(r){ 
				 $('#' + $c.albSelId ).html(r);
				$($.fac.id( $.fac.importWaitingId)).html('');
				enable("Import");
			} 
		});
		
	};
	
	function getParam(name) {
	    return decodeURI(
	        (RegExp(name + '=' + '(.+?)(&|$)').exec(location.search)||[,null])[1]
	    );
	}
	
	var onOpen = function(event, ui){
		indiv = $( '#import-connected-modal');
		
		if(indiv.is(":empty")){ //If div is empty, populate it
			out ="<div style='margin: 10px 15px'>";
			out += "<p id='"+$.fac.importWaitingId+"'>Please wait while albums are loaded.</p>";
			out += "<p><table ><tbody><tr><td>";
			out += "</select><label for='" + $c.albSelId + "'>Albums</label><select disable='disabled' title='Choose an album to import into post.' id='" + $c.albSelId + "'><option>Loading albums...</option></td></tr>";
			out += "<tr><td><input type='checkbox' id='imp_album_description'/><label for='imp_album_description'>Import Album Description</label></td></tr>";
			out += "<tr><td><input type='checkbox' id='imp_photo_captions'/><label for='imp_photo_captions'>Import Photo Captions</label></td></tr>";
			out += "<tr><td></td></tr>";
			out += "</tbody></table></p></div>"; 
			indiv.html(out);

			disable("Import");
			populateConnectables();
		}
		
	};
	
    var $info = $('#' + $c.modId);
    $info.dialog({                   
        'dialogClass'   : 'wp-dialog',           
        'modal'         : true,
		'title': "Import Album",
        'autoOpen'      : false, 
		'open': onOpen,
        'closeOnEscape' : true,      
        'buttons'       : [
		{
           	text:"Cancel",
			click: function() {
                $(this).dialog('close');
            }
		}, 
		{
			text: "Import",
			click: function(){
			sel = $("#" + $c.albSelId);
			ngid = sel.val();
			npid = getParam('post');
			inc_al_desc = $('#imp_album_description').val();
			inc_ph_desc = $('#imp_photo_captions').val();
			stat = $($.fac.id( $.fac.importWaitingId));
			
			args = { action: Actions.connect , pid: npid, gid: ngid, al_desc: inc_al_desc , ph_desc: inc_ph_desc  };
			$(this).dialog('close');
			disableBtns();
			$.fac.sync_album(args, "Import");

		}
	}
	] 
    	
	});

    $('#'+ $c.openModBtnId).click(function(event) {
        event.preventDefault();
        $info.dialog('open');
    });

	
});
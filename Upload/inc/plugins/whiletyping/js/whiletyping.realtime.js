document.observe("dom:loaded", function() {
	// add a container above the textfield
	var container = new Element('div',{'id':"whiletyping_notifier"}).setStyle({'color':'red'});
	if($("message"))
		$("message").insert({'before':container});
	
	// add a periodical pull to check if there are new messages
	var current_script = THIS_SCRIPT.split('.')[0];
	var interval = window.setInterval(function(){
		new Ajax.Request('xmlhttp.php?action=whiletyping&tid='+MYBB_TID+'&script='+current_script,{
			method: 'get',
			onSuccess: function(response){
				container.update(response.responseText);
				
				// check if script.aculo.us is loaded
				if(typeof Effect === "object")
				{
					new Effect.Pulsate(container);
				}
			}
		});
	}, 5000);
	
	// clear the whiletyping_notifier when the quick_reply_submit button is pressed
	if($("quick_reply_submit"))
	{
		$("quick_reply_submit").observe("click", function(){
			$("whiletyping_notifier").update('');
		});
	}
});

function whiletypingShowPosts()
{
	new Ajax.Request('xmlhttp.php?action=whiletyping_get_posts&tid='+MYBB_TID,{
		method: 'get',
		onSuccess: function(response){
			var posts_html = response.responseText;
			var pids = posts_html.match(/id="post_([0-9]+)"/g);
			var lastpid = pids.pop().match(/id="post_([0-9]+)"/);
			if(lastpid !== null) lastpid = lastpid[1];
			var posts = document.createElement("div");
			posts.innerHTML = posts_html;
			$('posts').appendChild(posts);
			if(MyBB.browser == "ie" || MyBB.browser == "opera" || MyBB.browser == "safari" || MyBB.browser == "chrome")
			{
				var scripts = posts_html.extractScripts();
				scripts.each(function(script)
				{
					eval(script);
				});
			}
			
			if($('lastpid') && lastpid !== null)
			{
				$('lastpid').value = lastpid;
			}
			
		}
	});
	
	if($('whiletyping_notifier'))
	{
		$('whiletyping_notifier').update('');
	}
}


function whiletypingSubmitPreview()
{
	whiletypingSimulateClick($$("input[name='previewpost']")[0]);
}
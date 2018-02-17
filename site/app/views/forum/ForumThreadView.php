<?php
namespace app\views\forum;

use app\authentication\DatabaseAuthentication;
use app\views\AbstractView;
use app\models\Course;
use app\libraries\FileUtils;


class ForumThreadView extends AbstractView {


	public function forumAccess(){
        return $this->core->getConfig()->isForumEnabled();
    }
	
	/** Shows Forums thread splash page, including all posts
		for a specific thread, in addition to all of the threads
		that have been created to be displayed in the left panel.
	*/
	public function showForumThreads($user, $posts, $threads) {
		if(!$this->forumAccess()){
			$this->core->redirect($this->core->buildUrl(array('component' => 'navigation')));
			return;
		}

		$this->core->getOutput()->addBreadcrumb("Discussion Forum", $this->core->buildUrl(array('component' => 'forum', 'page' => 'view_thread')));
		
		//Body Style is necessary to make sure that the forum is still readable...
		$return = <<<HTML
		<style>body {min-width: 925px;}</style>

		<script>
		function openFile(directory, file, path ){
			window.open("{$this->core->getConfig()->getSiteUrl()}&component=misc&page=display_file&dir=" + directory + "&file=" + file + "&path=" + path,"_blank","toolbar=no,scrollbars=yes,resizable=yes, width=700, height=600");
		}
		</script>

HTML;
	if($this->core->getUser()->getGroup() <= 2){
		$return .= <<<HTML
		<script>
								function changeName(element, user, visible_username, anon){
									new_element = element.getElementsByTagName("strong")[0];
									icon = element.getElementsByClassName("fa fa-eye");
									if(icon.length == 0){
										icon = element.getElementsByClassName("fa fa-eye-slash");
									} icon = icon[0];
									if(new_element.innerText == visible_username) {
										if(anon) {
											new_element.style.color = "grey";
											new_element.style.fontStyle = "italic";
										}
										new_element.innerText = user;
										icon.className = "fa fa-eye-slash";
										icon.title = "Hide full user information";
									} else {
										if(anon) {
											new_element.style.color = "black";
											new_element.style.fontStyle = "normal";
										}
										new_element.innerText = visible_username;
										icon.className = "fa fa-eye";
										icon.title = "Show full user information";
									}
									
								}
		</script>
HTML;
	}
	$return .= <<<HTML
		<div style="margin-top:5px;background-color:transparent; margin: !important auto;padding:0px;box-shadow: none;" class="content">

		<div style="margin-top:10px; margin-bottom:10px; height:50px;  " id="forum_bar">
			<div style="margin-left:20px; height: 50px; width:50px;" class="create_thread_button"><a title="Create thread" href="{$this->core->buildUrl(array('component' => 'forum', 'page' => 'create_thread'))}"><i style="vertical-align: middle; position: absolute; margin-top: 9px; margin-left: 11px;" class="fa fa-plus-circle fa-2x" aria-hidden="true"></i></a>
			</div>
		</div>

HTML;
		if(count($threads) == 0){
		$return .= <<<HTML
					<div style="margin-left:20px;margin-top:10px;margin-right:20px;padding:25px; text-align:center;" class="content">
						<h4>A thread hasn't been created yet. Be the first to do so!</h4>
					</div>
				</div>
HTML;
		} else {


			$return .= <<<HTML
				<div id="forum_wrapper">
					<div class="thread_list">
HTML;
					$used_active = false; //used for the first one if there is not thread_id set
					$function_date = 'date_format';
					$activeThreadTitle = "";
					$activeThread = array();
					$current_user = $this->core->getUser()->getId();
					$activeThreadAnnouncement = false;
					$start = 0;
					$end = 10;
					foreach($threads as $thread){
						$first_post = $this->core->getQueries()->getFirstPostForThread($thread["id"]);
						$date = date_create($first_post['timestamp']);
						$class = "thread_box";
						//Needs to be refactored to rid duplicated code
						if(!isset($_REQUEST["thread_id"]) && !$used_active){
							$class .= " active";
							$used_active = true;
							$activeThread = $thread;
							$activeThreadTitle = $thread["title"];
							if($thread["pinned"])
								$activeThreadAnnouncement = true;
						} else if(isset($_REQUEST["thread_id"]) && $_REQUEST["thread_id"] == $thread["id"]) {
							$class .= " active";
							$activeThreadTitle = $thread["title"];
							$activeThread = $thread;
							if($thread["pinned"])
								$activeThreadAnnouncement = true;
						}

						if($this->core->getQueries()->viewedThread($current_user, $thread["id"])){
							$class .= " viewed";
						}
						$contentDisplay = substr($first_post["content"], 0, 80);
						$titleDisplay = substr($thread["title"], 0, 30);
						if(strlen($first_post["content"]) > 80){
							$contentDisplay .= "...";
						}
						if(strlen($thread["title"]) > 30){
							$titleDisplay .= "...";
						}
						$return .= <<<HTML
						<a href="{$this->core->buildUrl(array('component' => 'forum', 'page' => 'view_thread', 'thread_id' => $thread['id']))}">
						<div class="{$class}">
HTML;
						if($thread["pinned"] == true){
							$return .= <<<HTML
							<i class="fa fa-star" style="position:relative; float:right; display:inline-block; color:gold; -webkit-text-stroke-width: 1px;
    -webkit-text-stroke-color: black;" aria-hidden="true"></i>
HTML;
						}
						$return .= <<<HTML
						<h4>{$titleDisplay}</h4>
						<h5 style="font-weight: normal;">{$contentDisplay}</h5>
						<h5 style="float:right; font-weight:normal;margin-top:5px">{$function_date($date,"m/d/Y g:i A")}</h5>
						</div>
						</a>
						<hr style="margin-top: 0px;margin-bottom:0px;">
HTML;
					}

			$thread_id = -1;
			$function_content = 'nl2br';
			$title_html = '';
			$return .= <<< HTML
					</div>
					<div style="display:inline-block;width:70%; float: right;" class="posts_list">
HTML;

            $title_html .= <<< HTML
            <h3 style="max-width: 95%; display:inline-block;word-wrap: break-word;margin-top:10px; margin-left: 5px;">
HTML;
					if($this->core->getUser()->getGroup() <= 2 && $activeThreadAnnouncement){
                        $title_html .= <<<HTML
							<a style="display:inline-block; color:orange; " onClick="alterAnnouncement({$activeThread['id']}, 'Are you sure you want to remove this thread as an announcement?', 'remove_announcement')" title="Remove thread from announcements"><i class="fa fa-star" onmouseleave="changeColor(this, 'gold')" onmouseover="changeColor(this, '#e0e0e0')" style="position:relative; display:inline-block; color:gold; -webkit-text-stroke-width: 1px;
    -webkit-text-stroke-color: black;" aria-hidden="true"></i></a>
HTML;
                    } else if($activeThreadAnnouncement){
                        $title_html .= <<<HTML
						 <i class="fa fa-star" style="position:relative; display:inline-block; color:gold; -webkit-text-stroke-width: 1px; -webkit-text-stroke-color: black;" aria-hidden="true"></i>
HTML;
                    } else if($this->core->getUser()->getGroup() <= 2 && !$activeThreadAnnouncement){
                        $title_html .= <<<HTML
							<a style="position:relative; display:inline-block; color:orange; " onClick="alterAnnouncement({$activeThread['id']}, 'Are you sure you want to make this thread an announcement?', 'make_announcement')" title="Make thread an announcement"><i class="fa fa-star" onmouseleave="changeColor(this, '#e0e0e0')" onmouseover="changeColor(this, 'gold')" style="position:relative; display:inline-block; color:#e0e0e0; -webkit-text-stroke-width: 1px;
    -webkit-text-stroke-color: black;" aria-hidden="true"></i></a>
HTML;
                    }
                    $title_html .= <<< HTML
					{$activeThreadTitle}</h3>
HTML;
					$first = true;
					$order_array = array();
					$reply_level_array = array();
					foreach($posts as $post){
						if($thread_id == -1) {
							$thread_id = $post["thread_id"];
						}
						if($post["parent_id"] > 1){
							$place = array_search($post["parent_id"], $order_array);
							$tmp_array = array($post["id"]);
							array_splice($order_array, $place+1, 0, $tmp_array);
							$parent_reply_level = $reply_level_array[$place];
							array_splice($reply_level_array, $place+1, 0, $parent_reply_level+1);
						} else {
							array_push($order_array, $post["id"]);
							array_push($reply_level_array, 1);
						}
					}
					$i = 0;
					foreach($order_array as $ordered_post){
						foreach($posts as $post){
							if($post["id"] == $ordered_post){
								if($post["parent_id"] == 1) {
									$reply_level = 1;	
								} else {
									$reply_level = $reply_level_array[$i];
								}
								
								$return .= $this->createPost($thread_id, $post, $function_content, $function_date, $title_html, $first, $reply_level);
								break;
							}
							
						}
						if($first){
							$first= false;
						}
						$i++;
					}

			$return .= <<<HTML
			
					<form style="margin-right:17px;" method="POST" action="{$this->core->buildUrl(array('component' => 'forum', 'page' => 'publish_post'))}" enctype="multipart/form-data">
					<input type="hidden" name="thread_id" value="{$thread_id}" />
	            	<br/>
	            	<div class="form-group row">
	            		<textarea name="post_content" id="post_content" style="white-space: pre-wrap;resize:none;height:100px;width:100%;" rows="10" cols="30" placeholder="Enter your reply here..." required></textarea>
	            	</div>

	            	<br/>

	           		<span style="float:left;display:inline-block;">
            			<label id="file_input_label" class="btn btn-primary" for="file_input">
    					<input id="file_input" name="file_input[]" accept="image/*" type="file" style="display:none" onchange="checkNumFilesForumUpload(this)" multiple>
    					Upload Attachment
						</label>
						<span class='label label-info' id="file_name"></span>
					</span>

	            	<div style="margin-bottom:20px;float:right;" class="form-group row">
	            		<label style="display:inline-block;" for="Anon">Anonymous?</label> <input type="checkbox" style="margin-right:15px;display:inline-block;" name="Anon" value="Anon" /><input type="submit" style="display:inline-block;" name="post" value="Reply" class="btn btn-primary" />
	            	</div>
	            	</form>
	            	<br/>

					</div>

				</div>
				</div>
HTML;
		}

if(isset($_SESSION["post_content"]) && isset($_SESSION["post_recover_active"])){
			
	$post_content = html_entity_decode($_SESSION["post_content"]);

	$return .= <<<HTML
			<script>
				var contentBox = document.getElementById('post_content');
				contentBox.innerHTML = `{$post_content}`;
				document.getElementById('file_input').value = null;
				var box = $('.posts_list');
				box.scrollTop(box.prop('scrollHeight'));
			</script>
HTML;
		$_SESSION["post_recover_active"] = null;
}

		return $return;
	}

	public function createPost($thread_id, $post, $function_content, $function_date, $title_html, $first, $reply_level){
		$post_html = "";
		$post_id = $post["id"];
		$thread_dir = FileUtils::joinPaths(FileUtils::joinPaths($this->core->getConfig()->getCoursePath(), "forum_attachments"), $thread_id);

		$date = date_create($post["timestamp"]);
		$full_name = $this->core->getQueries()->getDisplayUserNameFromUserId($post["author_user_id"]);
		if($post["anonymous"]){
			$visible_username = "Anonymous";
		} else {
			$visible_username = substr($full_name, 0, strpos($full_name, " ")+2) . ".";
		}
		$classes = "post_box";						
		
		if($first){
			$classes .= " first_post";
		}

		if($this->core->getQueries()->isStaffPost($post["author_user_id"])){
			$classes .= " important";
		}

		$offset = ($reply_level-1)*15;
		$post_html .= <<<HTML
			<div class="$classes" id="$post_id" style="margin-left:{$offset}px;" reply-level="$reply_level">
HTML;

		if($this->core->getUser()->getGroup() <= 2){
			$post_html .= <<<HTML
				<a class="post_button" style="position:absolute; display:inline-block; color:red; float:right;" onClick="deletePost( {$post['thread_id']}, {$post['id']}, '{$post['author_user_id']}', '{$function_date($date,'m/d/Y g:i A')}' )" title="Remove post"><i class="fa fa-times" aria-hidden="true"></i></a>
HTML;
		}
		 
		if($first){
			$first = false;
			$post_html .= $title_html;
		} else {
			$post_html .= <<<HTML
				<a style="float:right; right: 25px; position: absolute" onClick="replyPost({$post['thread_id']}, {$post['id']}, '{$post['author_user_id']}', '{$function_date($date,'m/d/Y g:i A')}')"> reply </a>
HTML;
		}
		// This Chunk below is the pop-up window for reply.
		$post_html .= <<<HTML
			<div class="popup-form" id="reply-user-post">
				<h3 id="reply_user_prompt"></h3>
				<form method="post" action="{$this->core->buildUrl(array('component' => 'forum', 'page' => 'reply_post'))}">
					<input type="hidden" id="reply_thread_id" name="reply_thread_id" value="" />
					<input type="hidden" id="reply_parent_id" name="reply_parent_id" value="" />
					<textarea name="reply_post_content" id="reply_post_content" style="margin-right:10px;white-space: pre-wrap;resize:none;min-height:200px;width:98%;" placeholder="Enter your reply here..." required></textarea>
					<div style="float: right; width: auto; margin-top: 10px">
						<a onclick="$('#reply-user-post').css('display', 'none');" class="btn btn-danger">Cancel</a>
						<input class="btn btn-primary" type="submit" value="Submit" />
					</div>	
				</form>
			</div>
			<p class="post_content">{$function_content($post["content"])}</p>
			<hr style="margin-bottom:3px;"><span style="margin-top:5px;margin-left:10px;float:right;">		
HTML;

	if($this->core->getUser()->getGroup() <= 2){
		$info_name = $full_name . " (" . $post['author_user_id'] . ")";
		$post_html .= <<<HTML
		<a style=" margin-right:2px;display:inline-block; color:black; " onClick="changeName(this.parentNode, '{$info_name}', '{$visible_username}', {$post['anonymous']}	)" title="Show full user information"><i class="fa fa-eye" aria-hidden="true"></i></a>
HTML;
	}
	$post_html .= <<<HTML
		<h7><strong id="post_user_id">{$visible_username}</strong> {$function_date($date,"m/d/Y g:i A")}</h7></span>
HTML;

		if($post["has_attachment"]){
			$post_dir = FileUtils::joinPaths($thread_dir, $post["id"]);
			$files = FileUtils::getAllFiles($post_dir);
			foreach($files as $file){
				$path = urlencode(htmlspecialchars($file['path']));
				$name = urlencode(htmlspecialchars($file['name']));
				$name_display = htmlentities($file['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
				$post_html .= <<<HTML
			<a href="#" style="display:inline-block;white-space: nowrap;" class="btn-default btn-sm" onclick="openFile('forum_attachments', '{$name}', '{$path}')" > {$name_display} </a>
HTML;
			}
		}
		$post_html .= <<<HTML
			</div>
HTML;
		return $post_html;
	}

	public function createThread() {

		if(!$this->forumAccess()){
			$this->core->redirect($this->core->buildUrl(array('component' => 'navigation')));
			return;
		}

		$this->core->getOutput()->addBreadcrumb("Discussion Forum", $this->core->buildUrl(array('component' => 'forum', 'page' => 'view_thread')));
		$this->core->getOutput()->addBreadcrumb("Create Thread", $this->core->buildUrl(array('component' => 'forum', 'page' => 'create_thread')));
		$return = <<<HTML

		<div style="margin-top:5px;background-color:transparent; margin: !important auto;padding:0px;box-shadow: none;" class="content">

		<div style="margin-top:10px; margin-bottom:10px; height:50px;  " id="forum_bar">
			<div style="margin-left:20px; height:50px; width:50px;" class="create_thread_button"><a href="{$this->core->buildUrl(array('component' => 'forum', 'page' => 'view_thread'))}"><i style="vertical-align: middle; position: absolute; margin-top: 8px; margin-left: 10px;" class="fa fa-arrow-left fa-2x" aria-hidden="true"></i></a>
			</div>
		</div>

		<div style="padding-left:20px;padding-top:1vh;height:69vh;border-radius:3px;box-shadow: 0 2px 15px -5px #888888;padding-right:20px;background-color: #E9EFEF;" id="forum_wrapper">

		<h3> Create Thread </h3>

			<form style="padding-right:15px;margin-top:15px;margin-left:10px;height:63vh;overflow-y: auto" method="POST" action="{$this->core->buildUrl(array('component' => 'forum', 'page' => 'publish_thread'))}" enctype="multipart/form-data">

            	<div class="form-group row">
            		Title: <input type="text" size="45" placeholder="Title" name="title" id="title" required/>
            	</div>
            	<br/>
            	<div class="form-group row">
            		<textarea name="thread_content" id="thread_content" style="white-space: pre-wrap;resize:none;height:50vh;width:100%;" rows="10" cols="30" placeholder="Enter your post here..." required></textarea>
            	</div>

            	<br/>

            	<div style="margin-bottom:10px;" class="form-group row">

            	<span style="float:left;display:inline-block;">
            	<label id="file_input_label" class="btn btn-primary" for="file_input">
    				<input id="file_input" name="file_input[]" accept="image/*" type="file" style="display:none" onchange="checkNumFilesForumUpload(this)" multiple>
    				Upload Attachment
				</label>
				<span class='label label-info' id="file_name"></span>
				</span>

				<span style="display:inline-block;float:right;">
            	<label for="Anon">Anonymous?</label> <input type="checkbox" style="margin-right:15px;display:inline-block;" name="Anon" value="Anon" />
HTML;
				
				if($this->core->getUser()->getGroup() < 4){
						$return .= <<<HTML
						<label style="display:inline-block;" for="Announcement">Announcement?</label> <input type="checkbox" style="margin-right:15px;display:inline-block;" name="Announcement" value="Announcement" />
HTML;

				}
				$return .= <<<HTML
				<input type="submit" style="display:inline-block;" name="post" value="Post" class="btn btn-primary" />
				</span>
            	</div>

            	<br/>

            </form>
		</div>
		</div>
HTML;

if(isset($_SESSION["thread_title"]) && isset($_SESSION["thread_content"]) && isset($_SESSION["thread_recover_active"])){
	$title = html_entity_decode($_SESSION["thread_title"]);
			
	$thread_content = html_entity_decode($_SESSION["thread_content"]);

	$return .= <<<HTML
			<script>
				var titleBox = document.getElementById('title');
				titleBox.value = `{$title}`;
				var contentBox = document.getElementById('thread_content');
				contentBox.innerHTML = `{$thread_content}`;
				document.getElementById('file_input').value = null;
			</script>
HTML;
		unset($_SESSION["thread_recover_active"]);
}
		return $return;
	}

}
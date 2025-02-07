<?php
if (!defined("IN_MYBB") || !defined("IN_ADMINCP")) {
    die("Direct access is not allowed.");
}

$page->add_breadcrumb_item("Name Finder", "index.php?module=user-name_finder");

if (!$mybb->input['action']) {
    $page->output_header("Name Finder");

    $sub_tabs['name_finder'] = array(
        'title' => "Name Finder",
        'link' => "index.php?module=user-name_finder",
        'description' => "Search users by Discord ID or previous usernames."
    );

    $page->output_nav_tabs($sub_tabs, 'name_finder');

    $form = new Form("index.php?module=user-name_finder", "post");
    $form_container = new FormContainer("Name Finder");

    $form_container->output_row(
        "Enter Discord ID or Username:",
        "",
        $form->generate_text_box("search_term", $mybb->input['search_term'], array('id' => 'search_term')),
        'search_term'
    );

    $form_container->end();

    $buttons[] = $form->generate_submit_button("Search");
    $form->output_submit_wrapper($buttons);
    $form->end();

    if ($mybb->request_method == "post" && $mybb->input['search_term']) {
        $search_term = $db->escape_string($mybb->input['search_term']);

		if (ctype_digit($search_term)) {
			$discord_query = $db->simple_select("users", "*", "discord_id='{$search_term}'");
			if ($db->num_rows($discord_query) > 0) {
				$table = new Table;
				$table->construct_header("Username");
				$table->construct_header("UID");
				$table->construct_header("Discord ID");
	
				while ($user = $db->fetch_array($discord_query)) {
					$table->construct_cell(htmlspecialchars_uni($user['username']));
					$table->construct_cell(htmlspecialchars_uni($user['uid']));
					$table->construct_cell(htmlspecialchars_uni($user['discord_id']));
					$table->construct_row();
				}
	
				$table->output("Discord ID Search Results");
			} else {
				$page->output_inline_error("No users found with the specified Discord ID.");
			}
		} else {
			$username_query = $db->simple_select("usernamehistory", "*", "username='{$search_term}'");
	
			if ($db->num_rows($username_query) > 0) {
				$table = new Table;
				$table->construct_header("Username");
				$table->construct_header("UID");
				
				while ($entry = $db->fetch_array($username_query)) {
					$user_info = $db->fetch_array($db->simple_select("users", "*", "uid='{$entry['uid']}'"));
					$table->construct_cell(htmlspecialchars_uni($user_info['username']));
					$table->construct_cell(htmlspecialchars_uni($user_info['uid']));
					$table->construct_row();
				}
	
				$table->output("Username History Search Results");
			} else {
				$page->output_inline_error("No users found with the specified previous username.");
			}
		}
    }

    $page->output_footer();
}

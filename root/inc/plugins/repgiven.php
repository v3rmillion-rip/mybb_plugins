<?php

if (!defined('IN_MYBB')) {
    die('This file cannot be accessed directly.');
}

function repgiven_info()
{
    return array(
        "name"          => "Rep given",
        "description"   => "Repuation given by user",
        "website"       => "",
        "author"        => "JP (yakov)",
        "authorsite"    => "",
        "version"       => "1.0",
        "guid"          => "",
        "compatibility" => "18*"
    );
}

function repgiven_install()
{
	file_put_contents("repgiven.php", '<?php
define(\'IN_MYBB\', 1);
require_once \'./global.php\';
if(is_object($plugins)) {
	$plugins->run_hooks(\'repgiven_start\');
}
?>');
}

function repgiven_is_installed()
{
    return file_exists(MYBB_ROOT . 'repgiven.php');
}

function repgiven_uninstall()
{
    unlink(MYBB_ROOT . 'repgiven.php');
}

function repgiven_activate()
{
	
	global $db;

	$template_content = '<html>
<head>
<title>{$mybb->settings[\'bbname\']} - Reputation given by {$target_username}</title>
{$headerinclude}
<script type="text/javascript">
	var delete_reputation_confirm = "{$lang->delete_reputation_confirm}";
</script>
</head>
<body>
{$header}
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
    <tr>
        <td class="thead" colspan="{$colspan}"><strong>Reputation given by {$target_username}</strong></td>
    </tr>
    <tr>
        <td class="tcat"><strong>Comments</strong></td>
    </tr>
	<tr>
		{$repgiven}
    </tr>
	<tr>
	<td class="tfoot" align="right">
	<form action="repgiven.php" method="get">
		<input type="hidden" name="uid" value="{$uid}">
		<select name="show">
			<option value="all">Show: All Votes</option>
			<option value="positive">Show: Positive Ratings</option>
			<option value="neutral">Show: Neutral Ratings</option>
			<option value="negative">Show: Negative Ratings</option>
		</select>
		<select name="sort">
			<option value="dateline" selected="selected">Sort by: Last Updated</option>
			<option value="username">Sort by: Username</option>
		</select>
		<input type="submit" class="button" value="Go">
	</form>
	</td>
</tr>
</table>
{$multipage}
{$footer}
</body>
</html>';
    $template_array = array(
        'title'     => 'misc_viewrepgiven',
        'template'  => $db->escape_string($template_content),
        'sid'       => '-1',
        'version'   => '',
        'dateline'  => TIME_NOW
    );
    $db->insert_query("templates", $template_array);
}

function repgiven_deactivate()
{
	global $db;

    $db->delete_query("templates", "title = 'misc_viewrepgiven'");
}



$plugins->add_hook("repgiven_start", "repgiven_run");
function repgiven_run() {
    global $mybb, $db, $lang, $templates, $theme, $headerinclude, $header, $footer, $multipage, $report_link, $delete_link;

    $uid = $mybb->get_input('uid', MyBB::INPUT_INT);
    $show = $mybb->get_input('show', MyBB::INPUT_STRING) ?: "all";
    $sort = $mybb->get_input('sort', MyBB::INPUT_STRING) ?: "dateline";
    $page = $mybb->get_input('page', MyBB::INPUT_INT) ?: 1;
    $limit = (int)$mybb->settings['repsperpage']; // Number of entries per page
    if($limit < 1)
    {
        $limit = 15;
    }
    $start = ($page > 0) ? ($page - 1) * $limit : 0;

    $rep_count = 0;
    $target_username = "no one";
    $repgiven = "<tr>";
    $colspan = 5;
    $lang->load("reputation");

    if (!$uid || $uid && empty($uid)) {
        error($lang->add_no_uid);
    }
    else {
        if ($uid === 1 && !is_super_admin($mybb->user['uid'])) {
            error($lang->reputations_disabled_user);
        }
        $query = $db->simple_select('users', 'username', 'uid=' . (int)$uid);
        $queried_username = $db->fetch_field($query, 'username');

        if ($queried_username) {
            $target_username = $queried_username;

            $lang->nav_profile = $lang->sprintf($lang->nav_profile, $target_username);
            add_breadcrumb($lang->nav_profile, get_profile_link($uid));
            add_breadcrumb($lang->nav_reputation);

            $order_by = ($sort === "username") ? "u.username ASC" : "r.dateline DESC";
            $where_clause = "r.adduid = " . (int)$uid;

            // Filter for positive, neutral, or negative votes
            if ($show == "positive") {
                $where_clause .= " AND r.reputation > 0";
            } elseif ($show == "negative") {
                $where_clause .= " AND r.reputation < 0";
            } elseif ($show == "neutral") {
                $where_clause .= " AND r.reputation = 0";
            }

            // Count total results for pagination
            $total_count_query = $db->simple_select("reputation r", "COUNT(*) AS total", $where_clause);
            $total_count = (int)$db->fetch_field($total_count_query, "total");

            // Fetch paginated results
            $query = $db->query("
                SELECT r.*, u.username as target_username
                FROM " . TABLE_PREFIX . "reputation r
                LEFT JOIN " . TABLE_PREFIX . "users u ON r.adduid = u.uid
                WHERE " . $where_clause . "
                ORDER BY " . $order_by . "
                LIMIT " . $start . ", " . $limit
            );

            $reputation_records = [];

            while ($rep = $db->fetch_array($query)) {
                $uid_reciept = (int)$rep['uid'];
                $vote_reputation = (int)$rep['reputation'];
                $status_class = "trow_reputation_neutral";
                $vote_type_class = "reputation_neutral";
                $vote_type = "Neutral";

                if ($vote_reputation < 0) {
                    $status_class = "trow_reputation_negative";
                    $vote_type_class = "reputation_negative";
                    $vote_type = "Negative";
                } elseif ($vote_reputation > 0) {
                    $vote_reputation = "+{$vote_reputation}";
                    $status_class = "trow_reputation_positive";
                    $vote_type_class = "reputation_positive";
                    $vote_type = "Positive";
                }
                $vote_reputation = "({$vote_reputation})";

                $dateline = my_date('relative', $rep['dateline']);
                $recipt_query = $db->simple_select('users', 'username, reputation, usergroup', 'uid=' . $uid_reciept);
                $queried_recipt_user = $db->fetch_array($recipt_query);

                if ($queried_recipt_user) {
                    $reputation_records[] = [
                        'rep' => $rep,
                        'uid_reciept' => $uid_reciept,
                        'vote_reputation' => $vote_reputation,
                        'status_class' => $status_class,
                        'vote_type_class' => $vote_type_class,
                        'vote_type' => $vote_type,
                        'dateline' => $dateline,
                        'queried_recipt_user' => $queried_recipt_user
                    ];
                }
            }

            foreach ($reputation_records as $record) {
                $rep = $record['rep'];
                $uid_reciept = $record['uid_reciept'];
                $vote_reputation = $record['vote_reputation'];
                $status_class = $record['status_class'];
                $vote_type_class = $record['vote_type_class'];
                $vote_type = $record['vote_type'];
                $dateline = $record['dateline'];
                $queried_recipt_user = $record['queried_recipt_user'];

                $recipt_rep = $queried_recipt_user['reputation'];
                $recipt_username = $queried_recipt_user['username'];
                $usergroup_query = $db->simple_select('usergroups', 'namestyle', 'gid=' . (int)$queried_recipt_user['usergroup']);
                $usergroup = $db->fetch_field($usergroup_query, 'namestyle');

                $styled_username = str_replace("{username}", $recipt_username, $usergroup);
                $user_comments = htmlspecialchars_uni($rep['comments']);
                if ($user_comments == '') 
                {
                    $user_comments = $lang->no_comment;
                }

                $delete_link = '';
	            if($mybb->usergroup['issupermod'] == 1 || ($mybb->usergroup['candeletereputations'] == 1 && $uid == $mybb->user['uid'] && $mybb->user['uid'] != 0))
	            {
	            	$delete_link = "<div class=\"float_right postbit_buttons\">
		                <a href=\"reputation.php?action=delete&amp;uid={$uid_reciept}&amp;rid={$rep['rid']}&amp;my_post_key={$mybb->post_code}\" onclick=\"MyBB.deleteReputation({$uid_reciept}, {$rep['rid']}); return false;\" class=\"postbit_qdelete\"><span>{$lang->delete_vote}</span></a>
	                </div>";
                }

                $recipt_rep_class = "reputation_neutral";
                if ($recipt_rep < 0) {
                    $recipt_rep_class = "reputation_negative";
                } elseif ($recipt_rep > 0) {
                    $recipt_rep_class = "reputation_positive";
                }

                $repgiven .= "<tr>
                                <td class=\"trow1 {$status_class}\" id=\"rid{$rep['rid']}\">
                                    {$report_link}{$delete_link}
                                    <a href=\"/member.php?action=profile&amp;uid={$uid_reciept}\">{$styled_username}</a>
                                    <span class=\"smalltext\">(
                                        <a href=\"reputation.php?uid={$uid_reciept}\">
                                            <strong class=\"{$recipt_rep_class}\">{$recipt_rep}</strong>
                                        </a>
                                    ) - {$dateline}
                                    <br /></span>
                                    <br />
                                    <strong class=\"{$vote_type_class}\">{$vote_type} {$vote_reputation}:</strong>
                                    <br> {$user_comments}
                                </td>
                            </tr>";
                $rep_count++;
            }
        }

        // Generate pagination
        $multipage = multipage($total_count, $limit, $page, "repgiven.php?uid={$uid}&show={$show}&sort={$sort}");

        if ($rep_count === 0) {
            $repgiven .= "<td align=\"left\"><span>No reputation given at this time.</span></td>";
        }

        $repgiven .= "</tr>";
        $viewrepgiven = '';
        eval("\$viewrepgiven = \"".$templates->get("misc_viewrepgiven")."\";");
        output_page($viewrepgiven);
    }
}
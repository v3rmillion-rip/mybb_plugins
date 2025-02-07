<?php
if(!defined("IN_MYBB")) {
    die("Direct access not allowed.");
}

function discord_verification_info()
{
    return array(
        "name" => "Discord Verification",
        "description" => "Adds a Discord verification button to the user profile contact details.",
        "website" => "",
        "author" => "Mellon",
        "authorsite" => "",
        "version" => "1.1",
        "compatibility" => "18*"
    );
}

function discord_verification_install()
{
    global $db;

    if(!$db->field_exists('discord_verified', 'users')) {
        $db->add_column('users', 'discord_verified', 'TINYINT(1) NOT NULL DEFAULT 0');
        $db->add_column('users', 'discord_tag', 'VARCHAR(100) NOT NULL DEFAULT ""');
        $db->add_column('users', 'discord_id', 'VARCHAR(100) NOT NULL DEFAULT ""');
		$db->add_column('users', 'discord_avatar', 'VARCHAR(255) NOT NULL DEFAULT ""');
		$db->add_column('users', 'discord_dateline', 'int(10) unsigned NOT NULL DEFAULT 0');
    }
}

function discord_verification_is_installed()
{
    global $db;
    return $db->field_exists('discord_verified', 'users');
}

function discord_verification_uninstall()
{
    global $db;
}

function discord_verification_activate()
{
}

function discord_verification_deactivate()
{
}

function discord_verification_button()
{
    global $mybb, $templates, $memprofile, $discord_verified, $contact_details, $db;

	$disc_avatar = $mybb->user['discord_avatar'];

    if($mybb->user['uid'] == $memprofile['uid']) {	
		  if ($disc_avatar) {
			$avatar_url = file_exists(MYBB_ROOT . $disc_avatar) ? $disc_avatar : "default_discord.png";
		  }
          if ($mybb->user['discord_verified']) {
            $discord_verified = '
            <tr>
                <td class="trow1">
                    <strong>Verified Discord:</strong>
                    <br>
                    <a class="button" href="https://discord.com/oauth2/authorize?client_id=REDACTED&response_type=code&redirect_uri=https%3A%2F%2Fv3rmillion.rip%2Fdiscord_verify.php&scope=identify">Update</a>
                </td>
                <td class="trow1">
                    <div style="font-size:14px;display:inline-block;font-family: \'Ubuntu Mono\', monospace, monospace;">
                        <img draggable="false" src="' . $avatar_url . '" onerror="this.style.display=\'none\'" alt="Profile" title="Profile" style="padding: 2px; height: 26px; border-radius: 20px; border: 1px solid rgb(114, 137, 218); vertical-align: middle;">
                        <span style="display:inline-block;text-align:left;margin-left:5px;vertical-align: middle;">
                            <a href="https://v3rm.rip/discord">' . htmlspecialchars($mybb->user['discord_tag']) . '</a>
                            <br>ID: ' . htmlspecialchars($mybb->user['discord_id']) . '
                        </span>
                    </div>
                </td>
            </tr>';
        } else {
            $discord_verified = '
            <tr>
                <td class="trow1">
                    <strong>Verified Discord:</strong>
                </td>
                <td class="trow1">
                    <a class="button" style="border-color:#7289da;background-color:#7289da;" href="https://discord.com/oauth2/authorize?client_id=REDACTED&response_type=code&redirect_uri=https%3A%2F%2Fv3rmillion.rip%2Fdiscord_verify.php&scope=identify">Verify Discord</a>
                </td>
            </tr>';
        }
    }
    else
    {
		$muid = (int)$memprofile['uid'];
        $query = $db->simple_select("users", "*", "uid = {$muid}");
		$user = $db->fetch_array($query);
		if ($user && $user['discord_verified']) {
		  
		   $disc_avatar = $user['discord_avatar'];
		   if ($disc_avatar) {
			 $avatar_url = file_exists(MYBB_ROOT . $disc_avatar) ? $disc_avatar : "default_discord.png";
		   }
           $discord_verified = '
           <tr>
               <td class="trow1">
                   <strong>Verified Discord:</strong>
               </td>
               <td class="trow1">
                   <div style="font-size:14px;display:inline-block;font-family: \'Ubuntu Mono\', monospace, monospace;">
                       <img draggable="false" src="' . $avatar_url . '" onerror="this.style.display=\'none\'" alt="Profile" title="Profile" style="padding: 2px; height: 26px; border-radius: 20px; border: 1px solid rgb(114, 137, 218); vertical-align: middle;">
                       <span style="display:inline-block;text-align:left;margin-left:5px;vertical-align: middle;">
                           <a href="https://v3rm.rip/discord">' . htmlspecialchars($user['discord_tag']) . '</a>
                           <br>ID: ' . htmlspecialchars($user['discord_id']) . '
                       </span>
                   </div>
               </td>
           </tr>';
		}
    }

}
$plugins->add_hook("member_profile_start", "discord_verification_button");

function discord_verification_contact_display()
{
    global $mybb, $templates, $memprofile, $discord_verified, $contact_details, $lang, $theme;
	if ($contact_details === '') {
		$theme['borderwidth'] = "0"; 
		$theme['tablespace'] = "10";
		$lang->users_contact_details = $lang->sprintf($lang->users_contact_details, $memprofile['username']);
		eval('$contact_details = "'.$templates->get("member_profile_contact_details").'";');
	}

}
$plugins->add_hook("member_profile_end", "discord_verification_contact_display");


function discord_verification_post_warning(&$post) {
    
	global $mybb, $unverified_discord, $db;
	
	// Premium and normal sellers
    if ($post['fid'] != 18 && $post['fid'] != 19) {
        return;
    }
	
	$uid = (int)$post['uid'];
	$query = $db->simple_select("users", "*", "uid = {$uid}");
	$user = $db->fetch_array($query);
	if ($user && !$user['discord_verified']) {
		$unverified_discord = '
		<div style="background-color: #202020; text-align: center;">
			<span style="color: #808080; font-size: 14px;">
				<i class="fa fa-exclamation-triangle" aria-hidden="true" style="color: #CD1818; padding: 5px;"></i>
				This user has not <a href="https://v3rm.rip/discord">linked</a> their Discord. If you choose to do deals with them, we recommend using the on-site <a href="private.php?action=send&amp;uid=' . $uid . '">PM System</a> exclusively so that you can report them easily if they scam.
			</span>
		</div>';		
		$post['discord_warning'] = $unverified_discord;
	}
}
$plugins->add_hook('postbit', 'discord_verification_post_warning');

?>
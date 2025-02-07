<?php

if(!defined("IN_MYBB"))
{
    die("This file cannot be accessed directly.");
}

function notlogined_redirect_info()
{
    return array(
        "name"          => "Not Logged In Redirect",
        "description"   => "Redirects logged-in users away from the login page.",
        "website"       => "",
        "author"        => "Mellon",
        "authorsite"    => "",
        "version"       => "1.0",
        "compatibility" => "18*",
    );
}

function notlogined_redirect_activate()
{
    global $db;
}

function notlogined_redirect_deactivate()
{
}

function notlogined_redirect()
{
    global $mybb;

    if ($mybb->input['action'] == "login" && $mybb->user['uid'] != 0)
    {
        header("Location: index.php");
        exit;
    }
}
$plugins->add_hook("global_start", "notlogined_redirect");
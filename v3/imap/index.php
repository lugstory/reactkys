<?php

include("chilkat.php");

$success = false;

// An Office365 OAuth2 access token must first be obtained prior
// to running this code.

// Getting the OAuth2 access token for the 1st time requires the O365 account owner's 
// interactive authorizaition via a web browser.  Afterwards, the access token
// can be repeatedly refreshed automatically.

// See the following examples for getting and refreshing an OAuth2 access token

// Get Office365 SMTP/IMAP/POP3 OAuth2 Access Token
// Refresh Office365 SMTP/IMAP/POP3 OAuth2 Access Token

// First get our previously obtained OAuth2 access token.
$jsonToken = new CkJsonObject();
$success = $jsonToken->LoadFile('qa_data/tokens/office365.json');
if ($success == false) {
    print 'Failed to open the office365 OAuth JSON file.' . "\n";
    exit;
}

$imap = new CkImap();

$imap->put_Ssl(true);
$imap->put_Port(993);

// Connect to the Office365 IMAP server.
$success = $imap->Connect('outlook.office365.com');
if ($success == false) {
    print $imap->lastErrorText() . "\n";
    exit;
}

// Use OAuth2 authentication.
$imap->put_AuthMethod('XOAUTH2');

// Login using our username (i.e. email address) and the access token for the password.
$success = $imap->Login('OFFICE365_EMAIL_ADDRESS',$jsonToken->stringOf('access_token'));
if ($success == false) {
    print $imap->lastErrorText() . "\n";
    exit;
}

print 'O365 OAuth authentication is successful.' . "\n";

// Get the list of mailboxes.
$refName = '';
$wildcardedMailbox = '*';
$subscribed = false;

$mboxes = new CkMailboxes();
$success = $imap->MbxList($subscribed,$refName,$wildcardedMailbox,$mboxes);
if ($success == false) {
    print $imap->lastErrorText() . "\n";
    exit;
}

$i = 0;
while ($i < $mboxes->get_Count()) {
    print $mboxes->getName($i) . "\n";
    $i = $i + 1;
}

// Sample output looks like this:
// Archive
// Calendar
// Calendar/Birthdays
// Calendar/United States holidays
// Contacts
// Conversation History
// Deleted Items
// Drafts
// INBOX
// INBOX/abc
// INBOX/misc
// INBOX/misc/birdeye
// INBOX/old
// INBOX/old/large
// INBOX/receipts
// Journal
// Junk Email
// Notes
// Outbox
// RSS Subscriptions
// Sent Items
// Sync Issues
// Sync Issues/Conflicts
// Sync Issues/Local Failures
// Sync Issues/Server Failures
// Tasks
// Trash

// Disconnect from the IMAP server.
$success = $imap->Disconnect();

?>